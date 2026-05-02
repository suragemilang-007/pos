<?php

namespace App\Http\Controllers;

use App\Http\Requests\Promo\StorePromoRequest;
use App\Services\Inventory\PromoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PromoController — tipis, hanya orchestrate ke PromoService.
 * Semua logika bisnis (validasi tipe_promo, cek penggunaan) ada di PromoService.
 */
class PromoController extends Controller
{
    public function __construct(
        private readonly PromoService $promoService
    ) {}

    /**
     * GET /api/gudang/promo?produk_id=&status=&nama= — SD-18
     */
    public function index(Request $request): JsonResponse
    {
        $promos = $this->promoService->getAll([
            'produk_id' => $request->input('produk_id'),
            'status'    => $request->input('status'),
            'nama'      => $request->input('nama'),
        ]);

        return response()->json(['data' => $promos], 200);
    }

    /**
     * POST /api/gudang/promo — SD-18
     *
     * FormRequest menangani validasi field-level.
     * PromoService::create() menangani validasi lintas-field (tipe_promo constraints).
     * Return 201 sesuai konvensi CLAUDE.md (data dibuat).
     */
    public function store(StorePromoRequest $request): JsonResponse
    {
        try {
            $promo = $this->promoService->create($request->validated());

            return response()->json([
                'data'    => $promo->load(['produk:id,nama_produk', 'satuanSyarat:id,nama_satuan']),
                'message' => 'Promo berhasil ditambahkan',
            ], 201);

        } catch (\InvalidArgumentException $e) {
            // Validasi lintas-field gagal di PromoService (tipe_promo constraints)
            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => ['tipe_promo' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * PUT /api/gudang/promo/{id} — SD-18
     *
     * StorePromoRequest juga dipakai untuk update (PUT = semua field dikirim ulang).
     */
    public function update(StorePromoRequest $request, int $id): JsonResponse
    {
        try {
            $this->promoService->update($id, $request->validated());

            return response()->json([
                'message' => 'Promo berhasil diperbarui',
            ], 200);

        } catch (\RuntimeException $e) {
            $status = $e->getCode() === 404 ? 404 : 400;
            return response()->json(['message' => $e->getMessage()], $status);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => ['tipe_promo' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * PATCH /api/gudang/promo/{id}/toggle — SD-18
     * Balik is_active tanpa perlu tahu state sebelumnya.
     */
    public function toggle(int $id): JsonResponse
    {
        try {
            $this->promoService->toggle($id);

            $isActive = \App\Models\ProdukPromo::find($id)?->is_active;

            return response()->json([
                'data'    => ['id' => $id, 'is_active' => (bool) $isActive],
                'message' => $isActive ? 'Promo diaktifkan' : 'Promo dinonaktifkan',
            ], 200);

        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * DELETE /api/gudang/promo/{id} — SD-18
     * Return 422 jika promo sudah pernah digunakan di transaksi (sesuai SD-18).
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->promoService->delete($id);

            return response()->json(['message' => 'Promo berhasil dihapus'], 200);

        } catch (\RuntimeException $e) {
            $status = match ($e->getCode()) {
                404 => 404,
                422 => 422,
                default => 400,
            };
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
}
