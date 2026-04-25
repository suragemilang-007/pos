<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pembelian\StorePembelianRequest;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Services\Purchasing\PembelianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                supplierId: $v['supplier_id'],
                items:      $v['items'],
                tanggal:    $v['tanggal_pembelian'],
                userId:     $request->input('auth_user_id')
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
