<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use Illuminate\Database\Seeder;

class KategoriProdukSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['nama_kategori' => 'Minuman', 'deskripsi' => 'Air mineral, minuman kemasan, sirup, teh, kopi'],
            ['nama_kategori' => 'Sembako', 'deskripsi' => 'Beras, minyak goreng, gula, tepung, telur'],
            ['nama_kategori' => 'Kebersihan', 'deskripsi' => 'Sabun, detergen, pembersih lantai, sampo'],
            ['nama_kategori' => 'Snack', 'deskripsi' => 'Keripik, biskuit, wafer, permen, coklat'],
            ['nama_kategori' => 'Rokok', 'deskripsi' => 'Rokok kretek, rokok putih, rokok elektrik'],
            ['nama_kategori' => 'Bumbu Dapur', 'deskripsi' => 'Kecap, saus, terasi, bumbu instan, garam, merica'],
            ['nama_kategori' => 'Mie & Pasta', 'deskripsi' => 'Mie instan, bihun, soun, makaroni, spaghetti'],
            ['nama_kategori' => 'Susu & Dairy', 'deskripsi' => 'Susu UHT, susu kental manis, keju, mentega, yogurt'],
            ['nama_kategori' => 'Perawatan Diri', 'deskripsi' => 'Pasta gigi, sikat gigi, deodoran, lotion, pembalut'],
            ['nama_kategori' => 'Obat & Kesehatan', 'deskripsi' => 'Obat bebas, vitamin, plester, masker, hand sanitizer'],
            ['nama_kategori' => 'Makanan Kaleng', 'deskripsi' => 'Ikan kaleng, kornet, sarden, buah kaleng, kacang kaleng'],
            ['nama_kategori' => 'Alat Tulis', 'deskripsi' => 'Pulpen, buku tulis, pensil, penggaris, staples'],
            ['nama_kategori' => 'Kebutuhan Bayi', 'deskripsi' => 'Popok bayi, susu formula, tisu basah bayi, sabun bayi'],
            ['nama_kategori' => 'Minuman Botol', 'deskripsi' => 'Air mineral botol, minuman berenergi botol, jus botol, teh botol'],
            ['nama_kategori' => 'Minuman Renteng', 'deskripsi' => 'Minuman kemasan sachet renteng, teh kotak renteng, susu renteng'],
            ['nama_kategori' => 'Minuman Gelas Cup', 'deskripsi' => 'Air mineral gelas cup, teh gelas, jus gelas, minuman serbuk gelas'],
            ['nama_kategori' => 'Minuman Galon', 'deskripsi' => 'Air mineral galon isi ulang, galon bermerek, air galon dalam kemasan'],
            ['nama_kategori' => 'Minyak Goreng', 'deskripsi' => 'Minyak goreng sawit, minyak goreng kemasan botol, refill, dan jerigen'],
        ];

        foreach ($kategori as $data) {
            KategoriProduk::create($data);
        }
    }
}