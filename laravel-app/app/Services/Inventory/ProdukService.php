<?php

namespace App\Services\Inventory;

use App\Models\Produk;

class ProdukService
{
    /**
     * Cari produk aktif berdasarkan kode barcode, beserta semua satuannya.
     *
     * Eager load satuanList() (bukan satuan()) karena relasi ini sudah terurut:
     * is_default DESC, isi ASC — urutan yang dipakai endpoint cekStok dan form kasir.
     * Dengan eager load di sini, controller tidak perlu query tambahan.
     *
     * @return Produk|null  null jika kode tidak ditemukan atau produk nonaktif
     */
    public function getByKode(string $kode): ?Produk
    {
        return Produk::with('satuanList')
            ->where('kode_produk', $kode)
            ->where('status', 'aktif')
            ->first();
    }

    /**
     * Cek apakah stok mencukupi untuk qty yang diminta (dalam satuan_dasar).
     *
     * Parameter $qty di sini adalah jumlah dalam satuan_dasar.
     * Konversi dari satuan kasir ke satuan_dasar (×isi_satuan) dilakukan
     * di pemanggil sebelum memanggil method ini.
     */
    public function isStokCukup(int $produkId, int $qty): bool
    {
        $produk = Produk::find($produkId);

        if (! $produk) return false;

        return $produk->qty_stok >= $qty;
    }

    /**
     * Cek apakah stok sudah di bawah atau sama dengan batas minimum.
     * Dipakai untuk highlight baris di tampilan stok kasir.
     */
    public function isStokRendah(int $produkId): bool
    {
        $produk = Produk::select('qty_stok', 'stok_minimum')
            ->find($produkId);

        if (! $produk) return false;

        return $produk->qty_stok <= $produk->stok_minimum;
    }
}
