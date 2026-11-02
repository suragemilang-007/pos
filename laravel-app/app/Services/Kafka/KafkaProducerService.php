<?php

namespace App\Services\Kafka;

use App\Models\DetailTransaksi;
use App\Models\Pembelian;
use App\Models\PenyesuaianStok;
use App\Models\ReturPembelian;
use App\Models\ReturPenjualan;
use App\Models\Transaksi;
use App\Models\VoidTransaksi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * KafkaProducerService — mengirim event ke Node.js kafka-service via HTTP POST.
 *
 * Laravel tidak publish langsung ke Kafka karena:
 * 1. Tidak ada Kafka client native yang production-ready untuk PHP
 * 2. Semua konfigurasi Kafka terpusat di Node.js — tidak tersebar ke dua service
 * 3. Node.js yang bertanggung jawab atas retry, partitioning, dan idempotency
 *
 * Alur: Controller → Service → KafkaProducerService → HTTP POST → Node.js → Kafka
 */
class KafkaProducerService
{
    private string $producerUrl;

    public function __construct()
    {
        // URL diambil dari config agar tidak hardcode — nilai Docker: http://kafka-service:3000
        // JANGAN ganti dengan localhost:3000 — tidak bisa diakses antar container
        $this->producerUrl = config('services.kafka.producer_url');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TRANSAKSI PENJUALAN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Publish event saat transaksi penjualan dibuat (status: PENDING).
     *
     * items harus menyertakan isiSatuan karena Consumer Kafka
     * menggunakannya untuk konversi: qty_stok_berkurang = qty × isiSatuan.
     * Tanpa isiSatuan, Consumer tidak bisa tahu berapa pcs yang harus dikurangi
     * untuk pembelian 2 karton (isi 96 pcs) vs 2 pcs.
     */
    public function publishTransactionCreated(Transaksi $transaksi): bool
    {
        // Muat relasi jika belum di-eager load oleh pemanggil
        $transaksi->loadMissing('details');

        $payload = [
            'eventType'   => 'transaction.created',
            'transaksiId' => $transaksi->id,
            'items'       => $transaksi->details->map(fn ($d) => [
                'produkId'      => $d->produk_id,
                'produkSatuanId'=> $d->produk_satuan_id,
                'namaSatuan'    => $d->nama_satuan,
                'isiSatuan'     => (int) $d->isi_satuan,   // WAJIB — untuk konversi stok
                'qty'           => (int) $d->qty,
                'hargaSatuan'   => (float) $d->harga_satuan,
                'diskonItem'    => (float) $d->diskon_item,
            ])->values()->all(),
            'customerId'  => $transaksi->customer_id,
            'userId'      => $transaksi->user_id,
            'total'       => (float) $transaksi->total,
            'metodeBayar' => $transaksi->metode_bayar,
            'timestamp'   => now()->toIso8601String(),
        ];

        return $this->publish($payload, "transaction.created transaksiId={$transaksi->id}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PEMBELIAN / RESTOCK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Publish event saat Purchase Order dibuat (status: PENDING).
     *
     * Consumer akan TAMBAH qty_stok += qty × isiSatuan dan
     * UPDATE harga_beli_dasar = hargaBeliSatuan / isiSatuan
     * agar margin profit selalu dihitung dari harga beli terkini.
     */
    public function publishPurchaseCreated(Pembelian $pembelian): bool
    {
        $pembelian->loadMissing('details');

        $payload = [
            'eventType'      => 'purchase.created',
            'pembelianId'    => $pembelian->id,
            'supplierId'     => $pembelian->supplier_id,
            'items'          => $pembelian->details->map(fn ($d) => [
                'produkId'        => $d->produk_id,
                'produkSatuanId'  => $d->produk_satuan_id,
                'namaSatuan'      => $d->nama_satuan,
                'isiSatuan'       => (int) $d->isi_satuan,   // WAJIB — konversi stok
                'qty'             => (int) $d->qty,
                'hargaBeliSatuan' => (float) $d->harga_beli_satuan,
            ])->values()->all(),
            'userId'         => $pembelian->user_id,
            // v4: info pembayaran — Consumer INSERT pembayaran_pembelian jika cash/dp>0
            'tipePembayaran' => $pembelian->tipe_pembayaran,
            'jumlahDp'       => (float) $pembelian->jumlah_dp,
            'timestamp'      => now()->toIso8601String(),
        ];

        return $this->publish($payload, "purchase.created pembelianId={$pembelian->id}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RETUR PENJUALAN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Publish event saat retur dari customer diajukan.
     *
     * Consumer akan TAMBAH qty_stok += qtyRetur × isiSatuan
     * (barang kembali dari customer ke gudang).
     * isiSatuan diambil dari snapshot di detail_retur_penjualan,
     * bukan dari produk_satuan — agar retur historis tidak berubah
     * jika satuan produk diedit di kemudian hari.
     */
    public function publishSaleReturnRequested(ReturPenjualan $retur): bool
    {
        $retur->loadMissing('details');

        $payload = [
            'eventType'   => 'sale.return.requested',
            'returId'     => $retur->id,
            'transaksiId' => $retur->transaksi_id,
            'items'       => $retur->details->map(fn ($d) => [
                'produkId'    => $d->produk_id,
                'namaSatuan'  => $d->nama_satuan,
                'isiSatuan'   => (int) $d->isi_satuan,   // WAJIB — konversi stok
                'qtyRetur'    => (int) $d->qty_retur,
                'hargaSatuan' => (float) $d->harga_satuan,
            ])->values()->all(),
            'userId'      => $retur->user_id,
            'timestamp'   => now()->toIso8601String(),
        ];

        return $this->publish($payload, "sale.return.requested returId={$retur->id}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RETUR PEMBELIAN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Publish event saat retur ke supplier diajukan.
     *
     * Consumer akan KURANGI qty_stok -= qtyRetur × isiSatuan
     * (barang keluar dari gudang ke supplier).
     */
    public function publishPurchaseReturnRequested(ReturPembelian $retur): bool
    {
        $retur->loadMissing('details');

        $payload = [
            'eventType'   => 'purchase.return.requested',
            'returId'     => $retur->id,
            'pembelianId' => $retur->pembelian_id,
            'items'       => $retur->details->map(fn ($d) => [
                'produkId'    => $d->produk_id,
                'namaSatuan'  => $d->nama_satuan,
                'isiSatuan'   => (int) $d->isi_satuan,   // WAJIB — konversi stok
                'qtyRetur'    => (int) $d->qty_retur,
                'hargaSatuan' => (float) $d->harga_satuan,
            ])->values()->all(),
            'userId'      => $retur->user_id,
            'timestamp'   => now()->toIso8601String(),
        ];

        return $this->publish($payload, "purchase.return.requested returId={$retur->id}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VOID TRANSAKSI
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Publish event void ke Kafka.
     *
     * KRITIS — hanya item is_bonus=false yang dimasukkan ke payload:
     * - Item bonus sudah keluar secara fisik dari gudang saat transaksi berlangsung
     * - Item bonus TIDAK tercatat sebagai penjualan berbayar (harga_satuan=0)
     * - Jika stok item bonus dikembalikan → stok double-count karena
     *   pengurangan stok saat transaksi asli sudah mengurangi stok bonus juga
     *
     * Query langsung ke detail_transaksi WHERE is_bonus=0 (lebih efisien dari
     * load-all-then-filter di PHP, apalagi transaksi bisa punya banyak item).
     *
     * Consumer (voidConsumer.js) prosesnya:
     *   1. Untuk setiap item payload: qty_stok += qty × isiSatuan
     *   2. UPDATE transaksi SET status='VOIDED'
     *   3. UPDATE void_transaksi SET status='COMPLETED'
     *   4. Push WebSocket void.completed + stock.updated
     */
    public function publishTransactionVoid(VoidTransaksi $void): bool
    {
        // Query langsung WHERE is_bonus=0 — filter di DB bukan di PHP
        $items = DetailTransaksi::where('transaksi_id', $void->transaksi_id)
            ->where('is_bonus', 0)
            ->get()
            ->map(fn ($d) => [
                'produkId'  => $d->produk_id,
                'qty'       => (int) $d->qty,
                'isiSatuan' => (int) $d->isi_satuan,  // WAJIB — Consumer: stok_kembali = qty × isiSatuan
            ])
            ->values()
            ->all();

        $payload = [
            'eventType'   => 'transaction.void.requested',
            'voidId'      => $void->id,
            'transaksiId' => $void->transaksi_id,
            'items'       => $items,
            'userId'      => $void->user_id,
            'timestamp'   => now()->toIso8601String(),
        ];

        return $this->publish($payload, "transaction.void.requested voidId={$void->id}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PENYESUAIAN STOK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Publish event penyesuaian stok ke Kafka (SD-20).
     *
     * MENGAPA VIA KAFKA (bukan UPDATE langsung di Service)?
     *
     * Penyesuaian stok berdampak pada qty_stok — data yang dipantau real-time
     * oleh semua terminal kasir aktif. Jika UPDATE dilakukan sinkron di Laravel:
     *   - Kasir A yang sedang di halaman pantau stok tidak akan tahu stok berubah
     *     sampai mereka refresh manual → risiko transaksi dengan stok stale
     *   - Tidak ada mekanisme broadcast ke WebSocket dari dalam PHP (Laravel tidak
     *     punya koneksi langsung ke WS server di Node.js)
     *
     * Dengan alur Kafka:
     *   1. Laravel INSERT penyesuaian_stok (PENDING) → publish event
     *   2. Consumer UPDATE qty_stok di DB → push WebSocket 'stock.updated'
     *   3. Semua browser yang subscribe 'stok-global' menerima stok terbaru
     *      tanpa perlu refresh → kasir tidak bisa menjual stok yang sudah disesuaikan
     *
     * Berbeda dengan pembayaran hutang yang tidak via Kafka: pembayaran tidak
     * mengubah qty_stok, hanya data keuangan → tidak butuh real-time ke kasir.
     *
     * Consumer (adjustmentConsumer.js) menentukan arah dari field `tipe`:
     *   koreksi_plus                               → qty_stok += qtySatuanDasar
     *   barang_rusak | hilang | expired | koreksi_minus → qty_stok -= qtySatuanDasar
     *
     * qtySatuanDasar selalu positif — arah ditentukan oleh tipe, bukan tanda nilai.
     * Tidak perlu isi_satuan karena qty sudah dalam satuan_dasar (tidak perlu konversi).
     */
    public function publishStockAdjustment(PenyesuaianStok $penyesuaian): bool
    {
        $payload = [
            'eventType'       => 'stock.adjustment.requested',
            'adjustmentId'    => $penyesuaian->id,
            'produkId'        => $penyesuaian->produk_id,
            'qtySatuanDasar'  => (int) $penyesuaian->qty_satuan_dasar,
            'tipe'            => $penyesuaian->tipe,
            'userId'          => $penyesuaian->user_id,
            'timestamp'       => now()->toIso8601String(),
        ];

        return $this->publish(
            $payload,
            "stock.adjustment.requested adjustmentId={$penyesuaian->id}"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WEBSOCKET DIRECT PUSH (non-Kafka)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Push notifikasi langsung ke WebSocket tanpa melalui Kafka.
     *
     * Dipakai untuk operasi sinkron yang tidak mengubah stok — contoh: tolak void.
     * Tidak throw exception jika gagal (best-effort) karena DB sudah terupdate;
     * notifikasi WS hanya convenience, bukan syarat keberhasilan operasi.
     */
    public function pushWebSocket(string $channel, array $payload): void
    {
        try {
            Http::timeout(3)
                ->acceptJson()
                ->post($this->producerUrl . '/push-ws', [
                    'channel' => $channel,
                    'payload' => $payload,
                ]);
        } catch (\Throwable $e) {
            Log::warning('[WS PUSH] Gagal push notifikasi WS', [
                'channel' => $channel,
                'event'   => $payload['event'] ?? '?',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INTERNAL
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kirim payload ke Node.js kafka-service dan tangani response.
     *
     * Timeout 5 detik dipilih karena:
     * - Node.js publish ke Kafka idealnya < 100ms
     * - Jika Kafka sedang lambat (leader election), 5 detik cukup untuk retry internal KafkaJS
     * - Lebih dari 5 detik berarti ada masalah serius — Laravel tidak boleh terblokir lebih lama
     *
     * @throws \RuntimeException jika Node.js tidak response atau response non-2xx
     */
    private function publish(array $payload, string $logContext): bool
    {
        $url = $this->producerUrl . '/publish';

        try {
            Log::info("[KAFKA PUBLISH] Mengirim: {$logContext}", [
                'url'       => $url,
                'eventType' => $payload['eventType'],
            ]);

            $response = Http::timeout(5)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info("[KAFKA PUBLISH] Berhasil: {$logContext}");
                return true;
            }

            // Response non-2xx: Node.js menolak atau ada error di sana
            $body = $response->json('message', $response->body());

            Log::error("[KAFKA PUBLISH] Gagal: {$logContext}", [
                'status'   => $response->status(),
                'response' => $body,
            ]);

            throw new \RuntimeException(
                "Kafka producer menolak event '{$payload['eventType']}' " .
                "(HTTP {$response->status()}): {$body}"
            );

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Node.js tidak bisa dijangkau — kafka-service mungkin belum ready atau crash
            Log::error("[KAFKA PUBLISH] Koneksi gagal: {$logContext}", [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                "Tidak dapat menghubungi Kafka producer service ({$this->producerUrl}): " .
                $e->getMessage()
            );
        }
    }
}
