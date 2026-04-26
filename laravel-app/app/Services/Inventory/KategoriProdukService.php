<?php

namespace App\Services\Inventory;

use App\Models\KategoriProduk;
use Illuminate\Database\Eloquent\Collection;

/**
 * KategoriProdukService — CRUD kategori produk (data referensi, tidak lewat Kafka).
 * Delete dengan proteksi: kategori yang masih dipakai produk (aktif maupun nonaktif) DILARANG dihapus.
 */
class KategoriProdukService
{
    /**
     * Semua kategori urut A-Z, lengkap dengan jumlah produk (withCount).
     * Dipakai di halaman manajemen kategori (SD-16).
     */
    public function getAll(): Collection
    {
        // Alias 'produk as jumlah_produk' agar nama field sesuai kontrak SD-16
        return KategoriProduk::withCount(['produk as jumlah_produk'])
            ->orderBy('nama_kategori')
            ->get();
    }

    public function create(array $data): KategoriProduk
    {
        return KategoriProduk::create([
            'nama_kategori' => $data['nama_kategori'],
            'deskripsi'     => $data['deskripsi'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $kategori = KategoriProduk::findOrFail($id);

        return $kategori->update([
            'nama_kategori' => $data['nama_kategori'],
            'deskripsi'     => array_key_exists('deskripsi', $data)
                ? $data['deskripsi']
                : $kategori->deskripsi,
        ]);
    }

    /**
     * Hapus kategori.
     * Cek produk_count dulu — tolak jika masih ada produk (aktif maupun nonaktif).
     * Sesuai ERD: "Jika kategori masih dipakai produk aktif: DILARANG hapus."
     * dan CLASS_DIAGRAM: "Cek COUNT(*) di produk WHERE kategori_id=?"
     *
     * @throws \RuntimeException  422 jika masih ada produk yang memakai kategori ini
     */
    public function delete(int $id): bool
    {
        $kategori = KategoriProduk::withCount('produk')->findOrFail($id);

        if ($kategori->produk_count > 0) {
            throw new \RuntimeException(
                "Kategori tidak dapat dihapus karena masih digunakan oleh {$kategori->produk_count} produk",
                422
            );
        }

        return (bool) $kategori->delete();
    }

    /**
     * Cek apakah kategori masih digunakan produk manapun (aktif maupun nonaktif).
     */
    public function hasProduk(int $id): bool
    {
        return KategoriProduk::findOrFail($id)
            ->produk()
            ->exists();
    }
}
