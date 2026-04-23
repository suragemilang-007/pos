'use strict';

/**
 * consumers/purchaseConsumer.js
 *
 * Subscribe ke topic 'inventory-updates', group 'pos-inventory-group'.
 * Filter: hanya proses eventType === 'purchase.created'.
 *
 * Tanggung jawab:
 * 1. TAMBAH qty_stok setiap produk dalam PO (qty × isiSatuan)
 * 2. UPDATE harga_beli_dasar produk ke harga terbaru per satuan_dasar
 * 3. UPDATE status pembelian → COMPLETED
 * 4. Push notifikasi ke admin gudang dan broadcast stok ke semua terminal
 */

const { TOPICS } = require('../config/kafka');

// ─────────────────────────────────────────────────────────────────────────────
// PROSES UTAMA
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Proses satu event 'purchase.created'.
 *
 * Mengapa ada UPDATE harga_beli_dasar?
 * harga_beli_dasar di tabel produk menyimpan harga beli per satuan_dasar.
 * Supplier bisa berubah harga setiap PO. Consumer ini mengupdate harga_beli_dasar
 * agar laporan profit margin selalu menggunakan harga beli terkini.
 * Formula: harga_beli_dasar = hargaBeliSatuan / isiSatuan
 *
 * @param {object}                          event
 * @param {import('mysql2/promise').Pool}   pool
 * @param {object}                          wsService
 */
async function processPurchase(event, pool, wsService) {
    const { pembelianId, items, userId } = event;

    const conn = await pool.getConnection();

    try {
        await conn.beginTransaction();

        const stokUpdates = [];

        for (const item of items) {
            // Konversi ke satuan dasar — KRITIS
            // Contoh: beli 5 karton kopi (isiSatuan=96) → tambah 480 pcs ke stok
            const qtyDasar = item.qty * (item.isiSatuan || 1);

            // Harga beli per satuan_dasar — untuk update harga_beli_dasar produk
            // Contoh: karton Rp 252.000 / 96 pcs = Rp 2.625/pcs
            const hargaBeliPerDasar = item.hargaBeliSatuan / (item.isiSatuan || 1);

            await conn.query(
                `UPDATE produk
                 SET    qty_stok         = qty_stok + ?,
                        harga_beli_dasar = ?
                 WHERE  id = ?`,
                [qtyDasar, hargaBeliPerDasar, item.produkId]
            );

            console.log(
                `[PO CONSUMER] produk ${item.produkId}: +${qtyDasar} stok ` +
                `(${item.qty} × ${item.isiSatuan}) | harga_beli_dasar → ${hargaBeliPerDasar}`
            );

            // Stok baru tidak diketahui tanpa SELECT — gunakan sentinel -1 sebagai sinyal
            // frontend untuk fetch ulang jika perlu nilai pasti
            stokUpdates.push({ produkId: item.produkId, tambahan: qtyDasar });
        }

        await conn.query(
            `UPDATE pembelian SET status = 'COMPLETED' WHERE id = ?`,
            [pembelianId]
        );

        await conn.commit();

        console.log(`[PO CONSUMER] Pembelian ${pembelianId} → COMPLETED`);

        // Push notifikasi personal ke admin gudang yang membuat PO
        wsService.push(`user-${userId}`, {
            event:       'purchase.completed',
            pembelianId: pembelianId,
            status:      'COMPLETED',
            message:     'Restock berhasil. Stok sudah diperbarui.',
        });

        // Broadcast ke semua terminal — stokBaru tidak diketahui tanpa SELECT tambahan,
        // broadcast tetap dikirim agar terminal lain tahu ada perubahan dan bisa
        // request ulang data stok terbaru
        wsService.pushAll('stok-global', {
            event:   'stock.updated',
            updates: stokUpdates,
        });

    } catch (err) {
        console.error(`[PO CONSUMER] Error proses pembelian ${pembelianId}:`, err.message);

        try {
            await conn.rollback();
        } catch (rbErr) {
            console.error(`[PO CONSUMER] Rollback gagal:`, rbErr.message);
        }

        // Tandai CANCELLED agar owner bisa tindak lanjut
        try {
            await pool.query(
                `UPDATE pembelian SET status = 'CANCELLED' WHERE id = ?`,
                [pembelianId]
            );
        } catch (upErr) {
            console.error(`[PO CONSUMER] Gagal UPDATE CANCELLED:`, upErr.message);
        }

        wsService.push(`user-${userId}`, {
            event:       'purchase.failed',
            pembelianId: pembelianId,
            status:      'CANCELLED',
            message:     'Terjadi kesalahan sistem saat memproses restock',
        });

    } finally {
        conn.release();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// CONSUMER SETUP
// ─────────────────────────────────────────────────────────────────────────────

async function start(kafka, pool, wsService) {
    const consumer = kafka.consumer({ groupId: 'pos-inventory-group' });

    await consumer.connect();
    await consumer.subscribe({ topic: TOPICS.INVENTORY, fromBeginning: false });

    await consumer.run({
        eachMessage: async ({ message }) => {
            let event;
            try {
                event = JSON.parse(message.value.toString());
            } catch {
                console.error('[PO CONSUMER] Pesan tidak valid (bukan JSON), dilewati');
                return;
            }

            if (event.eventType !== 'purchase.created') return;

            if (!event.pembelianId || !Array.isArray(event.items) || !event.userId) {
                console.error('[PO CONSUMER] Payload tidak lengkap:', JSON.stringify(event));
                return;
            }

            console.log(`[PO CONSUMER] Memproses ${event.eventType} | pembelianId: ${event.pembelianId}`);

            try {
                await processPurchase(event, pool, wsService);
            } catch (err) {
                console.error(`[PO CONSUMER] Error tidak tertangani pembelian ${event.pembelianId}:`, err);
            }
        },
    });

    console.log(`[PO CONSUMER] Siap konsumsi topic: ${TOPICS.INVENTORY}`);
    return consumer;
}

module.exports = { start, processPurchase };