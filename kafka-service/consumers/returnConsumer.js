'use strict';

/**
 * consumers/returnConsumer.js
 *
 * Subscribe ke topic 'inventory-updates', group 'pos-return-group'.
 * Menangani DUA tipe event berbeda dengan arah stok berlawanan:
 *
 *   sale.return.requested     → stok BERTAMBAH (barang kembali dari customer ke toko)
 *   purchase.return.requested → stok BERKURANG (barang keluar dari toko ke supplier)
 *
 * Kedua event ada di topic yang sama (inventory-updates) karena sama-sama
 * mempengaruhi stok — dipisah dari transaction-events agar consumer group
 * bisa di-scale secara independen.
 */

const { TOPICS } = require('../config/kafka');

const HANDLED_EVENTS = new Set([
    'sale.return.requested',
    'purchase.return.requested',
]);

// ─────────────────────────────────────────────────────────────────────────────
// RETUR PENJUALAN — stok BERTAMBAH
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Proses retur dari customer ke toko.
 * Barang dikembalikan → stok naik, refund dihitung.
 *
 * @param {object}                          event
 * @param {import('mysql2/promise').Pool}   pool
 * @param {object}                          wsService
 */
async function processSaleReturn(event, pool, wsService) {
    const { returId, items, userId } = event;

    const conn = await pool.getConnection();

    try {
        await conn.beginTransaction();

        let totalRefund   = 0;
        const stokUpdates = [];

        for (const item of items) {
            // Konversi ke satuan dasar — KRITIS
            // Contoh: retur 1 renteng kopi (isiSatuan=10) → tambah 10 pcs ke stok
            const qtyDasar = item.qtyRetur * (item.isiSatuan || 1);

            await conn.query(
                `UPDATE produk
                 SET    qty_stok = qty_stok + ?
                 WHERE  id = ?`,
                [qtyDasar, item.produkId]
            );

            // subtotal_refund = hargaSatuan × qtyRetur (snapshot dari detail_transaksi)
            totalRefund += item.hargaSatuan * item.qtyRetur;

            console.log(
                `[RETUR CONSUMER] sale retur produk ${item.produkId}: +${qtyDasar} stok ` +
                `(${item.qtyRetur} × ${item.isiSatuan})`
            );

            stokUpdates.push({ produkId: item.produkId, tambahan: qtyDasar });
        }

        // Update status dan total_refund retur penjualan
        await conn.query(
            `UPDATE retur_penjualan
             SET    status       = 'COMPLETED',
                    total_refund = ?
             WHERE  id = ?`,
            [totalRefund, returId]
        );

        await conn.commit();

        console.log(`[RETUR CONSUMER] Retur penjualan ${returId} → COMPLETED | refund: ${totalRefund}`);

        wsService.push(`user-${userId}`, {
            event:       'return.sale.completed',
            returId:     returId,
            status:      'COMPLETED',
            nilaiRefund: totalRefund,
            message:     `Retur berhasil. Refund: Rp ${totalRefund.toLocaleString('id-ID')}`,
        });

        wsService.pushAll('stok-global', {
            event:   'stock.updated',
            updates: stokUpdates,
        });

    } catch (err) {
        console.error(`[RETUR CONSUMER] Error proses retur penjualan ${returId}:`, err.message);

        try {
            await conn.rollback();
        } catch (rbErr) {
            console.error(`[RETUR CONSUMER] Rollback gagal:`, rbErr.message);
        }

        try {
            await pool.query(
                `UPDATE retur_penjualan SET status = 'REJECTED' WHERE id = ?`,
                [returId]
            );
        } catch {}

        wsService.push(`user-${userId}`, {
            event:   'return.sale.failed',
            returId: returId,
            status:  'REJECTED',
            message: 'Terjadi kesalahan sistem saat memproses retur penjualan',
        });

    } finally {
        conn.release();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// RETUR PEMBELIAN — stok BERKURANG
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Proses retur dari toko ke supplier.
 * Barang keluar → stok turun, kredit ke supplier dihitung.
 *
 * @param {object}                          event
 * @param {import('mysql2/promise').Pool}   pool
 * @param {object}                          wsService
 */
async function processPurchaseReturn(event, pool, wsService) {
    const { returId, items, userId } = event;

    const conn = await pool.getConnection();

    try {
        await conn.beginTransaction();

        let totalKredit   = 0;
        const stokUpdates = [];

        for (const item of items) {
            // Konversi ke satuan dasar — KRITIS
            // Contoh: retur 1 karton ke supplier (isiSatuan=96) → kurangi 96 pcs dari stok
            const qtyDasar = item.qtyRetur * (item.isiSatuan || 1);

            await conn.query(
                `UPDATE produk
                 SET    qty_stok = qty_stok - ?
                 WHERE  id = ?`,
                [qtyDasar, item.produkId]
            );

            // total_kredit = hargaSatuan × qtyRetur (snapshot dari detail_pembelian)
            totalKredit += item.hargaSatuan * item.qtyRetur;

            console.log(
                `[RETUR CONSUMER] purchase retur produk ${item.produkId}: -${qtyDasar} stok ` +
                `(${item.qtyRetur} × ${item.isiSatuan})`
            );

            stokUpdates.push({ produkId: item.produkId, pengurangan: qtyDasar });
        }

        await conn.query(
            `UPDATE retur_pembelian
             SET    status       = 'COMPLETED',
                    total_kredit = ?
             WHERE  id = ?`,
            [totalKredit, returId]
        );

        await conn.commit();

        console.log(`[RETUR CONSUMER] Retur pembelian ${returId} → COMPLETED | kredit: ${totalKredit}`);

        wsService.push(`user-${userId}`, {
            event:       'return.purchase.completed',
            returId:     returId,
            status:      'COMPLETED',
            nilaiKredit: totalKredit,
            message:     `Retur pembelian berhasil. Kredit: Rp ${totalKredit.toLocaleString('id-ID')}`,
        });

        wsService.pushAll('stok-global', {
            event:   'stock.updated',
            updates: stokUpdates,
        });

    } catch (err) {
        console.error(`[RETUR CONSUMER] Error proses retur pembelian ${returId}:`, err.message);

        try {
            await conn.rollback();
        } catch (rbErr) {
            console.error(`[RETUR CONSUMER] Rollback gagal:`, rbErr.message);
        }

        try {
            await pool.query(
                `UPDATE retur_pembelian SET status = 'REJECTED' WHERE id = ?`,
                [returId]
            );
        } catch {}

        wsService.push(`user-${userId}`, {
            event:   'return.purchase.failed',
            returId: returId,
            status:  'REJECTED',
            message: 'Terjadi kesalahan sistem saat memproses retur pembelian',
        });

    } finally {
        conn.release();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// CONSUMER SETUP
// ─────────────────────────────────────────────────────────────────────────────

async function start(kafka, pool, wsService) {
    const consumer = kafka.consumer({ groupId: 'pos-return-group' });

    await consumer.connect();
    await consumer.subscribe({ topic: TOPICS.INVENTORY, fromBeginning: false });

    await consumer.run({
        eachMessage: async ({ message }) => {
            let event;
            try {
                event = JSON.parse(message.value.toString());
            } catch {
                console.error('[RETUR CONSUMER] Pesan tidak valid (bukan JSON), dilewati');
                return;
            }

            if (!HANDLED_EVENTS.has(event.eventType)) return;

            if (!event.returId || !Array.isArray(event.items) || !event.userId) {
                console.error('[RETUR CONSUMER] Payload tidak lengkap:', JSON.stringify(event));
                return;
            }

            console.log(`[RETUR CONSUMER] Memproses ${event.eventType} | returId: ${event.returId}`);

            try {
                if (event.eventType === 'sale.return.requested') {
                    await processSaleReturn(event, pool, wsService);
                } else {
                    await processPurchaseReturn(event, pool, wsService);
                }
            } catch (err) {
                console.error(`[RETUR CONSUMER] Error tidak tertangani retur ${event.returId}:`, err);
            }
        },
    });

    console.log(`[RETUR CONSUMER] Siap konsumsi topic: ${TOPICS.INVENTORY}`);
    return consumer;
}

module.exports = { start, processSaleReturn, processPurchaseReturn };