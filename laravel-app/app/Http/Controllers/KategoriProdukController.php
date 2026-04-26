<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kategori\StoreKategoriRequest;
use App\Http\Requests\Kategori\UpdateKategoriRequest;
use App\Models\KategoriProduk;
use App\Services\Inventory\KategoriProdukService;
use Illuminate\Http\JsonResponse;

class KategoriProdukController extends Controller
{
    public function __construct(
        private readonly KategoriProdukService $kategoriService
    ) {}

    /**
     * GET /api/kategori — Daftar ringan untuk dropdown form.
     * Hanya id + nama_kategori, urut A-Z.
     * Diakses oleh kasir, admin_gudang, dan owner (SD-16).
     */
    public function index(): JsonResponse
    {
        $data = KategoriProduk::orderBy('nama_kategori')
            ->get(['id', 'nama_kategori']);

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/gudang/kategori — Daftar lengkap dengan jumlah_produk.
     * Digunakan di halaman manajemen kategori (SD-16).
     */
    public function indexWithCount(): JsonResponse
    {
        $data = $this->kategoriService->getAll();

        return response()->json(['data' => $data]);
    }

    /**
     * POST /api/gudang/kategori
     */
    public function store(StoreKategoriRequest $request): JsonResponse
    {
        $kategori = $this->kategoriService->create($request->validated());

        return response()->json([
            'data'    => $kategori,
            'message' => 'Kategori berhasil ditambahkan',
        ], 201);
    }

    /**
     * PUT /api/gudang/kategori/{id}
     */
    public function update(UpdateKategoriRequest $request, int $id): JsonResponse
    {
        $this->kategoriService->update($id, $request->validated());

        return response()->json([
            'data'    => KategoriProduk::find($id),
            'message' => 'Kategori berhasil diperbarui',
        ]);
    }

    /**
     * DELETE /api/gudang/kategori/{id}
     * Gagal dengan 422 jika kategori masih dipakai produk (aktif maupun nonaktif).
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->kategoriService->delete($id);

            return response()->json(['message' => 'Kategori berhasil dihapus']);

        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
