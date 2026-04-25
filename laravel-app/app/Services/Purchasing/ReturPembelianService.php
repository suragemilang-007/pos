<?php

namespace App\Services\Purchasing;

use App\Models\DetailPembelian;
use App\Models\DetailReturPembelian;
use App\Models\Pembelian;
use App\Models\ReturPembelian;
use App\Services\Kafka\KafkaProducerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturPembelianService
{
    public function __construct(
        private readonly KafkaProducerService $kafkaProducer
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // VALIDASI
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validasi sebelum retur pembelian diproses.
     *
     * Dua hal yang divalidasi (sesuai AD-10):
     * 1. Pembelian harus status=COMPLETED
     * 2. qty_retur tidak boleh melebihi qty asli di detail_pembelian per produk
     *
     * @throws \RuntimeException  kode 400 jika tidak valid
     */
    public function validate(int $pembelianId, array $itemsRetur): bool
    {
        $pembelian = Pembelian::find($pembelianId);

        if (! $pembelian || $pembelian->status !== 'COMPLETED') {
            throw new \RuntimeException(
                'Pembelian tidak dapat diretur (hanya pembelian COMPLETED yang bisa diretur)',
                400
            );
        }

        foreach ($itemsRetur as $item) {
            $detail = DetailPembelian::where('pembelian_id', $pembelianId)
                ->where('produk_id', $item['produk_id'])
                ->where('nama_satuan', $item['nama_satuan'])
                ->first();

            if (! $detail) {
                throw new \RuntimeException(
                    "Produk ID {$item['produk_id']} dengan satuan '{$item['nama_satuan']}' tidak ditemukan di PO asal",
                    400
                );
            }

            if ((int) $item['qty_retur'] > $detail->qty) {
                throw new \RuntimeException(
                    "Qty retur ({$item['qty_retur']}) melebihi qty PO ({$detail->qty}) " .
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
     * Proses pengajuan retur pembelian.
     * Consumer akan KURANGI qty_stok dan UPDATE status='COMPLETED' + total_kredit.
     */
    public function create(
        int    $pembelianId,
        array  $itemsRetur,
        string $alasan,
        int    $userId
    ): ReturPembelian {
        $this->validate($pembelianId, $itemsRetur);

        $noRetur = $this->generateNoRetur();

        $retur = DB::transaction(function () use (
            $pembelianId, $itemsRetur, $alasan, $userId, $noRetur
        ) {
            $r = ReturPembelian::create([
                'pembelian_id' => $pembelianId,
                'user_id'      => $userId,
                'no_retur'     => $noRetur,
                'alasan'       => $alasan,
                'total_kredit' => 0.00,     // Consumer yang update
                'status'       => 'PENDING',
            ]);

            // isi_satuan penting untuk Consumer:
            // qty_stok_berkurang = qty_retur × isi_satuan (produk kembali ke supplier)
            foreach ($itemsRetur as $item) {
                DetailReturPembelian::create([
                    'retur_pembelian_id' => $r->id,
                    'produk_id'          => $item['produk_id'],
                    'nama_satuan'        => $item['nama_satuan'],
                    'isi_satuan'         => $item['isi_satuan'],
                    'qty_retur'          => $item['qty_retur'],
                    'harga_satuan'       => $item['harga_satuan'],
                    'subtotal_kredit'    => $item['harga_satuan'] * $item['qty_retur'],
                ]);
            }

            return $r;
        });

        $retur->loadMissing('details');
        $this->kafkaProducer->publishPurchaseReturnRequested($retur);

        Log::info('[RETUR PEMBELIAN] Created', [
            'no_retur'     => $noRetur,
            'pembelian_id' => $pembelianId,
            'user_id'      => $userId,
            'items'        => count($itemsRetur),
        ]);

        return $retur;
    }

    /**
     * Hitung estimasi total kredit ke supplier: Σ (harga_satuan × qty_retur).
     */
    public function calculateKredit(array $itemsRetur): float
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
        $prefix = "RTP-{$today}-";

        $latest = ReturPembelian::where('no_retur', 'like', $prefix . '%')
            ->orderByDesc('no_retur')
            ->value('no_retur');

        $nextSeq = $latest ? ((int) substr($latest, -5)) + 1 : 1;

        return $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
