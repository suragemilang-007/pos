<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaksi\StoreTransaksiRequest;
use App\Models\Transaksi;
use App\Services\Transaction\TransaksiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    public function __construct(
        private readonly TransaksiService $transaksiService
    ) {}

    /**
     * POST /api/kasir/transaksi — SD-03
     *
     * Controller hanya orchestrate: validasi → service → 202.
     * Semua logika bisnis (kalkulasi, generate no, publish Kafka) ada di TransaksiService.
     *
     * Return 202 Accepted (bukan 200 OK) karena operasi bersifat async:
     * status PENDING → Consumer Kafka yang akan update ke COMPLETED/FAILED.
     * Frontend subscribe WebSocket untuk mendapat notifikasi hasil akhir.
     */
    public function store(StoreTransaksiRequest $request): JsonResponse
    {
        try {
            $v = $request->validated();

            $transaksi = $this->transaksiService->create(
                items:       $v['items'],
                customerId:  $v['customer_id'] ?? null,
                userId:      $request->input('auth_user_id'),
                bayar:       (float) $v['bayar'],
                metodeBayar: $v['metode_bayar']
            );

            return response()->json([
                'id_transaksi' => $transaksi->id,
                'no_transaksi' => $transaksi->no_transaksi,
                'status'       => 'PENDING',
                'message'      => 'Transaksi sedang diproses',
            ], 202);

        } catch (\RuntimeException $e) {
            // Kafka publish gagal (kafka-service down/timeout)
            return response()->json([
                'message' => 'Gagal mengirim transaksi ke antrian: ' . $e->getMessage(),
            ], 503);
        }
    }

    /**
     * GET /api/kasir/transaksi/{id} — SD-05
     *
     * Dipakai frontend sebelum kasir mengajukan retur — tampilkan item + satuan.
     * nama_satuan dan isi_satuan diambil dari detail_transaksi (snapshot),
     * BUKAN dari produk_satuan, agar riwayat tidak berubah jika satuan diedit.
     */
    public function show(int $id): JsonResponse
    {
        $transaksi = Transaksi::with([
            'details.produk:id,nama_produk',
            'customer:id,nama',
        ])->find($id);

        if (! $transaksi) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        // Cek kepemilikan: kasir hanya boleh lihat transaksi miliknya sendiri
        // Owner boleh lihat semua
        $authRole   = request()->input('auth_role_name');
        $authUserId = request()->input('auth_user_id');

        if ($authRole !== 'owner' && $transaksi->user_id !== $authUserId) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        return response()->json([
            'data' => [
                'id'                => $transaksi->id,
                'no_transaksi'      => $transaksi->no_transaksi,
                'status'            => $transaksi->status,
                'tanggal_transaksi' => $transaksi->tanggal_transaksi,
                'customer'          => $transaksi->customer
                    ? ['id' => $transaksi->customer->id, 'nama' => $transaksi->customer->nama]
                    : null,
                'items' => $transaksi->details->map(fn ($d) => [
                    'produk_id'    => $d->produk_id,
                    'nama_produk'  => $d->produk?->nama_produk,
                    // Snapshot — tampilkan satuan yang dipakai saat transaksi terjadi
                    'nama_satuan'  => $d->nama_satuan,
                    'isi_satuan'   => $d->isi_satuan,
                    'qty'          => $d->qty,
                    'harga_satuan' => $d->harga_satuan,
                    'subtotal'     => $d->subtotal,
                ])->values(),
            ],
        ], 200);
    }

    /**
     * GET /api/kasir/transaksi/{id}/struk — SD-04
     *
     * Struk hanya tersedia untuk transaksi COMPLETED — PENDING/FAILED
     * tidak punya data stok final yang valid untuk dicetak.
     *
     * Return HTML string di dalam JSON agar frontend bisa render ke iframe/popup
     * dan trigger window.print() tanpa navigasi halaman.
     */
    public function struk(int $id): JsonResponse
    {
        $transaksi = Transaksi::find($id);

        if (! $transaksi) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        if ($transaksi->status !== 'COMPLETED') {
            return response()->json([
                'message' => 'Struk hanya tersedia untuk transaksi yang sudah selesai (status: COMPLETED)',
            ], 400);
        }

        // Cek kepemilikan — sama dengan show()
        $authRole   = request()->input('auth_role_name');
        $authUserId = request()->input('auth_user_id');

        if ($authRole !== 'owner' && $transaksi->user_id !== $authUserId) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $html = $this->transaksiService->generateStruk($id);

        return response()->json([
            'data' => ['html' => $html],
        ], 200);
    }
}
