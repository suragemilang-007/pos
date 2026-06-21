<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan wajib dijaga: Role → User (FK role_id) → KategoriProduk (independen)
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            KategoriProdukSeeder::class,
            ProdukSeeder::class,          // FK kategori_id → kategori_produk (Mie & Pasta)
            MinumanRentengSeeder::class,  // FK kategori_id → kategori_produk (Minuman Renteng)
            CustomerSeeder::class,        // Data pelanggan toko
            AirMineralSeeder::class,        // FK kategori_id → Minuman Botol, Gelas Cup, Galon
            NutrisariPopIceSeeder::class,  // FK kategori_id → Minuman Renteng
            MinyakGorengSeeder::class,    // FK kategori_id → Minyak Goreng
            RokokSeeder::class,          // FK kategori_id → Rokok
        ]);
    }
}
