<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchasing\StorePembayaranRequest;
use App\Services\Purchasing\PembayaranPembelianService;
use Illuminate\Http\JsonResponse;

/**
 * PembayaranPembelianController — tipis, hanya orchestrate ke Service.
 *
 * Return HTTP 200 (bukan 202) karena pembayaran hutang adalah operasi SINKRON:
 * - Tidak ada perubahan stok → tidak perlu Kafka
 * - Admin perlu konfirmasi instan bahwa pembayaran tercatat
 * - Lihat penjelasan lengkap di PembayaranPembelianService
 */
class PembayaranPembelianController extends Controller
{
    public function __construct(
        private readonly PembayaranPembelianService $service
    ) {}

    /**
     * POST /api/gudang/pembelian/{id}/bayar — SD-19
     *
     * Validasi field-level ditangani StorePembayaranRequest.
     * Validasi bisnis (sisa_hutang, status lunas) ditangani PembayaranPembelianService.
     * ValidationException dari Service dibiarkan propagate → Laravel return 422 otomatis.
     */
    public function bayar(StorePembayaranRequest $request, int $id): JsonResponse
    {
        try {
            $v = $request->validated();

            $pembayaran = $this->service->create(
                pembelianId:  $id,
                userId:       (int) $request->input('auth_user_id'),
                jumlah:       (float) $v['jumlah'],
                metode:       $v['metode'],
                tanggalBayar: $v['tanggal_bayar'],
                catatan:      $v['catatan'] ?? null,
            );

            $po = $pembayaran->pembelian;

            return response()->json([
                'message'                => 'Pembayaran berhasil dicatat',
                'sisa_hutang_baru'       => (float) $po->sisa_hutang,
                'status_pembayaran_baru' => $po->status_pembayaran,
            ], 200);

        } catch (\RuntimeException $e) {
            $status = match ($e->getCode()) {
                404 => 404,
                400 => 400,
                default => 500,
            };
            return response()->json(['message' => $e->getMessage()], $status);
        }
        // \Illuminate\Validation\ValidationException (jumlah > sisa_hutang) dibiarkan
        // propagate — Laravel handler otomatis return 422 dengan format errors standar
    }
}
