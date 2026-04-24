<?php

namespace App\Http\Controllers;

use App\Http\Requests\Produk\StoreProdukSatuanRequest;
use App\Http\Requests\Produk\UpdateProdukSatuanRequest;
use App\Models\Produk;
use App\Services\Inventory\ProdukSatuanService;
use Illuminate\Http\JsonResponse;

class ProdukSatuanController extends Controller
{
    public function __construct(
        private readonly ProdukSatuanService $produkSatuanService
    ) {}

    /**
     * GET /api/gudang/produk/{id}/satuan
     * List semua satuan untuk satu produk.
     */
    public function index(int $produk): JsonResponse
    {
        $p = Produk::find($produk);

        if (! $p) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        $satuan = $this->produkSatuanService->getSatuanList($produk);

        return response()->json(['data' => $satuan], 200);
    }

    /**
     * POST /api/gudang/produk/{id}/satuan
     * Tambah satuan baru ke sebuah produk.
     */
    public function store(StoreProdukSatuanRequest $request, int $produk): JsonResponse
    {
        $p = Produk::find($produk);

        if (! $p) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        $satuan = $this->produkSatuanService->create($produk, $request->validated());

        return response()->json([
            'data'    => $satuan,
            'message' => 'Satuan berhasil ditambahkan',
        ], 201);
    }

    /**
     * PUT /api/gudang/produk/{id}/satuan/{satuan}
     * Update satuan produk.
     */
    public function update(UpdateProdukSatuanRequest $request, int $produk, int $satuan): JsonResponse
    {
        try {
            $this->produkSatuanService->update($satuan, $request->validated());

            return response()->json([
                'message' => 'Satuan berhasil diperbarui',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Satuan tidak ditemukan'], 404);
        }
    }

    /**
     * DELETE /api/gudang/produk/{id}/satuan/{satuan}
     *
     * Tolak penghapusan jika satuan pernah dipakai di detail_transaksi
     * atau detail_pembelian — data snapshot riwayat harus tetap valid.
     */
    public function destroy(int $produk, int $satuan): JsonResponse
    {
        try {
            $this->produkSatuanService->delete($satuan);

            return response()->json(['message' => 'Satuan dihapus'], 200);

        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
