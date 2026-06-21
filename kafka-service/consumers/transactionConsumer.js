"use strict";

/**
 * consumers/transactionConsumer.js
 *
 * Subscribe ke topic 'transaction-events', group 'pos-transaction-group'.
 * Satu-satunya consumer yang memproses event 'transaction.created'.
 *
 * Tanggung jawab utama:
 * 1. Validasi ketersediaan stok aktual di DB (BUKAN dari payload — payload bisa stale)
 * 2. Kurangi stok secara atomik jika semua item cukup
 * 3. Update status transaksi menjadi COMPLETED atau FAILED
 * 4. Push notifikasi real-time ke kasir dan semua terminal via WebSocket
 */

const { TOPICS } = require("../config/kafka");

// ─────────────────────────────────────────────────────────────────────────────
// FUNGSI PENDUKUNG
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Validasi ketersediaan stok untuk semua item transaksi.
 *
 * Mengapa validasi stok dilakukan di Consumer, bukan di Controller Laravel?
 * Karena Controller hanya mencatat PENDING dan langsung return 202 ke kasir.
 * Stok aktual dicek di sini agar tidak ada race condition antara cek di PHP
 * dan eksekusi di DB — Consumer adalah single-threaded gate keeper stok.
 *
 * Mengapa SELECT di luar DB transaction (sebelum BEGIN)?
 * Karena SELECT tanpa lock tidak memblokir transaksi lain.
 * Stok bisa berubah antara SELECT ini dan UPDATE di dalam transaksi, tapi
 * itu bukan masalah: jika berubah, UPDATE berikutnya masih akan mengurangi
 * nilai terkini — MySQL UPDATE bersifat atomic.
 * Race condition sesungguhnya hanya terjadi jika dua consumer memproses
 * transaksi yang mengkonsumsi stok produk yang sama secara bersamaan,
 * yang tidak terjadi karena satu group ID hanya punya satu active consumer.
 *
 * Fungsi ini JUGA menyimpan qty_stok saat validasi ke item._qtySaatIni
 * agar bisa dihitung stokBaru untuk payload WebSocket tanpa query tambahan.
 *
 * @param {import('mysql2/promise').Pool} pool
 * @param {Array}  items  - item dari payload Kafka event
 * @returns {Promise<{valid: boolean, message: string}>}
 */
async function validateStock(pool, items) {
  for (const item of items) {
    // Konversi ke satuan dasar — KRITIS
    // Contoh: qty=2 karton, isiSatuan=96 → butuh 192 pcs dari stok
    const qtyDibutuhkan = item.qty * item.isiSatuan;

    const [rows] = await pool.query(
      `SELECT qty_stok, nama_produk
             FROM   produk
             WHERE  id = ? AND status = 'aktif'`,
      [item.produkId],
    );

    if (rows.length === 0) {
      return {
        valid: false,
        message: `Produk ID ${item.produkId} tidak ditemukan atau sudah tidak aktif`,
      };
    }

    const { qty_stok, nama_produk } = rows[0];

    // Catat stok saat ini — dipakai untuk menghitung stokBaru di WebSocket push
    // tanpa perlu SELECT lagi setelah UPDATE (hemat satu round-trip DB)
    item._qtySaatIni = qty_stok;
    item._qtyDibutuhkan = qtyDibutuhkan;
    item._namaProduk = nama_produk;

    if (qty_stok < qtyDibutuhkan) {
      // Hitung stok tersisa dalam satuan yang sama dengan yang diminta kasir
      const stokDalamSatuan = Math.floor(qty_stok / item.isiSatuan);
      const satuanLabel = item.namaSatuan || "unit";

      return {
        valid: false,
        message:
          `Stok ${nama_produk} tidak mencukupi ` +
          `(tersedia: ${stokDalamSatuan} ${satuanLabel}, ` +
          `dibutuhkan: ${item.qty} ${satuanLabel})`,
      };
    }
  }

  return { valid: true, message: "" };
}

/**
 * Tandai transaksi sebagai FAILED dan push notifikasi ke kasir.
 * Dipakai di dua tempat: stok tidak cukup + error di tengah transaksi DB.
 */
async function markFailed(pool, transaksiId, userId, message, wsService) {
  try {
    await pool.query(`UPDATE transaksi SET status = 'FAILED' WHERE id = ?`, [
      transaksiId,
    ]);
  } catch (err) {
    // Jika UPDATE ini pun gagal, tetap lanjutkan untuk push WS
    console.error(
      `[TRX CONSUMER] Gagal UPDATE status FAILED transaksi ${transaksiId}:`,
      err.message,
    );
  }

  wsService.push(`user-${userId}`, {
    event: "transaction.failed",
    transaksiId: transaksiId,
    status: "FAILED",
    message: message,
  });

  console.log(`[TRX CONSUMER] Transaksi ${transaksiId} → FAILED | ${message}`);
}

// ─────────────────────────────────────────────────────────────────────────────
// PROSES UTAMA
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Proses satu event 'transaction.created' dari Kafka.
 *
 * @param {object}  event       - payload yang di-parse dari Kafka message
 * @param {import('mysql2/promise').Pool}  pool
 * @param {object}  wsService   - { push, pushAll }
 */
async function processTransaction(event, pool, wsService) {
  const { transaksiId, items, userId } = event;

  // ── Langkah 2-3: Validasi stok semua item ────────────────────────────────
  console.log(
    `[TRX CONSUMER] Memvalidasi stok untuk transaksi ${transaksiId} (${items.length} item)...`,
  );

  let validation;
  try {
    validation = await validateStock(pool, items);
  } catch (err) {
    console.error(
      `[TRX CONSUMER] Error saat validasi stok transaksi ${transaksiId}:`,
      err.message,
    );
    await markFailed(
      pool,
      transaksiId,
      userId,
      "Terjadi kesalahan sistem saat validasi stok",
      wsService,
    );
    return;
  }

  // ── Langkah 4: Stok tidak mencukupi ──────────────────────────────────────
  if (!validation.valid) {
    await markFailed(pool, transaksiId, userId, validation.message, wsService);
    return;
  }
  const conn = await pool.getConnection();

  try {
    await conn.beginTransaction();

    const stokUpdates = []; // akumulasi untuk payload stock.updated WebSocket

    // ── 5b: Kurangi stok setiap item ────────────────────────────────────
    for (const item of items) {
      await conn.query(
        `UPDATE produk
                 SET    qty_stok = qty_stok - ?
                 WHERE  id = ? AND status = 'aktif'`,
        [item._qtyDibutuhkan, item.produkId],
      );

      // Hitung stok baru untuk payload WebSocket
      // Menggunakan nilai yang sudah direkam saat validasi — tidak perlu SELECT lagi
      stokUpdates.push({
        produkId: item.produkId,
        stokBaru: item._qtySaatIni - item._qtyDibutuhkan,
      });
    }

    // ── 5c: Update status transaksi ──────────────────────────────────────
    await conn.query(`UPDATE transaksi SET status = 'COMPLETED' WHERE id = ?`, [
      transaksiId,
    ]);

    // ── 5d: Commit — semua perubahan permanen ────────────────────────────
    await conn.commit();

    console.log(`[TRX CONSUMER] Transaksi ${transaksiId} → COMPLETED`);

    // ── 5e: Push notifikasi personal ke kasir ────────────────────────────
    wsService.push(`user-${userId}`, {
      event: "transaction.completed",
      transaksiId: transaksiId,
      status: "COMPLETED",
      message: "Transaksi berhasil diproses",
    });

    // ── 5f: Push update stok ke semua terminal ───────────────────────────
    // Semua kasir yang sedang aktif akan melihat stok terbaru secara real-time
    // tanpa perlu refresh halaman
    wsService.pushAll("stok-global", {
      event: "stock.updated",
      updates: stokUpdates,
    });
  } catch (err) {
    // ── Error di tengah DB transaction → ROLLBACK ────────────────────────
    console.error(
      `[TRX CONSUMER] Error saat proses transaksi ${transaksiId}:`,
      err.message,
    );

    try {
      await conn.rollback();
      console.log(
        `[TRX CONSUMER] Rollback berhasil untuk transaksi ${transaksiId}`,
      );
    } catch (rollbackErr) {
      console.error(
        `[TRX CONSUMER] Rollback gagal transaksi ${transaksiId}:`,
        rollbackErr.message,
      );
    }

    // Tandai FAILED menggunakan pool (bukan conn yang sudah error)
    await markFailed(
      pool,
      transaksiId,
      userId,
      "Terjadi kesalahan sistem saat memproses transaksi",
      wsService,
    );
  } finally {
    // WAJIB release — tanpa ini koneksi tidak kembali ke pool dan pool akan habis
    conn.release();
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// CONSUMER SETUP
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Inisialisasi dan jalankan Kafka consumer untuk transaction-events.
 *
 * @param {import('kafkajs').Kafka}            kafka
 * @param {import('mysql2/promise').Pool}      pool
 * @param {object}                             wsService
 * @returns {Promise<import('kafkajs').Consumer>}
 */
async function start(kafka, pool, wsService) {
  const consumer = kafka.consumer({ groupId: "pos-transaction-group" });

  await consumer.connect();
  await consumer.subscribe({ topic: TOPICS.TRANSACTION, fromBeginning: false });

  await consumer.run({
    // eachMessage diproses secara sekuensial — satu per satu.
    // Ini intentional untuk transaksi: kita tidak mau dua event yang
    // mengkonsumsi produk sama diproses bersamaan (race condition stok).
    eachMessage: async ({ message }) => {
      let event;

      // ── Parse JSON — jangan crash consumer karena pesan corrupt ───────
      try {
        event = JSON.parse(message.value.toString());
      } catch {
        console.error(
          "[TRX CONSUMER] Pesan tidak valid (bukan JSON), dilewati",
        );
        return; // offset tetap di-commit, pesan corrupt tidak bisa di-retry
      }

      // ── Filter — hanya proses eventType yang relevan ─────────────────
      if (event.eventType !== "transaction.created") {
        return;
      }

      if (!event.transaksiId || !Array.isArray(event.items) || !event.userId) {
        console.error(
          "[TRX CONSUMER] Payload tidak lengkap:",
          JSON.stringify(event),
        );
        return;
      }

      console.log(
        `[TRX CONSUMER] Memproses ${event.eventType} | transaksiId: ${event.transaksiId}`,
      );

      try {
        await processTransaction(event, pool, wsService);
      } catch (err) {
        // Error tidak ter-handle di processTransaction — log tapi tidak re-throw.
        // Re-throw akan menyebabkan consumer crash dan restart dari offset yang sama
        // (pesan yang sama diproses ulang terus — infinite loop).
        console.error(
          `[TRX CONSUMER] Error tidak tertangani untuk transaksi ${event.transaksiId}:`,
          err,
        );
      }
    },
  });

  console.log(`[TRX CONSUMER] Siap konsumsi topic: ${TOPICS.TRANSACTION}`);
  return consumer;
}

module.exports = { start, processTransaction, validateStock };
