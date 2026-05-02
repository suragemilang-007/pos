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
                'transaksi_baru_id'=> null,              // diisi setelah kasir buat transaksi koreksi
                'user_id'          => $userId,
                'alasan'           => $alasan,
                'keterangan'       => $keterangan,
                'status'           => 'PENDING',
                'batas_waktu'      => $batasWaktu,
            ]);
        });

        // Load relasi yang dibutuhkan publishTransactionVoid:
        // transaksi.details berisi semua item — method publish yang filter is_bonus=false
        $void->load('transaksi.details');

        // Publish ke Kafka — Consumer (voidConsumer.js) yang:
        //   1. TAMBAH stok untuk item non-bonus (qty × isiSatuan)
        //   2. UPDATE transaksi SET status='VOIDED'
        //   3. UPDATE void_transaksi SET status='COMPLETED'
        //   4. Push WebSocket 'void.completed' + 'stock.updated'
        $this->kafkaProducer->publishTransactionVoid($void);

        Log::info('[VOID TRANSAKSI] Created', [
            'void_id'      => $void->id,
            'transaksi_id' => $transaksiId,
            'user_id'      => $userId,
            'alasan'       => $alasan,
            'batas_waktu'  => $batasWaktu->toIso8601String(),
        ]);

        return $void;
    }
}
