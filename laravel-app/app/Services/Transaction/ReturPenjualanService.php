<?php

namespace App\Services\Transaction;

use App\Models\DetailReturPenjualan;
use App\Models\DetailTransaksi;
use App\Models\ReturPenjualan;
use App\Models\Transaksi;
use App\Services\Kafka\KafkaProducerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturPenjualanService
{
    public function __construct(
        private readonly KafkaProducerService $kafkaProducer
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // VALIDASI
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validasi sebelum retur diproses.
     *
     * Dua hal yang divalidasi (sesuai AD-05):
     * 1. Transaksi harus status=COMPLETED — PENDING/FAILED tidak bisa diretur
     * 2. qty_retur tidak boleh melebihi qty asli di detail_transaksi per produk
     *
     * Validasi dilakukan di Service (bukan Consumer) karena ini adalah business rule
     * yang perlu diketahui kasir sebelum data dikirim ke Kafka.
     * Consumer hanya bertanggung jawab update stok dan status setelah validasi lolos.
     *
     * @throws \RuntimeException  kode 400 jika tidak valid
     */
    public function validate(int $transaksiId, array $itemsRetur): bool
    {
        $transaksi = Transaksi::find($transaksiId);

        if (! $transaksi || $transaksi->status !== 'COMPLETED') {
            throw new \RuntimeException(
                'Transaksi tidak dapat diretur (hanya transaksi COMPLETED yang bisa diretur)',
                400
            );
        }

        foreach ($itemsRetur as $item) {
            // Cari detail transaksi asal berdasarkan produk DAN nama_satuan —
            // satu transaksi bisa punya produk yang sama dengan satuan berbeda
            $detail = DetailTransaksi::where('transaksi_id', $transaksiId)
                ->where('produk_id', $item['produk_id'])
                ->where('nama_satuan', $item['nama_satuan'])
                ->first();

            if (! $detail) {
                throw new \RuntimeException(
                    "Produk ID {$item['produk_id']} dengan satuan '{$item['nama_satuan']}' tidak ditemukan di transaksi asal",
                    400
                );
            }

            if ((int) $item['qty_retur'] > $detail->qty) {
                throw new \RuntimeException(
                    "Qty retur ({$item['qty_retur']}) melebihi qty asli ({$detail->qty}) " .
                    "untuk produk ID {$item['produk_id']} satuan '{$item['nama_satuan']}'",
                    400
                );
            }
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Proses pengajuan retur penjualan.
     *
     * Alur: validate() → DB::transaction (INSERT) → publishKafka (di luar transaction)
     * Consumer yang akan UPDATE status='COMPLETED' dan total_refund.
     */
    public function create(
        int    $transaksiId,
        array  $itemsRetur,
        string $alasan,
        int    $userId
    ): ReturPenjualan {
        // Validasi dulu — lempar exception sebelum INSERT jika ada masalah
        $this->validate($transaksiId, $itemsRetur);

        $noRetur = $this->generateNoRetur();

        $retur = DB::transaction(function () use (
            $transaksiId, $itemsRetur, $alasan, $userId, $noRetur
        ) {
            $r = ReturPenjualan::create([
                'transaksi_id' => $transaksiId,
                'user_id'      => $userId,
                'no_retur'     => $noRetur,
                'alasan'       => $alasan,
                'total_refund' => 0.00,     // Consumer yang update setelah proses
                'status'       => 'PENDING',
            ]);

            // Snapshot dari detail_transaksi asal — isi_satuan penting untuk Consumer
            // agar bisa hitung: qty_stok_bertambah = qty_retur × isi_satuan
            foreach ($itemsRetur as $item) {
                DetailReturPenjualan::create([
                    'retur_id'     => $r->id,
                    'produk_id'    => $item['produk_id'],
                    'nama_satuan'  => $item['nama_satuan'],
                    'isi_satuan'   => $item['isi_satuan'],
                    'qty_retur'    => $item['qty_retur'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal_refund' => $item['harga_satuan'] * $item['qty_retur'],
                ]);
            }

            return $r;
        });

        // Publish ke Kafka SETELAH commit — Consumer tambah stok + update status
        $retur->loadMissing('details');
        $this->kafkaProducer->publishSaleReturnRequested($retur);

        Log::info('[RETUR PENJUALAN] Created', [
            'no_retur'     => $noRetur,
            'transaksi_id' => $transaksiId,
            'user_id'      => $userId,
            'items'        => count($itemsRetur),
        ]);

        return $retur;
    }

    /**
     * Hitung estimasi total refund: Σ (harga_satuan × qty_retur).
     */
    public function calculateRefund(array $itemsRetur): float
    {
        return (float) collect($itemsRetur)->sum(
            fn ($item) => $item['harga_satuan'] * $item['qty_retur']
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPER
    // ─────────────────────────────────────────────────────────────────────────

    private function generateNoRetur(): string
    {
        $today  = now()->format('Ymd');
        $prefix = "RTJ-{$today}-";

        $latest = ReturPenjualan::where('no_retur', 'like', $prefix . '%')
            ->orderByDesc('no_retur')
            ->value('no_retur');

        $nextSeq = $latest ? ((int) substr($latest, -5)) + 1 : 1;

        return $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
