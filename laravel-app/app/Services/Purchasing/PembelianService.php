<?php

namespace App\Services\Purchasing;

use App\Models\DetailPembelian;
use App\Models\Pembelian;
use App\Services\Kafka\KafkaProducerService;
use Illuminate\Support\Collection;
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
     * Buat Purchase Order baru dengan dukungan cash/kredit (v4).
     *
     * Alur:
     * - DB::transaction: INSERT pembelian + detail_pembelian
     * - Kafka publish SETELAH commit (jika di dalam transaction, rollback
     *   akan tinggalkan Consumer memproses PO yang tidak ada di DB)
     *
     * Logika status_pembayaran (sesuai CLAUDE.md §4 Konsep Hutang Dagang):
     *   cash           → lunas    (tidak ada hutang)
     *   kredit, dp=0   → belum_lunas (hutang penuh)
     *   kredit, dp>0   → sebagian (sebagian sudah dibayar)
     *
     * sisa_hutang = total - jumlah_dp (dihitung di Service, bukan DB trigger)
     */
    public function create(
        int     $supplierId,
        array   $items,
        string  $tanggal,
        string  $tipePembayaran   = 'cash',
        float   $jumlahDp         = 0.0,
        ?string $tanggalJatuhTempo = null,
        ?string $catatanPembayaran = null,
        int     $userId           = 0
    ): Pembelian {
        $total      = $this->calculateTotal($items);
        // Cash: sisa_hutang selalu 0 (lunas saat diterima — bukan berdasar DP).
        // Kredit: sisa = total - dp (dp bisa 0 jika tidak ada uang muka).
        $sisaHutang = $tipePembayaran === 'cash' ? 0.0 : max(0.0, $total - $jumlahDp);

        // Tentukan status pembayaran awal berdasarkan tipe dan DP
        $statusPembayaran = match (true) {
            $tipePembayaran === 'cash' => 'lunas',
            $jumlahDp <= 0            => 'belum_lunas',
            default                   => 'sebagian',
        };

        $noPembelian = $this->generateNoPembelian();

        $pembelian = DB::transaction(function () use (
            $supplierId, $items, $tanggal, $userId, $total,
            $tipePembayaran, $jumlahDp, $sisaHutang, $tanggalJatuhTempo,
            $statusPembayaran, $catatanPembayaran, $noPembelian
        ) {
            $po = Pembelian::create([
                'user_id'              => $userId,
                'supplier_id'          => $supplierId,
                'no_pembelian'         => $noPembelian,
                'total'                => $total,
                // ── Hutang dagang v4 ──────────────────────────────────────────
                'tipe_pembayaran'      => $tipePembayaran,
                'jumlah_dp'            => $jumlahDp,
                'sisa_hutang'          => $sisaHutang,
                'tanggal_jatuh_tempo'  => $tanggalJatuhTempo,
                'status_pembayaran'    => $statusPembayaran,
                'catatan_pembayaran'   => $catatanPembayaran,
                // ─────────────────────────────────────────────────────────────
                'status'               => 'PENDING',
                'tanggal_pembelian'    => $tanggal,
            ]);

            // Snapshot nama_satuan & isi_satuan — riwayat tidak berubah
            // jika admin edit satuan di kemudian hari.
            // Consumer pakai isi_satuan: qty_stok += qty × isi_satuan
            foreach ($items as $item) {
                DetailPembelian::create([
                    'pembelian_id'      => $po->id,
                    'produk_id'         => $item['produk_id'],
                    'produk_satuan_id'  => $item['produk_satuan_id'] ?? null,
                    'nama_satuan'       => $item['nama_satuan'],
                    'isi_satuan'        => $item['isi_satuan'],
                    'qty'               => $item['qty'],
                    'harga_beli_satuan' => $item['harga_beli_satuan'],
                    'subtotal'          => $item['harga_beli_satuan'] * $item['qty'],
                ]);
            }

            return $po;
        });

        // Publish ke Kafka SETELAH commit
        $pembelian->loadMissing('details');
        $this->kafkaProducer->publishPurchaseCreated($pembelian);

        Log::info('[PEMBELIAN] Created', [
            'no_pembelian'      => $noPembelian,
            'supplier_id'       => $supplierId,
            'user_id'           => $userId,
            'total'             => $total,
            'tipe_pembayaran'   => $tipePembayaran,
            'jumlah_dp'         => $jumlahDp,
            'sisa_hutang'       => $sisaHutang,
            'status_pembayaran' => $statusPembayaran,
            'items'             => count($items),
        ]);

        return $pembelian;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HUTANG DAGANG
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Daftar PO yang belum lunas, diurutkan dari jatuh tempo terdekat.
     *
     * Menggunakan raw query DB::table() karena melibatkan:
     * - LEFT JOIN ke pembayaran_pembelian dengan SUM agregasi
     * - DATEDIFF untuk hitung hari_tersisa langsung di DB (lebih efisien dari accessor)
     * - ORDER BY NULL handling (cash tanpa jatuh tempo ke bawah)
     *
     * filters: { supplier_id?, status_pembayaran?, dari?, sampai? }
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDaftarHutang(array $filters = []): Collection
    {
        $query = DB::table('pembelian')
            ->select([
                'pembelian.id',
                'pembelian.no_pembelian',
                'pembelian.supplier_id',
                'suppliers.nama_supplier',
                'pembelian.total',
                'pembelian.jumlah_dp',
                'pembelian.sisa_hutang',
                'pembelian.tanggal_jatuh_tempo',
                'pembelian.status_pembayaran',
                'pembelian.tanggal_pembelian',
                'pembelian.catatan_pembayaran',
            ])
            ->selectRaw('COALESCE(SUM(pp.jumlah), 0)                    AS total_dibayar')
            ->selectRaw('DATEDIFF(pembelian.tanggal_jatuh_tempo, CURDATE()) AS hari_tersisa')
            ->leftJoin('suppliers',            'suppliers.id', '=', 'pembelian.supplier_id')
            ->leftJoin('pembayaran_pembelian AS pp', 'pp.pembelian_id', '=', 'pembelian.id')
            ->whereIn('pembelian.status_pembayaran', ['belum_lunas', 'sebagian'])
            ->where('pembelian.status', 'COMPLETED')
            ->groupBy(
                'pembelian.id', 'pembelian.no_pembelian', 'pembelian.supplier_id',
                'suppliers.nama_supplier', 'pembelian.total', 'pembelian.jumlah_dp',
                'pembelian.sisa_hutang', 'pembelian.tanggal_jatuh_tempo',
                'pembelian.status_pembayaran', 'pembelian.tanggal_pembelian',
                'pembelian.catatan_pembayaran'
            )
            // NULL (cash tanpa jatuh tempo) muncul di bawah; kredit yang paling dekat di atas
            ->orderByRaw('pembelian.tanggal_jatuh_tempo IS NULL ASC')
            ->orderBy('pembelian.tanggal_jatuh_tempo', 'asc');

        if (! empty($filters['supplier_id'])) {
            $query->where('pembelian.supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['status_pembayaran'])) {
            $query->where('pembelian.status_pembayaran', $filters['status_pembayaran']);
        }

        if (! empty($filters['dari'])) {
            $query->whereDate('pembelian.tanggal_pembelian', '>=', $filters['dari']);
        }

        if (! empty($filters['sampai'])) {
            $query->whereDate('pembelian.tanggal_pembelian', '<=', $filters['sampai']);
        }

        return $query->get();
    }

    /**
     * Hitung total hutang per supplier — dipakai dashboard & laporan.
     */
    public function getTotalHutangPerSupplier(): array
    {
        return DB::table('pembelian')
            ->join('suppliers', 'suppliers.id', '=', 'pembelian.supplier_id')
            ->selectRaw('pembelian.supplier_id, suppliers.nama_supplier, SUM(pembelian.sisa_hutang) AS total_sisa')
            ->whereIn('pembelian.status_pembayaran', ['belum_lunas', 'sebagian'])
            ->where('pembelian.status', 'COMPLETED')
            ->groupBy('pembelian.supplier_id', 'suppliers.nama_supplier')
            ->orderByDesc('total_sisa')
            ->get()
            ->toArray();
    }

    /**
     * PO yang jatuh tempo dalam N hari ke depan — untuk sistem reminder.
     */
    public function getHutangJatuhTempoDalam(int $hari): Collection
    {
        return Pembelian::with('supplier:id,nama_supplier')
            ->whereIn('status_pembayaran', ['belum_lunas', 'sebagian'])
            ->where('status', 'COMPLETED')
            ->whereNotNull('tanggal_jatuh_tempo')
            ->whereDate('tanggal_jatuh_tempo', '<=', now()->addDays($hari)->toDateString())
            ->whereDate('tanggal_jatuh_tempo', '>=', now()->toDateString())
            ->orderBy('tanggal_jatuh_tempo')
            ->get();
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
