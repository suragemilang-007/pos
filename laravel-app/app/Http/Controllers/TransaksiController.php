<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaksi\StoreTransaksiRequest;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Services\Transaction\TransaksiService;
use App\Services\Transaction\VoidTransaksiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function __construct(
        private readonly TransaksiService $transaksiService,
        private readonly VoidTransaksiService $voidService
    ) {
    }

    /**
     * GET /api/kasir/transaksi/cari?q={no_transaksi atau id} — dicari untuk keperluan retur.
     * Mendukung pencarian by no_transaksi (TRX-YYYYMMDD-XXXXX) atau numeric id.
     * Hanya transaksi COMPLETED yang dikembalikan — sesuai validasi AD-05.
     */
    public function cariTransaksi(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (empty($q)) {
            return response()->json(['message' => 'Parameter pencarian wajib diisi'], 422);
        }

        // Cari by numeric id ATAU by no_transaksi (format TRX-...)
        $transaksi = is_numeric($q)
            ? Transaksi::with(['details.produk:id,nama_produk', 'user:id,nama'])
                ->find((int) $q)
            : Transaksi::with(['details.produk:id,nama_produk', 'user:id,nama'])
                ->where('no_transaksi', $q)
                ->first();

        if (!$transaksi) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        if ($transaksi->status !== 'COMPLETED') {
            return response()->json([
                'message' => "Transaksi berstatus {$transaksi->status} — hanya transaksi COMPLETED yang bisa diretur",
            ], 400);
        }

        return response()->json([
            'data' => [
                'id' => $transaksi->id,
                'no_transaksi' => $transaksi->no_transaksi,
                'tanggal_transaksi' => $transaksi->tanggal_transaksi,
                'total' => (float) $transaksi->total,
                'kasir' => $transaksi->user?->nama,
                'items' => $transaksi->details->map(fn($d) => [
                    'produk_id' => $d->produk_id,
                    'nama_produk' => $d->produk?->nama_produk,
                    'nama_satuan' => $d->nama_satuan,
                    'isi_satuan' => $d->isi_satuan,
                    'qty' => $d->qty,
                    'harga_satuan' => (float) $d->harga_satuan,
                    'subtotal' => (float) $d->subtotal,
                ])->values(),
            ],
        ]);
    }

    /**
     * GET /api/kasir/ringkasan — data untuk dashboard kasir.
     * Kasir hanya melihat transaksi miliknya sendiri (user_id = auth_user_id).
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $userId = $request->input('auth_user_id');
        $today = now()->toDateString();

        // Transaksi kasir ini hari ini (COMPLETED saja — yang pending/failed tidak dihitung)
        $transaksiHariIni = Transaksi::where('user_id', $userId)
            ->whereDate('tanggal_transaksi', $today)
            ->where('status', 'COMPLETED');

        // 5 transaksi terakhir kasir ini (semua status agar kasir tahu ada yang pending)
        $terakhir = Transaksi::where('user_id', $userId)
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'no_transaksi', 'tanggal_transaksi', 'total', 'metode_bayar', 'status']);

        // Jam transaksi terakhir (COMPLETED)
        $transaksiTerakhir = Transaksi::where('user_id', $userId)
            ->where('status', 'COMPLETED')
            ->latest('tanggal_transaksi')
            ->first(['tanggal_transaksi']);

        // Stok rendah: produk aktif dengan qty_stok <= stok_minimum
        $stokRendah = Produk::where('status', 'aktif')
            ->whereColumn('qty_stok', '<=', 'stok_minimum')
            ->count();

        return response()->json([
            'data' => [
                'transaksi_hari_ini' => $transaksiHariIni->count(),
                'omzet_hari_ini' => (float) $transaksiHariIni->sum('total'),
                'stok_rendah' => $stokRendah,
                'transaksi_terakhir_jam' => $transaksiTerakhir
                    ? $transaksiTerakhir->tanggal_transaksi->format('H:i')
                    : null,
                'transaksi_terakhir' => $terakhir->map(fn($t) => [
                    'id' => $t->id,
                    'no_transaksi' => $t->no_transaksi,
                    'waktu' => $t->tanggal_transaksi?->format('H:i'),
                    'total' => (float) $t->total,
                    'metode_bayar' => $t->metode_bayar,
                    'status' => $t->status,
                ])->values(),
            ],
        ]);
    }

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
                items: $v['items'],
                customerId: $v['customer_id'] ?? null,
                userId: $request->input('auth_user_id'),
                bayar: (float) $v['bayar'],
                metodeBayar: $v['metode_bayar']
            );

            return response()->json([
                'id_transaksi' => $transaksi->id,
                'no_transaksi' => $transaksi->no_transaksi,
                'status' => 'PENDING',
                'message' => 'Transaksi sedang diproses',
            ], 202);

        } catch (\RuntimeException $e) {
            // Kafka publish gagal (kafka-service down/timeout)
            return response()->json([
                'message' => 'Gagal mengirim transaksi ke antrian: ' . $e->getMessage(),
            ], 503);
        }
    }

    /**
     * GET /api/kasir/transaksi/{id} — SD-05 + SD-17
     *
     * Dipakai untuk dua keperluan:
     * 1. Sebelum retur — kasir lihat item + satuan snapshot
     * 2. Sebelum void  — frontend cek can_void + sisa_waktu_void sebelum tampilkan tombol
     *
     * Tiga field tambahan untuk void (SD-17):
     * - can_void       : true jika semua 4 kondisi eligibility terpenuhi
     * - sisa_waktu_void: sisa detik dalam window void (0 jika expired)
     * - void_reason    : string alasan jika can_void=false, null jika can_void=true
     */
    public function show(int $id): JsonResponse
    {
        $transaksi = Transaksi::with([
            'details.produk:id,nama_produk',
            'customer:id,nama',
        ])->find($id);

        if (!$transaksi) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        // Cek kepemilikan: kasir hanya boleh lihat transaksi miliknya sendiri
        // Owner boleh lihat semua
        $authRole = request()->input('auth_role_name');
        $authUserId = (int) request()->input('auth_user_id');

        if ($authRole !== 'owner' && $transaksi->user_id !== $authUserId) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        // Cek eligibility void — dijalankan untuk semua request show() karena
        // frontend membutuhkan info ini untuk menampilkan/menyembunyikan tombol void
        $voidInfo = $this->voidService->checkVoidEligibility($id);

        return response()->json([
            'data' => [
                'id' => $transaksi->id,
                'no_transaksi' => $transaksi->no_transaksi,
                'status' => $transaksi->status,
                'tanggal_transaksi' => $transaksi->tanggal_transaksi,
                'total' => (float) $transaksi->total,
                'customer' => $transaksi->customer
                    ? ['id' => $transaksi->customer->id, 'nama' => $transaksi->customer->nama]
                    : null,
                'items' => $transaksi->details->map(fn($d) => [
                    'produk_id' => $d->produk_id,
                    'nama_produk' => $d->produk?->nama_produk,
                    // Snapshot — tampilkan satuan yang dipakai saat transaksi terjadi
                    'nama_satuan' => $d->nama_satuan,
                    'isi_satuan' => $d->isi_satuan,
                    'qty' => $d->qty,
                    'harga_satuan' => (float) $d->harga_satuan,
                    'subtotal' => (float) $d->subtotal,
                    'is_bonus' => (bool) $d->is_bonus,
                ])->values(),
                // ── SD-17: Info void ─────────────────────────────────────────
                'can_void' => $voidInfo['eligible'],
                'sisa_waktu_void' => $voidInfo['sisa_detik'] ?? 0,
                'void_reason' => $voidInfo['reason'],
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

        if (!$transaksi) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        if ($transaksi->status !== 'COMPLETED') {
            return response()->json([
                'message' => 'Struk hanya tersedia untuk transaksi yang sudah selesai (status: COMPLETED)',
            ], 400);
        }

        // Cek kepemilikan — sama dengan show()
        $authRole = request()->input('auth_role_name');
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