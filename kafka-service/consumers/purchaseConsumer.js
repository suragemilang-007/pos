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
 * 4. INSERT pembayaran_pembelian (cash/dp) jika ada — dalam transaction yang sama
 * 5. Push notifikasi ke admin gudang dan broadcast stok ke semua terminal
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
 * Mengapa pembayaran diproses di Consumer (bukan di Laravel)?
 * INSERT pembayaran_pembelian harus atomic bersama UPDATE stok dan COMPLETED —
 * jika Laravel langsung INSERT tapi Consumer gagal update stok, data inkonsisten:
 * hutang tercatat lunas tapi stok belum bertambah.
 * Dengan satu DB transaction di Consumer, keduanya berhasil atau keduanya rollback.
 *
 * @param {object}                          event
 * @param {import('mysql2/promise').Pool}   pool
 * @param {object}                          wsService
 */
async function processPurchase(event, pool, wsService) {
    const { pembelianId, items, userId, tipePembayaran, jumlahDp } = event;

    const conn = await pool.getConnection();

    try {
        await conn.beginTransaction();

        const produkIds = [];   // kumpulkan untuk SELECT stok terbaru setelah COMMIT

        // ── Langkah 1-2: Update stok dan harga beli per produk ───────────────
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

            produkIds.push(item.produkId);
        }

        // ── Langkah 3: UPDATE pembelian → COMPLETED ──────────────────────────
        await conn.query(
            `UPDATE pembelian SET status = 'COMPLETED' WHERE id = ?`,
            [pembelianId]
        );

        // ── Langkah 4: INSERT pembayaran_pembelian (dalam transaction yang sama) ──
        //
        // Ambil total dari DB — bukan dari event payload — agar nilai yang
        // digunakan pasti konsisten dengan yang tersimpan di tabel pembelian.
        // Payload bisa saja tidak menyertakan total jika producer berubah.
        const [poRows] = await conn.execute(
            `SELECT total, sisa_hutang, tanggal_jatuh_tempo FROM pembelian WHERE id = ?`,
            [pembelianId]
        );
        const total            = poRows[0].total;
        const sisaHutang       = poRows[0].sisa_hutang;
        const tanggalJatuhTempo = poRows[0].tanggal_jatuh_tempo;

        if (tipePembayaran === 'cash') {
            // Cash: PO lunas saat diterima → INSERT pembayaran sebesar total
            await conn.execute(
                `INSERT INTO pembayaran_pembelian
                 (pembelian_id, user_id, jumlah, metode, tanggal_bayar, created_at)
                 VALUES (?, ?, ?, 'cash', CURDATE(), NOW())`,
                [pembelianId, userId, total]
            );
            console.log(`[PO CONSUMER] Pembayaran cash Rp${total} → pembelian ${pembelianId}`);

        } else if (tipePembayaran === 'kredit' && jumlahDp > 0) {
            // Kredit dengan DP: INSERT hanya sebesar DP — sisa dibayar nanti
            // via endpoint POST /api/gudang/pembelian/{id}/bayar (sinkron, bukan Kafka)
            await conn.execute(
                `INSERT INTO pembayaran_pembelian
                 (pembelian_id, user_id, jumlah, metode, tanggal_bayar, created_at)
                 VALUES (?, ?, ?, 'cash', CURDATE(), NOW())`,
                [pembelianId, userId, jumlahDp]
            );
            console.log(`[PO CONSUMER] DP kredit Rp${jumlahDp} → pembelian ${pembelianId} | sisa Rp${sisaHutang}`);

        } else {
            // Kredit dp=0: tidak ada pembayaran awal — SKIP INSERT
            // Hutang penuh akan dibayar via endpoint bayar hutang
            console.log(`[PO CONSUMER] Kredit tanpa DP → pembelian ${pembelianId} | hutang Rp${sisaHutang}`);
        }

        // ── Langkah 5: COMMIT semua — stok, status, pembayaran atomik ─────────
        await conn.commit();

        console.log(
            `[PO CONSUMER] Pembelian ${pembelianId} → COMPLETED ` +
            `| tipe: ${tipePembayaran ?? 'N/A'} | sisa_hutang: ${sisaHutang}`
        );

        // ── Push notifikasi personal ke admin gudang ──────────────────────────
        wsService.push(`user-${userId}`, {
            event:              'purchase.completed',
            pembelianId:        pembelianId,
            status:             'COMPLETED',
            tipePembayaran:     tipePembayaran ?? null,
            sisaHutang:         sisaHutang     ?? 0,
            tanggalJatuhTempo:  tanggalJatuhTempo ?? null,
            message:            tipePembayaran === 'cash'
                ? 'Restock berhasil. Pembayaran cash tercatat.'
                : `Restock berhasil. Sisa hutang: Rp${sisaHutang}.`,
        });

        // ── Ambil stok terbaru setelah COMMIT lalu broadcast ke semua terminal ──
        //
        // Query menggunakan pool (bukan conn yang sudah commit) agar mendapat
        // snapshot terbaru dari InnoDB — sama dengan pattern di voidConsumer.js
        // dan adjustmentConsumer.js.
        //
        // Pantau Stok (stok.blade.php) mengharapkan { produkId, stokBaru } —
        // nilai ABSOLUT, bukan delta. Tanpa SELECT ini, stokBaru === undefined
        // dan view akan set qty_stok = undefined → tampil 0.
        const [stokRows] = await pool.query(
            `SELECT id AS produkId, qty_stok AS stokBaru FROM produk WHERE id IN (?)`,
            [produkIds]
        );

        wsService.pushAll('stok-global', {
            event:   'stock.updated',
            updates: stokRows.map(r => ({ produkId: r.produkId, stokBaru: r.stokBaru })),
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