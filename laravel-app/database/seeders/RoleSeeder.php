<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nama_role'   => 'owner',
                // Owner dapat semua izin — array string sebagai gate permission
                'permissions' => [
                    'transaksi', 'retur_penjualan',
                    'produk', 'kategori', 'stok',
                    'customer', 'supplier',
                    'pembelian', 'retur_pembelian',
                    'laporan', 'user_management',
                ],
            ],
            [
                'nama_role'   => 'kasir',
                'permissions' => [
                    'transaksi', 'retur_penjualan',
                    'customer', 'stok',
                ],
            ],
            [
                'nama_role'   => 'admin_gudang',
                'permissions' => [
                    'produk', 'kategori', 'stok',
                    'supplier',
                    'pembelian', 'retur_pembelian',
                ],
            ],
        ];

        foreach ($roles as $data) {
            Role::create($data);
        }
    }
}
