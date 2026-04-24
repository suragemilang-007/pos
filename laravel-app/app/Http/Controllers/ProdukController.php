<?php

namespace App\Http\Controllers;

use App\Http\Requests\Produk\StoreProdukRequest;
use App\Http\Requests\Produk\UpdateProdukRequest;
use App\Models\Produk;
use App\Services\Inventory\ProdukSatuanService;
use App\Services\Inventory\ProdukService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function __construct(
        private readonly ProdukService       $produkService,
        private readonly ProdukSatuanService $produkSatuanService
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // CRUD PRODUK (admin gudang & owner)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/gudang/produk — SD-08
     * List produk dengan filter + pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Produk::with(['satuanList', 'kategori:id,nama_kategori']);

        // Filter opsional
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->integer('kategori_id'));
        }

        if ($request->filled('nama')) {
            $query->where('nama_produk', 'like', '%' . $request->string('nama') . '%');
        }

        // Default hanya tampilkan produk aktif; pass 'semua' untuk include nonaktif
        $status = $request->input('status', 'aktif');
        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        $limit  = min((int) $request->input('limit', 20), 100);
        $produk = $query->orderBy('nama_produk')->paginate($limit);

        $data = $produk->getCollection()->map(function (Produk $p) {
            return $this->formatProduk($p);
        });

        return response()->json([
            'data'       => $data,
            'pagination' => [
                'current_page' => $produk->currentPage(),
                'last_page'    => $produk->lastPage(),
                'per_page'     => $produk->perPage(),
                'total'        => $produk->total(),
            ],
        ], 200);
    }

    /**
     * POST /api/gudang/produk — SD-08
     * Buat produk baru beserta satuan_list.
     */
    public function store(StoreProdukRequest $request): JsonResponse
    {
        $produk = $this->produkService->create($request->validated());

        return response()->json([
            'data'    => $this->formatProduk($produk),
            'message' => 'Produk berhasil ditambahkan',
        ], 201);
    }

    /**
     * GET /api/gudang/produk/{id} — SD-08
     */
    public function show(int $produk): JsonResponse
    {
        $p = Produk::with(['satuanList', 'kategori:id,nama_kategori'])->find($produk);

        if (! $p) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json(['data' => $this->formatProduk($p)], 200);
    }

    /**
     * PUT /api/gudang/produk/{id} — SD-08
     * Update header produk saja. Satuan dikelola via /produk/{id}/satuan.
     */
    public function update(UpdateProdukRequest $request, int $produk): JsonResponse
    {
        $p = Produk::find($produk);

        if (! $p) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        $this->produkService->update($produk, $request->validated());

        // Reload setelah update
        $p->refresh()->load(['satuanList', 'kategori:id,nama_kategori']);

        return response()->json([
            'data'    => $this->formatProduk($p),
            'message' => 'Produk berhasil diperbarui',
        ], 200);
    }

    /**
     * DELETE /api/gudang/produk/{id} — SD-08
     *
     * Smart delete (AD-08):
     * - Pernah di transaksi/pembelian → soft delete (status='nonaktif')
     * - Belum pernah → hard delete
     */
    public function destroy(int $produk): JsonResponse
    {
        $p = Produk::find($produk);

        if (! $p) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        $result = $this->produkService->destroy($produk);

        $message = $result === 'soft'
            ? 'Produk dinonaktifkan (masih ada di riwayat transaksi)'
            : 'Produk dihapus';

        return response()->json(['message' => $message], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STOK REAL-TIME — kasir & owner
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/kasir/stok — SD-06
     *
     * Initial load daftar stok semua produk aktif untuk layar kasir.
     * Update real-time TIDAK dari polling endpoint ini — Frontend establish
     * WebSocket setelah initial load dan terima push event 'stock.updated'
     * dari Consumer saat ada transaksi/pembelian/retur yang selesai diproses.
     *
     * is_rendah: true jika qty_stok <= stok_minimum.
     * Flag ini dikirim dari backend agar frontend tidak perlu ulang kalkulasi —
     * cukup pakai nilai ini untuk highlight baris stok kritis.
     */
    public function stokRealTime(Request $request): JsonResponse
    {
        $query = Produk::with(['satuanList', 'kategori:id,nama_kategori'])
            ->where('status', 'aktif');

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->integer('kategori_id'));
        }

        if ($request->filled('nama')) {
            $query->where('nama_produk', 'like', '%' . $request->string('nama') . '%');
        }

        $limit  = min((int) $request->input('limit', 20), 100);
        $produk = $query->orderBy('nama_produk')->paginate($limit);

        $stok = $produk->getCollection()->map(function (Produk $p) {
            return [
                'id'          => $p->id,
                'kode_produk' => $p->kode_produk,
                'nama_produk' => $p->nama_produk,
                'kategori'    => $p->kategori?->nama_kategori,
                'satuan_dasar'=> $p->satuan_dasar,
                'qty_stok'    => $p->qty_stok,
                'stok_minimum'=> $p->stok_minimum,
                // is_rendah dihitung di backend — frontend langsung pakai tanpa kalkulasi ulang
                'is_rendah'   => $p->qty_stok <= $p->stok_minimum,
                'satuan_tersedia' => $this->produkSatuanService->buildSatuanTersedia($p),
            ];
        });

        return response()->json([
            'data' => [
                'stok'       => $stok,
                'pagination' => [
                    'current_page' => $produk->currentPage(),
                    'total'        => $produk->total(),
                    'per_page'     => $produk->perPage(),
                ],
            ],
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CEK STOK (kasir & owner) — SD-07
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/kasir/produk/{kode}/stok — SD-07
     */
    public function cekStok(string $kode): JsonResponse
    {
        $produk = $this->produkService->getByKode($kode);

        if (! $produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan atau tidak aktif',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id'              => $produk->id,
                'kode_produk'     => $produk->kode_produk,
                'nama_produk'     => $produk->nama_produk,
                'satuan_dasar'    => $produk->satuan_dasar,
                'qty_stok'        => $produk->qty_stok,
                'stok_minimum'    => $produk->stok_minimum,
                'satuan_tersedia' => $this->produkSatuanService->buildSatuanTersedia($produk),
            ],
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPER
    // ─────────────────────────────────────────────────────────────────────────

    private function formatProduk(Produk $p): array
    {
        return [
            'id'               => $p->id,
            'kode_produk'      => $p->kode_produk,
            'nama_produk'      => $p->nama_produk,
            'deskripsi'        => $p->deskripsi,
            'kategori'         => $p->kategori
                ? ['id' => $p->kategori->id, 'nama' => $p->kategori->nama_kategori]
                : null,
            'harga_beli_dasar' => (float) $p->harga_beli_dasar,
            'satuan_dasar'     => $p->satuan_dasar,
            'qty_stok'         => $p->qty_stok,
            'stok_minimum'     => $p->stok_minimum,
            'status'           => $p->status,
            'satuan_tersedia'  => $this->produkSatuanService->buildSatuanTersedia($p),
        ];
    }
}
