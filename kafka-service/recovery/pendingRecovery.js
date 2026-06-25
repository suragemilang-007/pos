'use strict';

/**
 * recovery/pendingRecovery.js
 *
 * Dijalankan sekali saat startup kafka-service untuk memulihkan transaksi
 * PENDING yang event-nya tidak pernah sampai ke Kafka.
 *
 * KAPAN INI TERJADI?
 * Saat pos_kafka_service (Node.js) mati, Laravel tetap bisa INSERT transaksi ke DB
 * (karena DB commit sebelum publish). Tapi HTTP POST ke kafka-service:3000 gagal →
 * event tidak pernah dikirim ke Kafka broker → Consumer tidak pernah tahu ada
 * transaksi baru → transaksi tetap PENDING selamanya.
 *
 * CARA KERJA:
 * 1. Query semua transaksi PENDING yang berumur > THRESHOLD_SECONDS (hindari
 *    transaksi yang baru saja dibuat dan sedang dalam alur normal)
 * 2. Untuk setiap transaksi, ambil detail item dari detail_transaksi
 * 3. Build ulang payload sesuai format 'transaction.created'
 * 4. Publish ke Kafka — Consumer akan memprosesnya seolah-olah event baru
 *
 * IDEMPOTENCY:
 * transactionConsumer.js menambahkan cek status di awal processTransaction():
 * jika status bukan PENDING lagi, event di-skip → aman dari double-processing.
 */

const { TOPICS } = require('../config/kafka');

const THRESHOLD_SECONDS = 30;

/**
 * @param {import('kafkajs').Producer}        producer
 * @param {import('mysql2/promise').Pool}     pool
 */
async function recoverPendingTransactions(producer, pool) {
    let transaksiRows;

    try {
        [transaksiRows] = await pool.query(`
            SELECT
                t.id,
                t.user_id,
                t.customer_id,
                t.total,
                t.metode_bayar,
                t.created_at
            FROM transaksi t
            WHERE t.status = 'PENDING'
              AND t.created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
            ORDER BY t.created_at ASC
        `, [THRESHOLD_SECONDS]);
    } catch (err) {
        console.error('[RECOVERY] Gagal query transaksi PENDING:', err.message);
        return;
    }

    if (transaksiRows.length === 0) {
        console.log('[RECOVERY] Tidak ada transaksi PENDING yang perlu dipulihkan');
        return;
    }

    console.log(`[RECOVERY] Ditemukan ${transaksiRows.length} transaksi PENDING — memulihkan...`);

    let berhasil = 0;
    let gagal    = 0;

    for (const trx of transaksiRows) {
        try {
            const [detailRows] = await pool.query(`
                SELECT
                    produk_id,
                    produk_satuan_id,
                    nama_satuan,
                    isi_satuan,
                    qty,
                    harga_satuan,
                    diskon_item
                FROM detail_transaksi
                WHERE transaksi_id = ?
            `, [trx.id]);

            if (detailRows.length === 0) {
                console.warn(`[RECOVERY] Transaksi ${trx.id} tidak punya detail item — dilewati`);
                gagal++;
                continue;
            }

            const payload = {
                eventType:   'transaction.created',
                transaksiId: trx.id,
                items: detailRows.map(d => ({
                    produkId:       d.produk_id,
                    produkSatuanId: d.produk_satuan_id,
                    namaSatuan:     d.nama_satuan,
                    isiSatuan:      d.isi_satuan,
                    qty:            d.qty,
                    hargaSatuan:    parseFloat(d.harga_satuan),
                    diskonItem:     parseFloat(d.diskon_item || 0),
                })),
                customerId:  trx.customer_id,
                userId:      trx.user_id,
                total:       parseFloat(trx.total),
                metodeBayar: trx.metode_bayar,
                timestamp:   new Date().toISOString(),
                _recovered:  true,  // penanda untuk log, tidak dipakai Consumer
            };

            // message key = transaksiId → memastikan event untuk transaksi yang sama
            // masuk ke partisi Kafka yang sama → urutan terjaga
            await producer.send({
                topic:    TOPICS.TRANSACTION,
                messages: [{ key: String(trx.id), value: JSON.stringify(payload) }],
            });

            berhasil++;
            console.log(`[RECOVERY] Transaksi ${trx.id} berhasil di-publish ulang ke Kafka`);

        } catch (err) {
            gagal++;
            console.error(`[RECOVERY] Gagal publish ulang transaksi ${trx.id}: ${err.message}`);
        }
    }

    console.log(`[RECOVERY] Selesai — berhasil: ${berhasil}, gagal: ${gagal}`);
}

module.exports = { recoverPendingTransactions };
