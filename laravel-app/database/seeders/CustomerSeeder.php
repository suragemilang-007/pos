<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'nama'          => 'Yogi',
                'no_telp'       => '081234567801',
                'alamat'        => 'Jl. Mawar No. 12, Yogyakarta',
                'tipe_customer' => 'reguler',
                'status'        => 'aktif',
            ],
            [
                'nama'          => 'Nur',
                'no_telp'       => '082234567802',
                'alamat'        => 'Jl. Melati No. 5, Sleman',
                'tipe_customer' => 'reguler',
                'status'        => 'aktif',
            ],
            [
                'nama'          => 'Siti',
                'no_telp'       => '083134567803',
                'alamat'        => 'Jl. Anggrek No. 8, Bantul',
                'tipe_customer' => 'reguler',
                'status'        => 'aktif',
            ],
            [
                'nama'          => 'Tarsono',
                'no_telp'       => '085678901234',
                'alamat'        => 'Jl. Pasar Baru No. 21, Kulonprogo',
                'tipe_customer' => 'grosir',
                'status'        => 'aktif',
            ],
            [
                'nama'          => 'Slamet',
                'no_telp'       => '087789012345',
                'alamat'        => 'Jl. Raya Godean No. 45, Sleman',
                'tipe_customer' => 'grosir',
                'status'        => 'aktif',
            ],
            [
                'nama'          => 'Gocci',
                'no_telp'       => '089567890126',
                'alamat'        => 'Jl. Kenanga No. 3, Yogyakarta',
                'tipe_customer' => 'reguler',
                'status'        => 'aktif',
            ],
            [
                'nama'          => 'Yani',
                'no_telp'       => '081287654327',
                'alamat'        => 'Jl. Dahlia No. 17, Bantul',
                'tipe_customer' => 'reguler',
                'status'        => 'aktif',
            ],
            [
                'nama'          => 'Ipah',
                'no_telp'       => '085298765438',
                'alamat'        => 'Jl. Wates No. 9, Kulonprogo',
                'tipe_customer' => 'reguler',
                'status'        => 'aktif',
            ],
            [
                'nama'          => 'Supri',
                'no_telp'       => '082112345679',
                'alamat'        => 'Jl. Palagan No. 33, Sleman',
                'tipe_customer' => 'grosir',
                'status'        => 'aktif',
            ],
            [
                'nama'          => 'Prima',
                'no_telp'       => '089898765430',
                'alamat'        => 'Jl. Imogiri No. 7, Bantul',
                'tipe_customer' => 'grosir',
                'status'        => 'aktif',
            ],
        ];

        foreach ($customers as $data) {
            Customer::create($data);
        }
    }
}
