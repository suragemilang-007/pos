<?php

namespace App\Services\Transaction;

use App\Models\ReturPenjualan;
use App\Models\Transaksi;
use App\Models\VoidTransaksi;
use App\Services\Kafka\KafkaProducerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VoidTransaksiService — logika bisnis pembatalan transaksi dalam batas waktu.
 *
 * Void BERBEDA dengan Retur:
 * - Void : dilakukan saat pembeli masih di kasir, < 30 menit, batalkan SEMUA item
 * - Retur: dilakukan belakangan, bisa parsial, ada proses persetujuan
 *
 * Alur: checkVoidEligibility → create → publishTransactionVoid (Kafka) → Consumer update stok
 */
class VoidTransaksiService
{
    public function __construct(
        private readonly KafkaProducerService $kafkaProducer
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // 1. CHECK ELIGIBILITY
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cek apakah transaksi memenuhi semua syarat untuk di-void.
     *
     * Validasi dilakukan BERURUTAN — stop di kondisi pertama yang gagal.
     * Urutan ini penting karena kondisi sebelumnya adalah prerequisite kondisi berikutnya:
     * - Tidak masuk akal cek waktu jika status bukan COMPLETED
     * - Tidak masuk akal cek riwayat void jika sudah lewat batas waktu
     *
     * @return array { eligible: bool, reason: ?string, sisa_detik: ?int }
     */
    public function checkVoidEligibility(int $transaksiId): array
    {
        $transaksi = Transaksi::find($transaksiId);

        if (! $transaksi) {
            return [
                'eligible'   => false,
                'reason'     => 'Transaksi tidak ditemukan',
                'sisa_detik' => null,
            ];
        }

        // ── Kondisi 1: status harus COMPLETED ─────────────────────────────────
        // PENDING = masih diproses Kafka, FAILED = gagal, CANCELLED/VOIDED = sudah tidak aktif
        if ($transaksi->status !== 'COMPLETED') {
            return [
                'eligible'   => false,
                'reason'     => 'Transaksi tidak dapat di-void (status bukan COMPLETED)',
                'sisa_detik' => null,
            ];
        }

        // ── Kondisi 2: dalam batas waktu (dari env VOID_TIME_LIMIT_MINUTES) ────
        // Hitung sisa waktu sekarang — digunakan untuk response DAN return value
        $sisaDetik = $this->getSisaWaktuVoid($transaksi);

        if ($sisaDetik <= 0) {
            return [
                'eligible'   => false,
                'reason'     => 'Batas waktu void telah habis (maksimal ' . config('services.void_time_limit') . ' menit)',
                'sisa_detik' => 0,
            ];
        }

        // ── Kondisi 3: belum pernah di-void ───────────────────────────────────
        // UNIQUE KEY di DB (uq_void_transaksi) juga menjamin ini, tetapi cek aplikatif
        // memberi pesan yang rapi sebelum DB melempar IntegrityConstraintViolationException
        if (VoidTransaksi::where('transaksi_id', $transaksiId)->exists()) {
            return [
                'eligible'   => false,
                'reason'     => 'Transaksi sudah pernah di-void sebelumnya',
                'sisa_detik' => $sisaDetik, // masih dalam window — informasi berguna untuk log
            ];
        }

        // ── Kondisi 4: belum ada retur penjualan ──────────────────────────────
        // Void dan Retur tidak boleh dilakukan bersamaan untuk transaksi yang sama
        // karena keduanya mengembalikan stok — akan ada double-return
        if (ReturPenjualan::where('transaksi_id', $transaksiId)->exists()) {
            return [
                'eligible'   => false,
                'reason'     => 'Transaksi sudah memiliki retur, gunakan menu Retur',
                'sisa_detik' => $sisaDetik,
            ];
        }

        return [
            'eligible'   => true,
            'reason'     => null,
            'sisa_detik' => $sisaDetik,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. GET SISA WAKTU VOID
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hitung sisa detik untuk void.
     * = (VOID_TIME_LIMIT_MINUTES × 60) − selisih detik sejak transaksi dibuat
     *
     * Return 0 jika sudah expired — tidak return negatif agar frontend aman pakai nilai ini.
     */
    public function getSisaWaktuVoid(Transaksi $transaksi): int
    {
        $limitDetik   = config('services.void_time_limit') * 60;
        $detikBerlalu = (int) $transaksi->created_at->diffInSeconds(now());

        return max(0, $limitDetik - $detikBerlalu);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. CREATE VOID
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Proses pengajuan void transaksi.
     *
     * Alur:
     *  1. checkVoidEligibility() ulang — race condition guard (penjelasan di bawah)
     *  2. Hitung batas_waktu = transaksi.created_at + VOID_TIME_LIMIT_MINUTES
     *  3. INSERT void_transaksi status='PENDING' (dalam DB transaction)
     *  4. Publish ke Kafka — Consumer yang UPDATE stok & status='VOIDED'
     *
     * ── Race Condition Guard ─────────────────────────────────────────────────
     * Mengapa validasi diulang di sini padahal sudah dilakukan di GET sebelumnya?
     *
     * Skenario tanpa guard:
     *   T=0ms  Kasir A: GET /transaksi/5  → can_void=true ✓
     *   T=0ms  Kasir B: GET /transaksi/5  → can_void=true ✓  (terminal berbeda)
     *   T=100ms Kasir A: POST /void       → berhasil INSERT void_transaksi id=1
     *   T=120ms Kasir B: POST /void       → lolos checkEligibility lama, lanjut INSERT
     *                                       → DB lempar IntegrityConstraintViolationException
     *                                       → 500 Error (tidak terhandle = log noise + bad UX)
     *
     * Skenario dengan guard:
     *   T=120ms Kasir B: POST /void       → checkVoidEligibility() BARU dijalankan
     *                                       → kondisi 3: VoidTransaksi::exists() = TRUE
     *                                       → lempar RuntimeException kode 400
     *                                       → Controller return 400 "Sudah pernah di-void"
     *
     * Guard ini tidak sepenuhnya mengeliminasi race condition
     * (ada window antara check dan INSERT), tetapi membuat kasus umum tertangani dengan rapi.
     * Kasus ekstrim (dua request submit dalam < 1ms) masih tertangani oleh UNIQUE constraint DB.
     * ────────────────────────────────────────────────────────────────────────
     *
     * @throws \RuntimeException kode 400 jika tidak eligible
     * @throws \RuntimeException kode 503 jika Kafka gagal
     */
    public function create(
        int     $transaksiId,
        int     $userId,
        string  $alasan,
        ?string $keterangan
    ): VoidTransaksi {
        // Race condition guard — validasi ulang sebelum INSERT
        $eligibility = $this->checkVoidEligibility($transaksiId);

        if (! $eligibility['eligible']) {
            throw new \RuntimeException($eligibility['reason'], 400);
        }

        $transaksi = Transaksi::findOrFail($transaksiId);

        // batas_waktu dihitung dari created_at transaksi, bukan dari sekarang —
        // konsisten dengan checkVoidEligibility (window mulai dari transaksi dibuat)
        $batasWaktu = $transaksi->created_at->copy()->addMinutes(
            config('services.void_time_limit')
        );

        $void = DB::transaction(function () use ($transaksiId, $userId, $alasan, $keterangan, $batasWaktu) {
            return VoidTransaksi::create([
                'transaksi_id'     => $transaksiId,
                'transaksi_baru_id'=> null,
                'user_id'          => $userId,
                'alasan'           => $alasan,
                'keterangan'       => $keterangan,
                'status'           => 'PENDING',
                'batas_waktu'      => $batasWaktu,
            ]);
        });

        // Tidak publish ke Kafka di sini — menunggu persetujuan owner.
        // Kafka akan dipublish di approve() setelah owner konfirmasi.
        Log::info('[VOID TRANSAKSI] Pengajuan diterima, menunggu persetujuan owner', [
            'void_id'      => $void->id,
            'transaksi_id' => $transaksiId,
            'user_id'      => $userId,
            'alasan'       => $alasan,
            'batas_waktu'  => $batasWaktu->toIso8601String(),
        ]);

        // Notifikasi real-time ke semua owner yang membuka halaman void-approval.
        // Best-effort: tidak throw jika WS gagal — kasir tetap mendapat response 202.
        $this->kafkaProducer->pushWebSocket('void-requests', [
            'event'          => 'void.submitted',
            'voidId'         => $void->id,
            'transaksiId'    => $transaksiId,
            'noTransaksi'    => $transaksi->no_transaksi,
            'totalTransaksi' => (float) $transaksi->total,
            'kasirId'        => $userId,
            'alasan'         => $alasan,
            'batasWaktu'     => $batasWaktu->toIso8601String(),
            'sisaDetik'      => max(0, now()->diffInSeconds($batasWaktu, false)),
        ]);

        return $void;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. APPROVE VOID (owner)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Owner menyetujui pengajuan void dari kasir.
     *
     * Validasi:
     * - void_transaksi harus PENDING
     * - batas_waktu belum lewat
     *
     * Jika lolos → publish ke Kafka → Consumer yang UPDATE stok + status VOIDED.
     *
     * @throws \RuntimeException kode 400 jika tidak eligible
     * @throws \RuntimeException kode 503 jika Kafka gagal
     */
    public function approve(int $voidId): VoidTransaksi
    {
        $void = VoidTransaksi::with('transaksi.details')->find($voidId);

        if (! $void) {
            throw new \RuntimeException('Pengajuan void tidak ditemukan', 404);
        }

        if ($void->status !== 'PENDING') {
            throw new \RuntimeException(
                "Pengajuan void sudah berstatus {$void->status}, tidak dapat disetujui",
                400
            );
        }

        // Cek batas waktu — owner harus approve sebelum window berakhir
        if (now()->isAfter($void->batas_waktu)) {
            // Otomatis tolak jika sudah kadaluarsa
            $void->update(['status' => 'REJECTED']);
            throw new \RuntimeException(
                'Batas waktu void telah habis. Pengajuan otomatis ditolak.',
                400
            );
        }

        // Publish ke Kafka — Consumer (voidConsumer.js) yang:
        //   1. TAMBAH stok untuk semua item (qty × isiSatuan)
        //   2. UPDATE transaksi SET status='VOIDED'
        //   3. UPDATE void_transaksi SET status='COMPLETED'
        //   4. Push WebSocket 'void.completed' + 'stock.updated'
        $this->kafkaProducer->publishTransactionVoid($void);

        Log::info('[VOID TRANSAKSI] Disetujui owner, event dikirim ke Kafka', [
            'void_id'      => $void->id,
            'transaksi_id' => $void->transaksi_id,
        ]);

        return $void;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. REJECT VOID (owner)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Owner menolak pengajuan void dari kasir.
     *
     * Sinkron (tidak via Kafka) — hanya UPDATE status + push WS langsung.
     * Tidak ada perubahan stok karena void tidak dieksekusi.
     *
     * @throws \RuntimeException kode 400 jika sudah tidak PENDING
     */
    public function reject(int $voidId): VoidTransaksi
    {
        $void = VoidTransaksi::find($voidId);

        if (! $void) {
            throw new \RuntimeException('Pengajuan void tidak ditemukan', 404);
        }

        if ($void->status !== 'PENDING') {
            throw new \RuntimeException(
                "Pengajuan void sudah berstatus {$void->status}",
                400
            );
        }

        $void->update(['status' => 'REJECTED']);

        // Push WS langsung ke kasir yang mengajukan (bukan via Kafka)
        $this->kafkaProducer->pushWebSocket("user-{$void->user_id}", [
            'event'        => 'void.rejected',
            'voidId'       => $void->id,
            'transaksiId'  => $void->transaksi_id,
            'status'       => 'REJECTED',
            'message'      => 'Pengajuan void ditolak oleh pemilik',
        ]);

        Log::info('[VOID TRANSAKSI] Ditolak owner', [
            'void_id'      => $void->id,
            'transaksi_id' => $void->transaksi_id,
            'kasir_id'     => $void->user_id,
        ]);

        return $void;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. LIST PENDING
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ambil semua pengajuan void yang menunggu persetujuan owner.
     * Urutkan dari yang paling mendekati batas_waktu agar owner prioritaskan yang urgent.
     */
    public function listPending(): \Illuminate\Database\Eloquent\Collection
    {
        return VoidTransaksi::with(['transaksi', 'user:id,nama'])
            ->where('status', 'PENDING')
            ->orderBy('batas_waktu', 'asc')
            ->get();
    }
}
