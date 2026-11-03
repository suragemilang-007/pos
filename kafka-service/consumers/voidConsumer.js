'use strict';

/**
 * consumers/voidConsumer.js
 *
 * Subscribe ke topic 'transaction-events', group 'pos-void-group'.
 * Hanya memproses event dengan eventType = 'transaction.void.requested'.
 *
 * Tanggung jawab utama:
 * 1. Kembalikan stok semua item ke gudang secara atomik
 * 2. Update status transaksi menjadi 'VOIDED'
 * 3. Update status void_transaksi menjadi 'COMPLETED'
 * 4. Push notifikasi real-time ke kasir yang bersangkutan dan semua terminal
 */

const { TOPICS } = require('../config/kafka');

// ─────────────────────────────────────────────────────────────────────────────
// FUNGSI PENDUKUNG
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Tandai void_transaksi sebagai REJECTED dan push notifikasi error ke kasir.
 *
 * Menggunakan pool (bukan conn) karena conn yang error kemungkinan besar sudah
 * tidak bisa dipakai — koneksi mungkin dalam state yang tidak konsisten setelah
 * ROLLBACK, terutama jika error terjadi di level driver MySQL.
 *
 * @param {import('mysql2/promise').Pool} pool
 * @param {number} voidId
 * @param {number} transaksiId
 * @param {number} userId
 * @param {string} reason
 * @param {object} wsService
 */
async function markRejected(pool, voidId, transaksiId, userId, reason, wsService) {
    try {
        // Catatan: void_transaksi tidak memiliki kolom updated_at (tabel immutable
        // setelah INSERT — hanya status yang berubah via enum state machine)
        await pool.query(
            `UPDATE void_transaksi SET status = 'REJECTED' WHERE id = ?`,
            [voidId]
        );
    } catch (err) {
        console.error(`[VOID CONSUMER] Gagal UPDATE status REJECTED void ${voidId}:`, err.message);
    }

    wsService.push(`user-${userId}`, {
        event:       'void.rejected',
        voidId:      voidId,
        transaksiId: transaksiId,
        status:      'REJECTED',
        message:     reason,
    });

    console.log(`[VOID CONSUMER] Void ${voidId} → REJECTED | ${reason}`);
}

// ─────────────────────────────────────────────────────────────────────────────
// PROSES UTAMA
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Proses satu event 'transaction.void.requested' dari Kafka.
 *
 * @param {object}                           event       - payload dari Kafka
 * @param {import('mysql2/promise').Pool}    pool
 * @param {object}                           wsService   - { push, pushAll }
 */
async function processVoid(event, pool, wsService) {
    const { voidId, transaksiId, items, userId } = event;

    console.log(
        `[VOID CONSUMER] Memproses void ${voidId} | transaksiId: ${transaksiId} | ${items.length} item`
    );

    const conn = await pool.getConnection();

    try {
        // ── Langkah 1: Mulai DB transaction ──────────────────────────────────
        //
        // MENGAPA SEMUA OPERASI DALAM SATU DB TRANSACTION?
        //
        // Void melibatkan minimal N+2 operasi DB:
        //   - N × UPDATE produk     (kembalikan stok tiap item)
        //   - 1 × UPDATE transaksi  (status → 'VOIDED')
        //   - 1 × UPDATE void_transaksi (status → 'COMPLETED')
        //
        // Tanpa atomic transaction, skenario berikut bisa terjadi:
        //   - Stok produk A dan B berhasil dikembalikan
        //   - Stok produk C gagal (misal: deadlock, koneksi terputus)
        //   - Transaksi belum sempat di-update ke 'VOIDED'
        //   → Stok A+B sudah bertambah, tapi transaksi masih 'COMPLETED'
        //   → Kasir bisa re-void transaksi yang sama → stok double-return
        //   → Laporan tidak konsisten (transaksi masih tercatat sebagai penjualan)
        //
        // Dengan BEGIN/COMMIT:
        //   - Semua operasi dijamin all-or-nothing (ACID Atomicity)
        //   - Jika ada yang gagal, ROLLBACK membatalkan semua perubahan
        //   - Database kembali ke state sebelum void dimulai
        //   - Kasir akan melihat notifikasi REJECTED dan bisa mencoba lagi
        //
        await conn.beginTransaction();

        const produkIds = []; // kumpulkan untuk query stok setelah COMMIT

        // ── Langkah 2: Kembalikan stok setiap item ────────────────────────────
        for (const item of items) {
            // Konversi ke satuan dasar — WAJIB sesuai aturan Consumer (CLAUDE.md §3.3)
            // Contoh: 2 karton (isi=96) yang di-void → kembalikan 192 pcs ke stok
            const qtyDasar = item.qty * item.isiSatuan;

            await conn.query(
                `UPDATE produk
                 SET    qty_stok = qty_stok + ?
                 WHERE  id = ?`,
                [qtyDasar, item.produkId]
            );

            produkIds.push(item.produkId);
        }

        // ── Langkah 3: Update status transaksi → VOIDED ───────────────────────
        // Catatan: tabel transaksi tidak memiliki kolom updated_at
        // (const UPDATED_AT = null di Transaksi.php — tabel immutable by design)
        await conn.query(
            `UPDATE transaksi SET status = 'VOIDED' WHERE id = ?`,
            [transaksiId]
        );

        // ── Langkah 4: Update status void_transaksi → COMPLETED ───────────────
        // Catatan: tabel void_transaksi tidak memiliki kolom updated_at
        // (sama dengan transaksi — hanya created_at yang ada)
        await conn.query(
            `UPDATE void_transaksi SET status = 'COMPLETED' WHERE id = ?`,
            [voidId]
        );

        // ── Langkah 5: COMMIT — semua perubahan menjadi permanen ──────────────
        await conn.commit();

        console.log(`[VOID CONSUMER] Void ${voidId} | transaksi ${transaksiId} → VOIDED+COMPLETED`);

        // ── Langkah 6: Ambil stok terbaru setelah COMMIT ──────────────────────
        // Query dilakukan setelah COMMIT (bukan sebelum) agar nilai yang dikirim
        // ke WebSocket mencerminkan state DB yang sudah final — tidak ada lag
        // antara nilai yang ditampilkan kasir dengan nilai aktual di DB.
        //
        // Menggunakan pool (bukan conn) agar koneksi yang sudah direlease
        // dari transaction tidak dipakai untuk SELECT biasa.
        const [stokRows] = await pool.query(
            `SELECT id AS produkId, qty_stok AS stokBaru FROM produk WHERE id IN (?)`,
            [produkIds]
        );

        // ── Langkah 7: Push notifikasi personal ke kasir ─────────────────────
        wsService.push(`user-${userId}`, {
            event:       'void.completed',
            voidId:      voidId,
            transaksiId: transaksiId,
            status:      'COMPLETED',
            message:     'Transaksi berhasil dibatalkan. Stok telah dikembalikan.',
        });

        // ── Langkah 7b: Push ke void-requests agar owner di halaman persetujuan
        // dapat menghapus item dari list — owner subscribe channel ini, bukan user-{kasirId}
        wsService.push('void-requests', {
            event:       'void.completed',
            voidId:      voidId,
            transaksiId: transaksiId,
            status:      'COMPLETED',
        });

        // ── Langkah 8: Broadcast update stok ke semua terminal ────────────────
        // Semua kasir yang aktif akan melihat stok terbaru secara real-time
        // tanpa perlu refresh halaman — ini adalah nilai dari arsitektur event-driven
        wsService.pushAll('stok-global', {
            event:   'stock.updated',
            updates: stokRows.map(row => ({
                produkId: row.produkId,
                stokBaru: row.stokBaru,
            })),
        });

        console.log(
            `[VOID CONSUMER] WS push selesai | user-${userId} + stok-global ` +
            `(${stokRows.length} produk diperbarui)`
        );

    } catch (err) {
        // ── Error di tengah DB transaction → ROLLBACK ─────────────────────────
        console.error(
            `[VOID CONSUMER] Error saat proses void ${voidId}:`, err.message
        );

        try {
            await conn.rollback();
            console.log(`[VOID CONSUMER] Rollback berhasil untuk void ${voidId}`);
        } catch (rollbackErr) {
            console.error(
                `[VOID CONSUMER] Rollback gagal void ${voidId}:`, rollbackErr.message
            );
        }

        // Tandai REJECTED menggunakan pool (bukan conn yang sudah error)
        // dan push notifikasi ke kasir agar bisa tindak lanjut
        await markRejected(
            pool,
            voidId,
            transaksiId,
            userId,
            'Terjadi kesalahan sistem saat memproses void. Silakan hubungi admin.',
            wsService
        );

    } finally {
        // WAJIB release koneksi ke pool — tanpa ini pool akan kehabisan koneksi
        // dan semua consumer lain akan terhenti
        conn.release();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// CONSUMER SETUP
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Inisialisasi dan jalankan Kafka consumer untuk void transaksi.
 *
 * Mengapa group ID terpisah ('pos-void-group') dari transactionConsumer?
 *
 * Kafka group ID menentukan offset tracking — satu group punya satu offset per
 * partition. Jika void dan transaction memakai group ID yang sama:
 *   - Salah satu consumer akan "mencuri" message dari consumer lain
 *   - transactionConsumer bisa mendapat event void.requested dan tidak tahu
 *     cara memprosesnya (hanya filter, offset tetap di-commit → event hilang)
 *   - Atau sebaliknya — voidConsumer mendapat event transaction.created
 *
 * Dengan group ID berbeda:
 *   - Setiap group mendapat salinan independen dari semua message di topic
 *   - transactionConsumer menerima semua event, filter 'transaction.created'
 *   - voidConsumer menerima semua event, filter 'transaction.void.requested'
 *   - Tidak ada message yang "dicuri" antar consumer
 *
 * @param {import('kafkajs').Kafka}            kafka
 * @param {import('mysql2/promise').Pool}      pool
 * @param {object}                             wsService
 * @returns {Promise<import('kafkajs').Consumer>}
 */
async function start(kafka, pool, wsService) {
    const consumer = kafka.consumer({ groupId: 'pos-void-group' });

    await consumer.connect();
    await consumer.subscribe({ topic: TOPICS.TRANSACTION, fromBeginning: false });

    await consumer.run({
        // Diproses sekuensial — satu void per satu.
        // Mencegah dua void untuk transaksi yang sama diproses bersamaan,
        // meski UNIQUE constraint di DB sudah menjamin itu di layer database.
        eachMessage: async ({ message }) => {
            let event;

            // ── Parse JSON — jangan crash consumer karena pesan corrupt ───────
            try {
                event = JSON.parse(message.value.toString());
            } catch {
                console.error('[VOID CONSUMER] Pesan tidak valid (bukan JSON), dilewati');
                return; // commit offset, pesan corrupt tidak bisa di-retry
            }

            // ── Filter — hanya proses event void ────────────────────────────
            if (event.eventType !== 'transaction.void.requested') {
                return; // event lain (misal transaction.created) diabaikan
            }

            // ── Validasi payload minimal ─────────────────────────────────────
            if (!event.voidId || !event.transaksiId || !Array.isArray(event.items) || !event.userId) {
                console.error('[VOID CONSUMER] Payload tidak lengkap:', JSON.stringify(event));
                return;
            }

            console.log(
                `[VOID CONSUMER] Menerima ${event.eventType} | voidId: ${event.voidId}`
            );

            try {
                await processVoid(event, pool, wsService);
            } catch (err) {
                // Error tidak ter-handle di processVoid — log tapi JANGAN re-throw.
                // Re-throw akan crash consumer dan Kafka akan retry message yang sama
                // terus-menerus (infinite loop / poison pill message).
                console.error(
                    `[VOID CONSUMER] Error tidak tertangani void ${event.voidId}:`, err
                );
            }
        },
    });

    console.log(`[VOID CONSUMER] Siap konsumsi topic: ${TOPICS.TRANSACTION} | group: pos-void-group`);
    return consumer;
}

// ─────────────────────────────────────────────────────────────────────────────
// NAMED LIFECYCLE EXPORTS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Referensi consumer yang sedang berjalan — disimpan di module level agar
 * stopVoidConsumer() bisa disconnect tanpa perlu menerima parameter.
 * @type {import('kafkajs').Consumer|null}
 */
let _instance = null;

/**
 * Mulai void consumer dan simpan referensinya untuk graceful shutdown.
 * Dipanggil dari index.js setelah transactionConsumer sudah siap.
 */
async function startVoidConsumer(kafka, pool, wsService) {
    _instance = await start(kafka, pool, wsService);
    return _instance;
}

/**
 * Hentikan void consumer saat shutdown.
 * Tidak perlu parameter — menggunakan referensi module-level _instance.
 */
async function stopVoidConsumer() {
    if (_instance) {
        try {
            await _instance.disconnect();
            console.log('[VOID CONSUMER] Consumer dihentikan');
        } catch (err) {
            console.error('[VOID CONSUMER] Error saat disconnect:', err.message);
        }
        _instance = null;
    }
}

module.exports = { start, startVoidConsumer, stopVoidConsumer, processVoid };
