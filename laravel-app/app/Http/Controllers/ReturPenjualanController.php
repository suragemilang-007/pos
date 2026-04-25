<?php

namespace App\Http\Controllers;

use App\Http\Requests\Retur\StoreReturPenjualanRequest;
use App\Services\Transaction\ReturPenjualanService;
use Illuminate\Http\JsonResponse;

class ReturPenjualanController extends Controller
{
    public function __construct(
        private readonly ReturPenjualanService $returService
    ) {}

    /**
     * POST /api/kasir/retur-penjualan — SD-05
     *
     * Validasi bisnis dilakukan di ReturPenjualanService::validate() sebelum INSERT.
     * Error validasi bisnis (bukan validasi field) return 400, bukan 422.
     * Return 202 karena Consumer yang menyelesaikan proses (update stok + status).
     */
    public function store(StoreReturPenjualanRequest $request): JsonResponse
    {
        try {
            $v = $request->validated();

            $retur = $this->returService->create(
                transaksiId: $v['transaksi_id'],
                itemsRetur:  $v['items_retur'],
                alasan:      $v['alasan'],
                userId:      $request->input('auth_user_id')
            );

            return response()->json([
                'id_retur' => $retur->id,
                'no_retur' => $retur->no_retur,
                'status'   => 'PENDING',
                'message'  => 'Retur penjualan sedang diproses',
            ], 202);

        } catch (\RuntimeException $e) {
            // Kode 400 = validasi bisnis gagal (transaksi belum COMPLETED / qty_retur berlebih)
            // Kode 503 = Kafka tidak bisa dihubungi
            $statusCode = $e->getCode() === 400 ? 400 : 503;

            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
