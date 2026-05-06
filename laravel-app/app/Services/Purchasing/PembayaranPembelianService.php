<?php

namespace App\Services\Purchasing;

use App\Models\Pembelian;
use App\Models\PembayaranPembelian;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * PembayaranPembelianService — logika pencatatan pembayaran hutang dagang.
 *
 * ── MENGAPA TIDAK VIA KAFKA? ─────────────────────────────────────────────────
 *
 * Semua operasi yang mengubah stok (transaksi, pembelian, retur, void) harus
 * async via Kafka karena stok adalah resource bersama yang harus diupdate secara
 * serializable di Consumer — mencegah race condition antar kasir.
 *
 * Pembayaran hutang TIDAK mengubah stok sama sekali — hanya:
 *   1. INSERT record di pembayaran_pembelian (audit pembayaran)
 *   2. UPDATE sisa_hutang di tabel pembelian
 *   3. UPDATE status_pembayaran (lunas / sebagian)
 *
 * Operasi murni keuangan ini aman dilakukan sinkron karena:
 * - Tidak ada resource stok yang di-contend oleh banyak pengguna bersamaan
 * - Admin gudang yang bayar hutang biasanya hanya satu orang
 * - Konsistensi langsung (200 OK) lebih baik daripada 202 + WS untuk transaksi
 *   keuangan — admin perlu konfirmasi instan bahwa pembayaran tercatat
 * - Event-driven untuk keuangan menambah kompleksitas tanpa manfaat nyata
 *
 * Referensi: CLAUDE.md §7.3 — "Pembayaran Hutang = SINKRON (tidak via Kafka)"
 * ─────────────────────────────────────────────────────────────────────────────
 */
class PembayaranPembelianService
{
    // ─────────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Catat satu cicilan / pelunasan hutang untuk PO tertentu.
     *
     * Alur (sesuai AD-19 + SD-19):
     *   1. Ambil PO, validasi masih bisa dibayar
     *   2. Validasi jumlah tidak melebihi sisa hutang
     *   3. DB::transaction → INSERT + UPDATE sisa_hutang + UPDATE status_pembayaran
     *   4. Return PembayaranPembelian dengan relasi pembelian
     *
     * @throws \RuntimeException        kode 404 jika PO tidak ditemukan
     * @throws \RuntimeException        kode 400 jika PO sudah lunas
     * @throws \Illuminate\Validation\ValidationException kode 422 jika jumlah > sisa_hutang
     */
    public function create(
        int     $pembelianId,
        int     $userId,
        float   $jumlah,
        string  $metode,
        string  $tanggalBayar,
        ?string $catatan = null
    ): PembayaranPembelian {
        // ── Langkah 1: Ambil PO, cek status_pembayaran ───────────────────────
        $pembelian = Pembelian::find($pembelianId);

        if (! $pembelian) {
            throw new \RuntimeException('Purchase Order tidak ditemukan', 404);
        }

        if ($pembelian->status_pembayaran === 'lunas') {
            throw new \RuntimeException(
                "PO {$pembelian->no_pembelian} sudah lunas, tidak perlu pembayaran lagi",
                400
            );
        }

        // ── Langkah 2: Validasi jumlah > 0 ───────────────────────────────────
        if ($jumlah <= 0) {
            throw ValidationException::withMessages([
                'jumlah' => ['Jumlah pembayaran harus lebih dari 0'],
            ]);
        }

        // ── Langkah 3: Validasi jumlah tidak melebihi sisa hutang ─────────────
        $sisaHutang = (float) $pembelian->sisa_hutang;

        if ($jumlah > $sisaHutang) {
            // Format Rp untuk pesan error yang informatif bagi admin
            $maks = 'Rp ' . number_format($sisaHutang, 0, ',', '.');

            throw ValidationException::withMessages([
                'jumlah' => ["Jumlah melebihi sisa hutang (maks: {$maks})"],
            ]);
        }

        // ── Langkah 4: DB::transaction — semua atau tidak sama sekali ─────────
        $pembayaran = DB::transaction(function () use (
            $pembelianId, $userId, $jumlah, $metode, $tanggalBayar, $catatan,
            $pembelian, $sisaHutang
        ) {
            // 4a. INSERT record pembayaran
            $record = PembayaranPembelian::create([
                'pembelian_id'  => $pembelianId,
                'user_id'       => $userId,
                'jumlah'        => $jumlah,
                'metode'        => $metode,
                'tanggal_bayar' => $tanggalBayar,
                'catatan'       => $catatan,
            ]);

            // 4b. Hitung sisa hutang baru
            // Menggunakan round() untuk menghindari floating-point imprecision
            // pada DECIMAL(10,2) — misal: 100000.00 - 99999.99 = 0.01 bukan 0.009999...
            $sisaBaru = round($sisaHutang - $jumlah, 2);

            // 4c & 4d. Tentukan status_pembayaran baru berdasarkan sisa
            if ($sisaBaru <= 0) {
                // Pelunasan penuh — sisa_hutang = 0 (bukan negatif, clamped)
                $pembelian->sisa_hutang       = 0.00;
                $pembelian->status_pembayaran = 'lunas';
            } else {
                // Cicilan — sisa masih ada
                $pembelian->sisa_hutang       = $sisaBaru;
                $pembelian->status_pembayaran = 'sebagian';
            }

            $pembelian->save();

            Log::info('[PEMBAYARAN HUTANG] Dicatat', [
                'pembelian_id'      => $pembelianId,
                'no_pembelian'      => $pembelian->no_pembelian,
                'user_id'           => $userId,
                'jumlah'            => $jumlah,
                'sisa_sebelumnya'   => $sisaHutang,
                'sisa_baru'         => $pembelian->sisa_hutang,
                'status_baru'       => $pembelian->status_pembayaran,
            ]);

            return $record;
        });

        // ── Langkah 5: Load relasi pembelian (fresh dari DB, bukan cache) ─────
        $pembayaran->load('pembelian.supplier');

        return $pembayaran;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // READ
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ambil riwayat semua pembayaran untuk satu PO.
     * Include nama user yang mencatat (untuk tampilan audit trail di UI).
     *
     * @param  int  $pembelianId
     * @return \Illuminate\Support\Collection<PembayaranPembelian>
     */
    public function getRiwayatPembayaran(int $pembelianId): Collection
    {
        return PembayaranPembelian::with('user:id,nama')
            ->where('pembelian_id', $pembelianId)
            ->orderBy('tanggal_bayar')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Hitung total yang sudah dibayar untuk satu PO.
     * = SUM(jumlah) dari semua record pembayaran PO ini.
     *
     * Lebih akurat daripada (total - sisa_hutang) karena sisa_hutang
     * mungkin belum diupdate jika ada race condition (meski tidak mungkin
     * karena pembayaran sinkron, ini tetap lebih defensif).
     */
    public function getTotalDibayar(int $pembelianId): float
    {
        return (float) PembayaranPembelian::where('pembelian_id', $pembelianId)
            ->sum('jumlah');
    }
}
