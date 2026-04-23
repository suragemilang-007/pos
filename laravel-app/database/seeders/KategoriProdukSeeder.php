<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use Illuminate\Database\Seeder;

class KategoriProdukSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['nama_kategori' => 'Minuman',    'deskripsi' => 'Air mineral, minuman kemasan, sirup, teh, kopi'],
            ['nama_kategori' => 'Sembako',    'deskripsi' => 'Beras, minyak goreng, gula, tepung, telur'],
            ['nama_kategori' => 'Kebersihan', 'deskripsi' => 'Sabun, detergen, pembersih lantai, sampo'],
            ['nama_kategori' => 'Snack',      'deskripsi' => 'Keripik, biskuit, wafer, permen, coklat'],
            ['nama_kategori' => 'Rokok',      'deskripsi' => 'Rokok kretek, rokok putih, rokok elektrik'],
        ];

        foreach ($kategori as $data) {
            KategoriProduk::create($data);
        }
    }
}
