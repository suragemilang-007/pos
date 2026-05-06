<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pembelian\StorePembelianRequest;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Services\Purchasing\PembelianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianController extends Controller
{
    public function __construct(
        private readonly PembelianService $pembelianService
    ) {}

    /**
     * GET /api/gudang/ringkasan — data untuk dashboard admin gudang.
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $bulanIni   = now()->startOfMonth()->toDateString();
        $hariIni    = now()->toDateString();

        // Pembelian bulan ini (COMPLETED)
        $totalBelanjaBuilan = (float) Pembelian::where('status', 'COMPLETED')
            ->whereBetween('tanggal_pembelian', [$bulanIni, $hariIni])
            ->sum('total');

        // PO masih PENDING
        $poPending = Pembelian::where('status', 'PENDING')->count();

        // Produk aktif
        $totalProdukAktif = Produk::where('status', 'aktif')->count();

        // Stok rendah
        $produkStokRendah = Produk::where('status', 'aktif')
            ->whereColumn('qty_stok', '<=', 'stok_minimum')
            ->count();

        // 5 pembelian terakhir dengan nama supplier
        $terakhir = Pembelian::with('supplier:id,nama_supplier')
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'supplier_id', 'no_pembelian', 'tanggal_pembelian', 'total', 'status']);

        return response()->json([
            'data' => [
                'total_produk_aktif'    => $totalProdukAktif,
                'produk_stok_rendah'    => $produkStokRendah,
                'total_belanja_bulan'   => $totalBelanjaBuilan,
                'po_pending'            => $poPending,
                'pembelian_terakhir'    => $terakhir->map(fn ($p) => [
                    'id'            => $p->id,
                    'no_pembelian'  => $p->no_pembelian,
                    'supplier'      => $p->supplier?->nama_supplier ?? '-',
                    'tanggal'       => $p->tanggal_pembelian,
                    'total'         => (float) $p->total,
                    'status'        => $p->status,
                ])->values(),
            ],
        ]);
    }

    /**
     * GET /api/gudang/pembelian
     * List PO dengan filter status, supplier, dan rentang tanggal.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pembelian::with('supplier:id,nama_supplier');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('dari')) {
            $query->whereDate('tanggal_pembelian', '>=', $request->input('dari'));
        }

        if ($request->filled('sampai')) {
            $query->whereDate('tanggal_pembelian', '<=', $request->input('sampai'));
        }

        $limit     = min((int) $request->input('limit', 20), 100);
        $pembelian = $query->orderByDesc('tanggal_pembelian')->paginate($limit);

        return response()->json([
            'data'       => $pembelian->items(),
            'pagination' => [
                'current_page' => $pembelian->currentPage(),
                'last_page'    => $pembelian->lastPage(),
                'per_page'     => $pembelian->perPage(),
                'total'        => $pembelian->total(),
            ],
        ], 200);
    }

    /**
     * POST /api/gudang/pembelian — SD-09
     *
     * Async via Kafka → return 202 Accepted.
     * Consumer akan UPDATE qty_stok dan harga_beli_dasar setelah PO diproses.
     */
    public function store(StorePembelianRequest $request): JsonResponse
    {
        try {
            $v = $request->validated();

            $pembelian = $this->pembelianService->create(
                supplierId:          $v['supplier_id'],
                items:               $v['items'],
                tanggal:             $v['tanggal_pembelian'],
                tipePembayaran:      $v['tipe_pembayaran'],
                jumlahDp:            (float) ($v['jumlah_dp'] ?? 0),
                tanggalJatuhTempo:   $v['tanggal_jatuh_tempo'] ?? null,
                catatanPembayaran:   $v['catatan_pembayaran'] ?? null,
                userId:              (int) $request->input('auth_user_id')
            );

            return response()->json([
                'id_pembelian' => $pembelian->id,
                'no_pembelian' => $pembelian->no_pembelian,
                'status'       => 'PENDING',
                'message'      => 'Pembelian sedang diproses',
            ], 202);

        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'Gagal mengirim pembelian ke antrian: ' . $e->getMessage(),
            ], 503);
        }
    }

    /**
     * GET /api/gudang/hutang — SD-19
     *
     * Daftar PO yang belum lunas (belum_lunas atau sebagian), terurut
     * dari yang paling dekat jatuh temponya agar admin melihat prioritas.
     *
     * withSum('pembayaran','jumlah') dipakai untuk menghindari N+1 pada
     * total_dibayar — lebih efisien dari accessor $p->total_dibayar yang
     * lazy-load semua record pembayaran per PO.
     */
    public function daftarHutang(Request $request): JsonResponse
    {
        $reminderDays = config('services.hutang_reminder_days', 3);

        // Filter status_pembayaran — default: semua kredit (belum_lunas & sebagian).
        // Klien bisa kirim nilai tunggal atau koma-pisah (misal "belum_lunas,sebagian").
        $validStatus = ['belum_lunas', 'sebagian', 'lunas'];
        $statusFilter = $request->filled('status_pembayaran')
            ? array_intersect(explode(',', $request->input('status_pembayaran')), $validStatus)
            : ['belum_lunas', 'sebagian'];
        if (empty($statusFilter)) {
            $statusFilter = ['belum_lunas', 'sebagian'];
        }

        $query = Pembelian::with('supplier:id,nama_supplier')
            ->withSum('pembayaran', 'jumlah')
            ->whereIn('status_pembayaran', $statusFilter)
            ->where('status', 'COMPLETED');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        // Urut: jatuh tempo terdekat di atas (NULL = cash, taruh paling bawah)
        $query->orderByRaw('tanggal_jatuh_tempo IS NULL ASC')
              ->orderBy('tanggal_jatuh_tempo', 'asc');

        $limit     = min((int) $request->input('limit', 20), 100);
        $paginated = $query->paginate($limit);

        // Ringkasan keseluruhan (tidak terpengaruh pagination)
        $ringkasan = Pembelian::where('status', 'COMPLETED')
            ->whereIn('status_pembayaran', ['belum_lunas', 'sebagian'])
            ->selectRaw('
                SUM(sisa_hutang)                                          AS total_hutang_semua,
                SUM(CASE WHEN tanggal_jatuh_tempo <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                         AND tanggal_jatuh_tempo >= CURDATE()
                         THEN 1 ELSE 0 END)                               AS hutang_jatuh_tempo_n_hari
            ', [$reminderDays])
            ->first();

        $data = $paginated->getCollection()->map(fn (Pembelian $po) => [
            'id'                  => $po->id,
            'no_pembelian'        => $po->no_pembelian,
            'tanggal_pembelian'   => $po->tanggal_pembelian
                ? (is_string($po->tanggal_pembelian) ? $po->tanggal_pembelian : $po->tanggal_pembelian->format('Y-m-d'))
                : null,
            'supplier'            => $po->supplier
                ? ['id' => $po->supplier->id, 'nama_supplier' => $po->supplier->nama_supplier]
                : null,
            'total'               => (float) $po->total,
            'jumlah_dp'           => (float) $po->jumlah_dp,
            'sisa_hutang'         => (float) $po->sisa_hutang,
            'tanggal_jatuh_tempo' => $po->tanggal_jatuh_tempo
                ? $po->tanggal_jatuh_tempo->format('Y-m-d')
                : null,
            'status_pembayaran'   => $po->status_pembayaran,
            'hari_tersisa'        => $po->hari_tersisa,
            'total_dibayar'       => (float) ($po->pembayaran_sum_jumlah ?? 0),
        ]);

        return response()->json([
            'data'       => $data->values(),
            'ringkasan'  => [
                'total_hutang_semua'        => (float) ($ringkasan->total_hutang_semua ?? 0),
                'hutang_jatuh_tempo_3hari'  => (int)   ($ringkasan->hutang_jatuh_tempo_n_hari ?? 0),
            ],
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ], 200);
    }

    /**
     * GET /api/gudang/pembelian/{id} — SD-10 (verifikasi PO asal sebelum retur)
     *
     * Include detail item dengan nama_satuan dan isi_satuan (snapshot).
     */
    public function show(int $pembelian): JsonResponse
    {
        $po = Pembelian::with([
            'supplier:id,nama_supplier,no_telp',
            'details.produk:id,nama_produk',
            'user:id,nama',
        ])->find($pembelian);

        if (! $po) {
            return response()->json(['message' => 'Pembelian tidak ditemukan'], 404);
        }

        return response()->json([
            'data' => [
                'id'                => $po->id,
                'no_pembelian'      => $po->no_pembelian,
                'status'            => $po->status,
                'tanggal_pembelian' => $po->tanggal_pembelian,
                'total'             => (float) $po->total,
                'supplier'          => $po->supplier
                    ? ['id' => $po->supplier->id, 'nama_supplier' => $po->supplier->nama_supplier]
                    : null,
                'dibuat_oleh'       => $po->user?->nama,
                // items menggunakan snapshot nama_satuan & isi_satuan dari detail_pembelian
                'items' => $po->details->map(fn ($d) => [
                    'produk_id'         => $d->produk_id,
                    'nama_produk'       => $d->produk?->nama_produk,
                    'nama_satuan'       => $d->nama_satuan,
                    'isi_satuan'        => $d->isi_satuan,
                    'qty'               => $d->qty,
                    'harga_beli_satuan' => (float) $d->harga_beli_satuan,
                    'subtotal'          => (float) $d->subtotal,
                ])->values(),
            ],
        ], 200);
    }
}
