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

    // ─────────────────────────────────────────────────────────────────────────
    // KASIR — ajukan void (membuat PENDING, menunggu owner)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/kasir/transaksi/{id}/void — SD-17
     *
     * Kasir mengajukan permintaan void. Status = PENDING sampai owner approve.
     * Return 202: operasi async — Kafka dipublish oleh approve(), bukan di sini.
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
            'alasan.required'        => 'Alasan void wajib diisi',
            'alasan.in'              => 'Alasan tidak valid',
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
                'message'      => 'Pengajuan void diterima. Menunggu persetujuan pemilik.',
            ], 202);

        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();
            if ($code === 400) {
                return response()->json(['message' => $e->getMessage()], 400);
            }
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OWNER — kelola pengajuan void
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/owner/void/pending
     * Daftar pengajuan void yang menunggu persetujuan (status=PENDING).
     */
    public function listPending(): JsonResponse
    {
        $voids = $this->voidService->listPending();

        $data = $voids->map(fn ($v) => [
            'id'             => $v->id,
            'transaksi_id'   => $v->transaksi_id,
            'no_transaksi'   => $v->transaksi?->no_transaksi,
            'total_transaksi'=> (float) ($v->transaksi?->total ?? 0),
            'tanggal'        => $v->transaksi?->tanggal_transaksi,
            'kasir'          => $v->user?->nama,
            'alasan'         => $v->alasan,
            'keterangan'     => $v->keterangan,
            'batas_waktu'    => $v->batas_waktu,
            'sisa_detik'     => max(0, now()->diffInSeconds($v->batas_waktu, false)),
            'created_at'     => $v->created_at,
        ]);

        return response()->json(['data' => $data]);
    }

    /**
     * POST /api/owner/void/{id}/approve
     * Owner menyetujui pengajuan → publish ke Kafka → Consumer proses void.
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $void = $this->voidService->approve($id);

            return response()->json([
                'id_void'      => $void->id,
                'transaksi_id' => $void->transaksi_id,
                'status'       => 'PENDING',
                'message'      => 'Void disetujui. Stok sedang diproses.',
            ], 202);

        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();
            if ($code === 400 || $code === 404) {
                return response()->json(['message' => $e->getMessage()], $code);
            }
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }

    /**
     * POST /api/owner/void/{id}/reject
     * Owner menolak pengajuan → DB update REJECTED + push WS ke kasir.
     */
    public function reject(int $id): JsonResponse
    {
        try {
            $void = $this->voidService->reject($id);

            return response()->json([
                'id_void'      => $void->id,
                'transaksi_id' => $void->transaksi_id,
                'status'       => 'REJECTED',
                'message'      => 'Pengajuan void ditolak.',
            ]);

        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();
            if ($code === 400 || $code === 404) {
                return response()->json(['message' => $e->getMessage()], $code);
            }
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
