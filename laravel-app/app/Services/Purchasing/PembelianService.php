<?php

namespace App\Services\Purchasing;

use App\Models\DetailPembelian;
use App\Models\Pembelian;
use App\Services\Kafka\KafkaProducerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PembelianService
{
    public function __construct(
        private readonly KafkaProducerService $kafkaProducer
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Buat Purchase Order baru, simpan ke DB sebagai PENDING, lalu publish ke Kafka.
     *
     * Alur identik dengan TransaksiService::create():
     * - DB::transaction hanya wrapping INSERT pembelian + detail
     * - Kafka publish SETELAH commit — jika publish ada di dalam transaction
     *   dan rollback terjadi, Consumer akan memproses PO yang tidak ada di DB.
     */
    public function create(
        int    $supplierId,
        array  $items,
        string $tanggal,
        int    $userId
    ): Pembelian {
        $total         = $this->calculateTotal($items);
        $noPembelian   = $this->generateNoPembelian();

        $pembelian = DB::transaction(function () use (
            $supplierId, $items, $tanggal, $userId, $total, $noPembelian
        ) {
            $po = Pembelian::create([
                'user_id'           => $userId,
                'supplier_id'       => $supplierId,
                'no_pembelian'      => $noPembelian,
                'total'             => $total,
                'status'            => 'PENDING',
                'tanggal_pembelian' => $tanggal,
            ]);

            // nama_satuan dan isi_satuan adalah snapshot — disimpan agar
            // riwayat PO tidak berubah jika admin edit satuan di kemudian hari.
            // Consumer menggunakan isi_satuan untuk konversi: qty_stok += qty × isi_satuan
            foreach ($items as $item) {
                DetailPembelian::create([
                    'pembelian_id'    => $po->id,
                    'produk_id'       => $item['produk_id'],
                    'produk_satuan_id'=> $item['produk_satuan_id'] ?? null,
                    'nama_satuan'     => $item['nama_satuan'],
                    'isi_satuan'      => $item['isi_satuan'],
                    'qty'             => $item['qty'],
                    'harga_beli_satuan' => $item['harga_beli_satuan'],
                    'subtotal'        => $item['harga_beli_satuan'] * $item['qty'],
                ]);
            }

            return $po;
        });

        // Publish ke Kafka SETELAH commit
        $pembelian->loadMissing('details');
        $this->kafkaProducer->publishPurchaseCreated($pembelian);

        Log::info('[PEMBELIAN] Created', [
            'no_pembelian' => $noPembelian,
            'supplier_id'  => $supplierId,
            'user_id'      => $userId,
            'total'        => $total,
            'items'        => count($items),
        ]);

        return $pembelian;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KALKULASI
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hitung total PO: Σ (harga_beli_satuan × qty) per item.
     */
    public function calculateTotal(array $items): float
    {
        return (float) collect($items)->sum(
            fn ($item) => $item['harga_beli_satuan'] * $item['qty']
        );
    }

    /**
     * Update status pembelian — dipanggil oleh Kafka Consumer setelah stok di-update.
     */
    public function updateStatus(int $id, string $status): bool
    {
        return (bool) Pembelian::where('id', $id)->update(['status' => $status]);
    }

    /**
     * Cek apakah PO bisa diretur.
     * Hanya PO berstatus COMPLETED yang bisa diretur ke supplier.
     */
    public function canReturn(int $id): bool
    {
        return Pembelian::where('id', $id)
            ->where('status', 'COMPLETED')
            ->exists();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate nomor PO unik per hari: PO-YYYYMMDD-XXXXX
     * Pola identik dengan TransaksiService::generateNoTransaksi().
     */
    private function generateNoPembelian(): string
    {
        $today  = now()->format('Ymd');
        $prefix = "PO-{$today}-";

        $latest = Pembelian::where('no_pembelian', 'like', $prefix . '%')
            ->orderByDesc('no_pembelian')
            ->value('no_pembelian');

        $nextSeq = $latest
            ? ((int) substr($latest, -5)) + 1
            : 1;

        return $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
