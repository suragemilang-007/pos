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
        ]);
    }
}
