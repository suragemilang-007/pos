<?php

namespace App\Http\Controllers;

use App\Http\Requests\Retur\StoreReturPembelianRequest;
use App\Services\Purchasing\ReturPembelianService;
use Illuminate\Http\JsonResponse;

class ReturPembelianController extends Controller
{
    public function __construct(
        private readonly ReturPembelianService $returService
    ) {}

    /**
     * POST /api/gudang/retur-pembelian — SD-10
     *
     * Validasi bisnis (pembelian COMPLETED + qty cukup) dilakukan di Service.
     * Return 202 karena Consumer yang menyelesaikan proses (kurangi stok + status).
     */
    public function store(StoreReturPembelianRequest $request): JsonResponse
    {
        try {
            $v = $request->validated();

            $retur = $this->returService->create(
                pembelianId: $v['pembelian_id'],
                itemsRetur:  $v['items_retur'],
                alasan:      $v['alasan'],
                userId:      $request->input('auth_user_id')
            );

            return response()->json([
                'id_retur' => $retur->id,
                'no_retur' => $retur->no_retur,
                'status'   => 'PENDING',
                'message'  => 'Retur pembelian sedang diproses',
            ], 202);

        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 503;

            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
