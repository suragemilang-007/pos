'use strict';

/**
 * consumers/adjustmentConsumer.js
 *
 * Subscribe ke topic 'inventory-updates', group 'pos-adjustment-group'.
 * Hanya memproses event dengan eventType = 'stock.adjustment.requested'.
 *
 * Tanggung jawab utama:
 * 1. Tentukan arah stok berdasarkan field `tipe` di payload
 * 2. UPDATE qty_stok produk secara atomik (TAMBAH atau KURANGI)
 * 3. UPDATE status penyesuaian_stok → 'COMPLETED'
 * 4. Push notifikasi real-time ke admin yang membuat penyesuaian (personal)
 * 5. Broadcast stock.updated ke semua terminal kasir (stok-global)
 *
 * Catatan arah stok:
 *   koreksi_plus                           → qty_stok += qtySatuanDasar
 *   barang_rusak | barang_hilang |
 *   barang_expired | koreksi_minus         → qty_stok -= qtySatuanDasar
 *
 * qtySatuanDasar selalu positif — arah ditentukan oleh tipe, bukan tanda nilai.
 * Backend (PenyesuaianStokService) sudah validasi qty <= stok saat ini untuk
 * tipe kurang, tapi Consumer tetap double-check sebagai last-resort safety net.
 */

const { TOPICS } = require('../config/kafka');

// ─────────────────────────────────────────────────────────────────────────────
// KONSTANTA
// ─────────────────────────────────────────────────────────────────────────────

/** Tipe yang mengurangi stok */
const TIPE_KURANG = new Set([
    'barang_rusak',
    'barang_hilang',
    'barang_expired',
    'koreksi_minus',
]);

// ─────────────────────────────────────────────────────────────────────────────
// FUNGSI PENDUKUNG
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Tandai penyesuaian tetap PENDING (tidak di-reject, bisa dicoba ulang oleh admin)
 * dan push notifikasi error personal ke admin yang bersangkutan.
 *
 * Berbeda dengan voidConsumer yang set REJECTED — penyesuaian_stok tidak punya
 * status REJECTED di ERD. Status PENDING dibiarkan agar admin bisa melihat bahwa
 * event-nya belum diproses dan bisa tindak lanjut.
 *
 * @param {import('mysql2/promise').Pool} pool
 * @param {number} adjustmentId
 * @param {number} produkId
 * @param {number} userId
 * @param {string} reason
 * @param {object} wsService
 */
async function notifyError(pool, adjustmentId, produkId, userId, reason, wsService) {
    // Biarkan status PENDING — tidak di-update karena tidak ada state REJECTED
    // Admin dapat melihat status PENDING di tabel dan menghubungi developer

    wsService.push(`user-${userId}`, {
        event:        'adjustment.failed',
        adjustmentId: adjustmentId,
        produkId:     produkId,
        status:       'PENDING',
        message:      reason,
    });

    console.log(`[ADJ CONSUMER] Adjustment ${adjustmentId} gagal diproses | ${reason}`);
}

// ─────────────────────────────────────────────────────────────────────────────
// PROSES UTAMA
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Proses satu event 'stock.adjustment.requested' dari Kafka.
 *
 * @param {object}                           event       - payload dari Kafka
 * @param {import('mysql2/promise').Pool}    pool
 * @param {object}                           wsService   - { push, pushAll }
 */
async function processAdjustment(event, pool, wsService) {
    const { adjustmentId, produkId, qtySatuanDasar, tipe, userId } = event;
    const isTipeKurang = TIPE_KURANG.has(tipe);

    console.log(
        `[ADJ CONSUMER] Memproses adjustment ${adjustmentId} | ` +
        `produkId: ${produkId} | tipe: ${tipe} | qty: ${qtySatuanDasar} ` +
        `| arah: ${isTipeKurang ? 'KURANG' : 'TAMBAH'}`
    );

    const conn = await pool.getConnection();

    try {
        // ── Langkah 1: Mulai DB transaction ──────────────────────────────────
        //
        // Dua operasi dalam satu atomic transaction:
        //   - UPDATE qty_stok di tabel produk
        //   - UPDATE status di tabel penyesuaian_stok
        //
        // Tanpa atomicity, jika stok berhasil berubah tapi status tidak
        // ter-update → admin akan mengira penyesuaian masih PENDING padahal
        // stok sudah berubah → duplikasi penyesuaian berisiko.
        //
        await conn.beginTransaction();

        // ── Langkah 2: Double-check stok untuk tipe kurang ───────────────────
        //
        // MENGAPA CONSUMER VERIFIKASI ULANG? (Backend + Consumer = double protection)
        //
        // Layer 1 — Backend (PenyesuaianStokService::create()):
        //   Validasi qty <= qty_stok saat request masuk. Ini menjaga UX:
        //   admin langsung mendapat pesan error yang jelas, bukan menunggu Consumer.
        //
        // Layer 2 — Consumer (di sini, dalam DB transaction):
        //   Ada jeda waktu antara validasi Backend dan eksekusi Consumer.
        //   Skenario race condition yang mungkin terjadi tanpa layer ini:
        //
        //   T=0  Admin A: Backend cek stok=5, qty=3. Lolos → INSERT PENDING #101
        //   T=1  Admin B: Backend cek stok=5, qty=4. Lolos → INSERT PENDING #102
        //   T=2  Consumer proses #101: kurangi 3 → stok=2
        //   T=3  Consumer proses #102: kurangi 4 dari stok=2 → stok NEGATIF (-2)!
        //
        //   Dengan SELECT FOR UPDATE di dalam transaction:
        //   T=3  Consumer proses #102: ambil stok=2 (locked), 2 < 4 → ROLLBACK
        //        → stok tidak pernah negatif, status #102 tetap PENDING
        //        → Admin B mendapat notifikasi WS 'adjustment.failed'
        //
        //   FOR UPDATE menciptakan row-level lock sehingga dua Consumer
        //   tidak bisa membaca dan memodifikasi baris produk yang sama sekaligus.
        if (isTipeKurang) {
            const [[produk]] = await conn.query(
                `SELECT qty_stok FROM produk WHERE id = ? FOR UPDATE`,
                [produkId]
            );

            if (!produk) {
                throw new Error(`Produk id=${produkId} tidak ditemukan`);
            }

            if (produk.qty_stok < qtySatuanDasar) {
                throw new Error(
                    `Stok tidak cukup: tersedia ${produk.qty_stok}, diminta ${qtySatuanDasar}`
                );
            }
        }

        // ── Langkah 3: UPDATE qty_stok ────────────────────────────────────────
        if (isTipeKurang) {
            await conn.query(
                `UPDATE produk
                 SET    qty_stok = qty_stok - ?
                 WHERE  id = ?`,
                [qtySatuanDasar, produkId]
            );
        } else {
            // koreksi_plus
            await conn.query(
                `UPDATE produk
                 SET    qty_stok = qty_stok + ?
                 WHERE  id = ?`,
                [qtySatuanDasar, produkId]
            );
        }

        // ── Langkah 4: UPDATE status penyesuaian_stok → COMPLETED ────────────
        // penyesuaian_stok tidak memiliki kolom updated_at (const UPDATED_AT = null
        // di PenyesuaianStok.php — tabel append-only, hanya status yang berubah)
        await conn.query(
            `UPDATE penyesuaian_stok SET status = 'COMPLETED' WHERE id = ?`,
            [adjustmentId]
        );

        // ── Langkah 5: COMMIT ─────────────────────────────────────────────────
        await conn.commit();

        console.log(
            `[ADJ CONSUMER] Adjustment ${adjustmentId} → COMPLETED | ` +
            `produkId ${produkId} qty_stok ${isTipeKurang ? '-' : '+'}${qtySatuanDasar}`
        );

        // ── Langkah 6: Ambil stok terbaru setelah COMMIT ──────────────────────
        // Menggunakan pool (bukan conn yang baru saja commit) untuk SELECT —
        // koneksi pool mendapat snapshot terbaru dari InnoDB (read-committed).
        const [[stokRow]] = await pool.query(
            `SELECT qty_stok AS stokBaru FROM produk WHERE id = ?`,
            [produkId]
        );

        const stokBaru = stokRow ? stokRow.stokBaru : null;

        // ── Langkah 7: Push notifikasi personal ke admin ──────────────────────
        wsService.push(`user-${userId}`, {
            event:        'adjustment.completed',
            adjustmentId: adjustmentId,
            tipe:         tipe,
            produkId:     produkId,
            stokBaru:     stokBaru,
            message:      'Penyesuaian stok berhasil diproses',
        });

        // ── Langkah 8: Broadcast update stok ke semua terminal kasir ──────────
        // Kasir perlu tahu stok terbaru agar form transaksi akurat
        wsService.pushAll('stok-global', {
            event:   'stock.updated',
            updates: [{ produkId, stokBaru }],
        });

        console.log(
            `[ADJ CONSUMER] WS push selesai | user-${userId} + stok-global ` +
            `| stokBaru: ${stokBaru}`
        );

    } catch (err) {
        // ── Error di tengah DB transaction → ROLLBACK ─────────────────────────
        console.error(
            `[ADJ CONSUMER] Error saat proses adjustment ${adjustmentId}:`, err.message
        );

        try {
            await conn.rollback();
            console.log(`[ADJ CONSUMER] Rollback berhasil untuk adjustment ${adjustmentId}`);
        } catch (rollbackErr) {
            console.error(
                `[ADJ CONSUMER] Rollback gagal adjustment ${adjustmentId}:`, rollbackErr.message
            );
        }

        // Push notifikasi error ke admin (status tetap PENDING di DB)
        await notifyError(
            pool,
            adjustmentId,
            produkId,
            userId,
            err.message.includes('Stok tidak cukup')
                ? err.message
                : 'Terjadi kesalahan sistem saat memproses penyesuaian stok. Silakan hubungi admin.',
            wsService
        );

    } finally {
        // WAJIB release koneksi ke pool
        conn.release();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// CONSUMER SETUP
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Inisialisasi dan jalankan Kafka consumer untuk penyesuaian stok.
 *
 * Group ID 'pos-adjustment-group' terpisah dari consumer inventory lain
 * (purchase=pos-inventory-group, return=pos-return-group) agar:
 * - Setiap group mendapat salinan independen semua message di inventory-updates topic
 * - adjustmentConsumer tidak "mencuri" event purchase.created dari purchaseConsumer
 * - Offset tracking per group — tidak saling terganggu
 *
 * @param {import('kafkajs').Kafka}            kafka
 * @param {import('mysql2/promise').Pool}      pool
 * @param {object}                             wsService
 * @returns {Promise<import('kafkajs').Consumer>}
 */
async function start(kafka, pool, wsService) {
    const consumer = kafka.consumer({ groupId: 'pos-adjustment-group' });

    await consumer.connect();
    await consumer.subscribe({ topic: TOPICS.INVENTORY, fromBeginning: false });

    await consumer.run({
        // Diproses sekuensial — satu penyesuaian per satu.
        // Mencegah dua adjustment pada produk yang sama berjalan paralel
        // yang bisa menyebabkan race condition pada qty_stok.
        eachMessage: async ({ message }) => {
            let event;

            // ── Parse JSON ────────────────────────────────────────────────────
            try {
                event = JSON.parse(message.value.toString());
            } catch {
                console.error('[ADJ CONSUMER] Pesan tidak valid (bukan JSON), dilewati');
                return;
            }

            // ── Filter — hanya proses stock.adjustment.requested ──────────────
            if (event.eventType !== 'stock.adjustment.requested') {
                return;
            }

            // ── Validasi payload minimal ──────────────────────────────────────
            if (!event.adjustmentId || !event.produkId || !event.qtySatuanDasar || !event.tipe || !event.userId) {
                console.error('[ADJ CONSUMER] Payload tidak lengkap:', JSON.stringify(event));
                return;
            }

            console.log(
                `[ADJ CONSUMER] Menerima ${event.eventType} | adjustmentId: ${event.adjustmentId}`
            );

            try {
                await processAdjustment(event, pool, wsService);
            } catch (err) {
                // JANGAN re-throw — akan crash consumer dan Kafka retry infinitely
                console.error(
                    `[ADJ CONSUMER] Error tidak tertangani adjustment ${event.adjustmentId}:`, err
                );
            }
        },
    });

    console.log(
        `[ADJ CONSUMER] Siap konsumsi topic: ${TOPICS.INVENTORY} | group: pos-adjustment-group`
    );
    return consumer;
}

// ─────────────────────────────────────────────────────────────────────────────
// NAMED LIFECYCLE EXPORTS
// ─────────────────────────────────────────────────────────────────────────────

/** @type {import('kafkajs').Consumer|null} */
let _instance = null;

/**
 * Mulai adjustment consumer dan simpan referensinya untuk graceful shutdown.
 */
async function startAdjustmentConsumer(kafka, pool, wsService) {
    _instance = await start(kafka, pool, wsService);
    return _instance;
}

/**
 * Hentikan adjustment consumer saat shutdown.
 */
async function stopAdjustmentConsumer() {
    if (_instance) {
        try {
            await _instance.disconnect();
            console.log('[ADJ CONSUMER] Consumer dihentikan');
        } catch (err) {
            console.error('[ADJ CONSUMER] Error saat disconnect:', err.message);
        }
        _instance = null;
    }
}

module.exports = { start, startAdjustmentConsumer, stopAdjustmentConsumer, processAdjustment };
