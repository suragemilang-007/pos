<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventory\StorePenyesuaianStokRequest;
use App\Services\Inventory\PenyesuaianStokService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * PenyesuaianStokController — tipis, hanya orchestrate ke PenyesuaianStokService.
 *
 * store() return 202 Accepted (bukan 200) karena operasi async via Kafka:
 * Consumer yang UPDATE qty_stok — bukan Controller ini.
 */
class PenyesuaianStokController extends Controller
{
    public function __construct(
        private readonly PenyesuaianStokService $service
    ) {}

    /**
     * GET /api/gudang/penyesuaian-stok — SD-20
     * Riwayat penyesuaian stok dengan filter opsional.
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->getAll(
            filters: $request->only(['produk_id', 'tipe', 'dari', 'sampai']),
            limit:   min((int) $request->input('limit', 20), 100)
        );

        $data = collect($paginator->items())->map(fn ($p) => [
            'id'               => $p->id,
            'produk'           => $p->produk ? [
                'id'          => $p->produk->id,
                'kode_produk' => $p->produk->kode_produk,
                'nama_produk' => $p->produk->nama_produk,
                'satuan_dasar'=> $p->produk->satuan_dasar,
            ] : null,
            'tipe'             => $p->tipe,
            'qty_satuan_dasar' => $p->qty_satuan_dasar,
            'keterangan'       => $p->keterangan,
            'status'           => $p->status,
            'created_at'       => $p->created_at?->toIso8601String(),
            'nama_user'        => $p->user?->nama,
        ]);

        return response()->json([
            'data'       => $data->values(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ], 200);
    }

    /**
     * POST /api/gudang/penyesuaian-stok — SD-20
     *
     * Return 202 Accepted — Consumer Kafka yang UPDATE qty_stok dan status='COMPLETED'.
     * ValidationException dari Service (qty > stok) dibiarkan propagate → 422 otomatis.
     */
    public function store(StorePenyesuaianStokRequest $request): JsonResponse
    {
        try {
            $penyesuaian = $this->service->create(
                data:   $request->validated(),
                userId: (int) $request->input('auth_user_id')
            );

            return response()->json([
                'id_penyesuaian' => $penyesuaian->id,
                'status'         => 'PENDING',
                'message'        => 'Penyesuaian stok sedang diproses',
            ], 202);

        } catch (\RuntimeException $e) {
            $status = $e->getCode() === 404 ? 404 : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
        // ValidationException (qty > stok) propagate → Laravel return 422 otomatis
    }
}
