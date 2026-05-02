<?php

namespace App\Http\Controllers;

use App\Services\Transaction\VoidTransaksiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoidTransaksiController extends Controller
{
    public function __construct(
        private readonly VoidTransaksiService $voidService
    ) {}

    /**
     * POST /api/kasir/transaksi/{id}/void — SD-17
     *
     * Alur: validasi → VoidTransaksiService::create() → 202 Accepted
     * Return 202 (bukan 200) karena operasi async: Consumer Kafka yang
     * UPDATE stok + status='VOIDED'. Frontend subscribe WebSocket untuk hasil akhir.
     *
     * Validasi keterangan:
     * - required jika alasan='lainnya' (alasan generik wajib penjelasan)
     * - nullable untuk alasan spesifik lain (sudah jelas tanpa keterangan)
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'alasan' => [
                'required',
                'string',
                'in:kesalahan_input,barang_rusak_saat_transaksi,permintaan_pembeli,lainnya',
            ],
            'keterangan' => [
                'required_if:alasan,lainnya',
                'nullable',
                'string',
                'max:500',
            ],
        ], [
            'alasan.required' => 'Alasan void wajib diisi',
            'alasan.in'       => 'Alasan tidak valid. Pilih: kesalahan_input, barang_rusak_saat_transaksi, permintaan_pembeli, atau lainnya',
            'keterangan.required_if' => 'Keterangan wajib diisi jika alasan adalah "lainnya"',
            'keterangan.max'         => 'Keterangan maksimal 500 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $void = $this->voidService->create(
                transaksiId: $id,
                userId:      (int) $request->input('auth_user_id'),
                alasan:      $request->input('alasan'),
                keterangan:  $request->input('keterangan'),
            );

            return response()->json([
                'id_void'      => $void->id,
                'transaksi_id' => $void->transaksi_id,
                'status'       => 'PENDING',
                'message'      => 'Void sedang diproses',
            ], 202);

        } catch (\RuntimeException $e) {
            // Kode 400: tidak eligible (dari checkVoidEligibility race condition guard)
            if ($e->getCode() === 400) {
                return response()->json(['message' => $e->getMessage()], 400);
            }

            // Kode lain (misal 503): Kafka producer gagal kirim ke Node.js
            return response()->json([
                'message' => 'Gagal mengirim void ke antrian: ' . $e->getMessage(),
            ], 503);
        }
    }
}
