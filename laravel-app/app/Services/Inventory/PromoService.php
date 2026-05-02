<?php

namespace App\Services\Inventory;

use App\Models\DetailTransaksi;
use App\Models\ProdukPromo;
use Illuminate\Support\Collection;

/**
 * PromoService — logika bisnis manajemen promo produk.
 *
 * PENTING (AD-18 + CLAUDE.md §5.6):
 * Kalkulasi promo dilakukan di FRONTEND (JavaScript), BUKAN di sini.
 * Service ini hanya menyimpan konfigurasi promo dan mengambilnya untuk
 * dikembalikan ke kasir via GET /produk/{kode}/stok.
 * Frontend yang menentukan item bonus dan nilai diskon berdasarkan data promo.
 */
class PromoService
{
    // ─────────────────────────────────────────────────────────────────────────
    // READ
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ambil semua promo dengan filter opsional.
     * filters: { produk_id?: int, status?: 'aktif'|'nonaktif', nama?: string }
     */
    public function getAll(array $filters = []): Collection
    {
        $query = ProdukPromo::with([
            'produk:id,nama_produk,kode_produk',
            'satuanSyarat:id,nama_satuan,isi',
            'bonusProduk:id,nama_produk',
            'bonusSatuan:id,nama_satuan,isi',
        ]);

        if (! empty($filters['produk_id'])) {
            $query->where('produk_id', (int) $filters['produk_id']);
        }

        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'aktif' ? 1 : 0);
        }

        if (! empty($filters['nama'])) {
            $query->where('nama_promo', 'like', '%' . $filters['nama'] . '%');
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Ambil semua promo milik satu produk (aktif maupun tidak) — untuk halaman kelola promo.
     */
    public function getByProduk(int $produkId): Collection
    {
        return ProdukPromo::with([
            'satuanSyarat:id,nama_satuan,isi',
            'bonusProduk:id,nama_produk',
            'bonusSatuan:id,nama_satuan,isi',
        ])
            ->where('produk_id', $produkId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Ambil promo yang berlaku hari ini untuk satu produk.
     * Dipakai endpoint GET /produk/{kode}/stok agar kasir tahu promo yang aktif.
     *
     * Query sesuai CLASS_DIAGRAM.md PromoService::getPromoAktif():
     *   WHERE is_active = 1
     *     AND (berlaku_dari IS NULL OR berlaku_dari <= CURDATE())
     *     AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
     */
    public function getPromoAktif(int $produkId): Collection
    {
        return ProdukPromo::with([
            'satuanSyarat:id,nama_satuan,isi',
            'bonusProduk:id,nama_produk',
            'bonusSatuan:id,nama_satuan,isi',
        ])
            ->where('produk_id', $produkId)
            ->aktif()   // scope di ProdukPromo model
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Buat promo baru dengan validasi lintas-field sesuai tipe_promo.
     *
     * Aturan per tipe (dari ERD.md + CLASS_DIAGRAM.md):
     *  bonus_produk_sama : bonus_qty wajib; bonus_produk_id HARUS null
     *                      (produk bonus = produk yang sama, tidak boleh ditentukan)
     *  bonus_produk_lain : bonus_qty, bonus_produk_id, bonus_satuan_id semua wajib
     *                      (produk bonus berbeda — kasir perlu tahu produknya apa)
     *  diskon_persen     : diskon_nilai wajib, antara 0.01 sampai 100
     *                      (0 tidak masuk akal, > 100 bukan persentase valid)
     *  diskon_nominal    : diskon_nilai wajib, harus > 0
     *
     * @throws \InvalidArgumentException kode 422 jika validasi lintas-field gagal
     */
    public function create(array $data): ProdukPromo
    {
        $this->validateTipePromo($data);

        return ProdukPromo::create($data);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Update data promo. Jika tipe_promo ikut diubah, validasi ulang lintas-field.
     *
     * @throws \InvalidArgumentException kode 422 jika validasi lintas-field gagal
     * @throws \RuntimeException         kode 404 jika promo tidak ditemukan
     */
    public function update(int $id, array $data): bool
    {
        $promo = ProdukPromo::find($id);

        if (! $promo) {
            throw new \RuntimeException('Promo tidak ditemukan', 404);
        }

        // Merge data lama dengan data baru agar validateTipePromo bisa lihat konteks penuh
        $merged = array_merge($promo->toArray(), $data);
        $this->validateTipePromo($merged);

        return (bool) $promo->update($data);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TOGGLE AKTIF
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Balik nilai is_active — aktifkan jika sedang nonaktif, nonaktifkan jika aktif.
     * Lebih aman dari SET is_active=1 karena tidak butuh tahu state saat ini.
     *
     * @throws \RuntimeException kode 404 jika promo tidak ditemukan
     */
    public function toggle(int $id): bool
    {
        $promo = ProdukPromo::find($id);

        if (! $promo) {
            throw new \RuntimeException('Promo tidak ditemukan', 404);
        }

        $promo->is_active = ! $promo->is_active;
        return $promo->save();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hapus promo jika belum pernah dipakai.
     *
     * Mengapa tidak boleh hapus promo yang sudah dipakai?
     * - detail_transaksi.promo_id adalah FK ke produk_promo
     * - ON DELETE SET NULL akan nullify promo_id di riwayat transaksi
     * - Data riwayat kehilangan informasi promo apa yang diterapkan — tidak bisa audit
     * - Lebih baik nonaktifkan saja (is_active=0) agar konfigurasi promo tetap terjaga
     *
     * @throws \RuntimeException kode 422 jika promo sudah dipakai di transaksi
     * @throws \RuntimeException kode 404 jika promo tidak ditemukan
     */
    public function delete(int $id): bool
    {
        $promo = ProdukPromo::find($id);

        if (! $promo) {
            throw new \RuntimeException('Promo tidak ditemukan', 404);
        }

        if ($this->hasBeenUsed($id)) {
            throw new \RuntimeException(
                'Promo tidak dapat dihapus karena sudah digunakan dalam transaksi. Nonaktifkan saja.',
                422
            );
        }

        return (bool) $promo->delete();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CEK PENGGUNAAN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cek apakah promo sudah pernah diterapkan di detail_transaksi.
     * COUNT(*) lebih cepat dari exists() karena tidak perlu load model.
     */
    public function hasBeenUsed(int $promoId): bool
    {
        return DetailTransaksi::where('promo_id', $promoId)->exists();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validasi lintas-field berdasarkan tipe_promo.
     * Dipanggil oleh create() dan update().
     *
     * @throws \InvalidArgumentException kode 422 dengan pesan spesifik per aturan yang dilanggar
     */
    private function validateTipePromo(array $data): void
    {
        $tipe        = $data['tipe_promo']    ?? null;
        $bonusQty    = $data['bonus_qty']     ?? null;
        $bonusProduk = $data['bonus_produk_id'] ?? null;
        $bonusSatuan = $data['bonus_satuan_id'] ?? null;
        $diskon      = $data['diskon_nilai']  ?? null;
        $dari        = $data['berlaku_dari']  ?? null;
        $sampai      = $data['berlaku_sampai'] ?? null;

        match ($tipe) {
            'bonus_produk_sama' => $this->validateBonusProdukSama($bonusQty, $bonusProduk),
            'bonus_produk_lain' => $this->validateBonusProdukLain($bonusQty, $bonusProduk, $bonusSatuan),
            'diskon_persen'     => $this->validateDiskonPersen($diskon),
            'diskon_nominal'    => $this->validateDiskonNominal($diskon),
            default             => throw new \InvalidArgumentException(
                "tipe_promo '{$tipe}' tidak valid", 422
            ),
        };

        // Validasi periode berlaku — berlaku_dari tidak boleh setelah berlaku_sampai
        if ($dari !== null && $sampai !== null && $dari > $sampai) {
            throw new \InvalidArgumentException(
                'berlaku_dari tidak boleh melebihi berlaku_sampai', 422
            );
        }
    }

    private function validateBonusProdukSama(mixed $bonusQty, mixed $bonusProduk): void
    {
        if (empty($bonusQty) || (int) $bonusQty < 1) {
            throw new \InvalidArgumentException(
                'bonus_qty wajib diisi dan harus >= 1 untuk tipe bonus_produk_sama', 422
            );
        }
        // bonus_produk_id HARUS null — produk bonus = produk yang sama dengan yang dibeli
        if ($bonusProduk !== null) {
            throw new \InvalidArgumentException(
                'bonus_produk_id harus kosong untuk tipe bonus_produk_sama (produk bonus sama dengan produk yang dibeli)', 422
            );
        }
    }

    private function validateBonusProdukLain(mixed $bonusQty, mixed $bonusProduk, mixed $bonusSatuan): void
    {
        if (empty($bonusQty) || (int) $bonusQty < 1) {
            throw new \InvalidArgumentException(
                'bonus_qty wajib diisi dan harus >= 1 untuk tipe bonus_produk_lain', 422
            );
        }
        if (empty($bonusProduk)) {
            throw new \InvalidArgumentException(
                'bonus_produk_id wajib diisi untuk tipe bonus_produk_lain', 422
            );
        }
        if (empty($bonusSatuan)) {
            throw new \InvalidArgumentException(
                'bonus_satuan_id wajib diisi untuk tipe bonus_produk_lain', 422
            );
        }
    }

    private function validateDiskonPersen(mixed $diskon): void
    {
        if ($diskon === null || $diskon === '') {
            throw new \InvalidArgumentException(
                'diskon_nilai wajib diisi untuk tipe diskon_persen', 422
            );
        }
        $nilai = (float) $diskon;
        if ($nilai <= 0 || $nilai > 100) {
            throw new \InvalidArgumentException(
                'diskon_nilai untuk diskon_persen harus antara 0.01 hingga 100', 422
            );
        }
    }

    private function validateDiskonNominal(mixed $diskon): void
    {
        if ($diskon === null || $diskon === '') {
            throw new \InvalidArgumentException(
                'diskon_nilai wajib diisi untuk tipe diskon_nominal', 422
            );
        }
        if ((float) $diskon <= 0) {
            throw new \InvalidArgumentException(
                'diskon_nilai untuk diskon_nominal harus lebih dari 0', 422
            );
        }
    }
}
