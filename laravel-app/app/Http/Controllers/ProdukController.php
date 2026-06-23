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
            $cari = $request->string('nama');
            $query->where(function ($q) use ($cari) {
                $q->where('nama_produk', 'like', '%' . $cari . '%')
                  ->orWhereHas('satuanList', fn ($s) => $s->where('barcode', $cari));
            });
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
    // SUGGEST PRODUK (kasir) — autocomplete saat kasir mengetik nama/kode
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/kasir/produk/suggest?q=xxx
     *
     * Dipakai dropdown autocomplete di halaman transaksi.
     * Cari berdasarkan: nama_produk (LIKE) ATAU kode_produk (LIKE) ATAU barcode (exact).
     * Kembalikan maks 8 hasil agar dropdown tidak terlalu panjang.
     */
    public function suggestProduk(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['data' => []], 200);
        }

        $results = Produk::with('satuanList:id,produk_id,nama_satuan,is_default')
            ->where('status', 'aktif')
            ->where(function ($query) use ($q) {
                $query->where('nama_produk', 'like', '%' . $q . '%')
                      ->orWhere('kode_produk', 'like', '%' . $q . '%')
                      ->orWhereHas('satuanList', fn ($s) => $s->where('barcode', $q));
            })
            ->orderBy('nama_produk')
            ->limit(8)
            ->get(['id', 'kode_produk', 'nama_produk', 'satuan_dasar', 'qty_stok']);

        return response()->json([
            'data' => $results->map(fn ($p) => [
                'kode_produk'  => $p->kode_produk,
                'nama_produk'  => $p->nama_produk,
                'satuan_dasar' => $p->satuan_dasar,
                'qty_stok'     => $p->qty_stok,
            ])->values(),
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CEK STOK (kasir & owner) — SD-07
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/kasir/produk/{kode}/stok — SD-07 (update v3: tambah promo_aktif)
     *
     * promo_aktif digunakan Frontend JavaScript untuk:
     * - Menampilkan label promo di UI kasir ("Beli 5 Gratis 1")
     * - Menghitung item bonus dan diskon secara lokal (TIDAK di backend — CLAUDE.md §5.6)
     * - Menyertakan promo_id + is_bonus saat POST /transaksi
     *
     * bonus_produk.satuan_tersedia disertakan agar frontend tahu satuan item bonus
     * tanpa perlu request tambahan ke endpoint lain.
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
                // ── SD-07 v3: promo aktif hari ini ──────────────────────────
                'promo_aktif'     => $produk->promoAktif->map(fn ($p) => [
                    'id'               => $p->id,
                    'nama_promo'       => $p->nama_promo,
                    'tipe_promo'       => $p->tipe_promo,
                    'min_qty'          => (int) $p->min_qty,
                    'produk_satuan_id' => $p->produk_satuan_id,
                    'bonus_qty'        => $p->bonus_qty !== null ? (int) $p->bonus_qty : null,
                    // bonus_produk: null untuk tipe selain bonus_produk_lain
                    // satuanList sudah eager-loaded di getByKode() — tidak ada N+1 query
                    'bonus_produk'     => $p->bonusProduk ? [
                        'id'              => $p->bonusProduk->id,
                        'nama_produk'     => $p->bonusProduk->nama_produk,
                        'satuan_tersedia' => $this->produkSatuanService->buildSatuanTersedia($p->bonusProduk),
                    ] : null,
                    'bonus_satuan_id'  => $p->bonus_satuan_id,
                    'diskon_nilai'     => $p->diskon_nilai !== null ? (float) $p->diskon_nilai : null,
                    'keterangan'       => $p->keterangan,
                ])->values(),
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
            // has_transaksi dipakai frontend untuk menentukan pesan konfirmasi hapus (AD-08):
            // true  → soft delete "Produk akan dinonaktifkan"
            // false → hard delete "Produk akan dihapus permanen"
            'has_transaksi'    => $this->produkService->hasTransaksi($p->id),
            'satuan_tersedia'  => $this->produkSatuanService->buildSatuanTersedia($p),
            // satuan_list: data lengkap termasuk harga_beli dan barcode untuk halaman manajemen produk.
            // Berbeda dari satuan_tersedia (hanya harga_jual + stok, untuk kasir).
            'satuan_list'      => $p->satuanList->map(fn ($s) => [
                'id'          => $s->id,
                'nama_satuan' => $s->nama_satuan,
                'isi'         => $s->isi,
                'harga_beli'  => (float) $s->harga_beli,
                'harga_jual'  => (float) $s->harga_jual,
                'is_default'  => (bool) $s->is_default,
                'barcode'     => $s->barcode,
            ])->values(),
        ];
    }
}
