<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use Illuminate\Database\Seeder;

class MinumanRentengSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = KategoriProduk::where('nama_kategori', 'Minuman Renteng')->firstOrFail();

        // Struktur: harga_beli_dasar = harga_beli_dus / isi_renteng_per_dus
        // Satuan dasar: renteng | Satuan beli: dus
        $produkList = [
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'KA-KSM',
                    'nama_produk'      => 'Kapal Api Kopi Bubuk Special Mix',
                    'deskripsi'        => 'Kopi bubuk special mix kapal api kemasan renteng',
                    'harga_beli_dasar' => 16000.00, // 192.000 / 12
                    'qty_stok'         => 0,
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 16000.00,  'harga_jual' => 17000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 12, 'harga_beli' => 192000.00, 'harga_jual' => 204000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'KA-KS60',
                    'nama_produk'      => 'Kapal Api Kopi Bubuk Special 60g',
                    'deskripsi'        => 'Kopi bubuk kapal api special kemasan 60g renteng',
                    'harga_beli_dasar' => 85000.00, // 425.000 / 5
                    'qty_stok'         => 0,
                    'stok_minimum'     => 5,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1, 'harga_beli' => 85000.00,  'harga_jual' => 90000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 5, 'harga_beli' => 425000.00, 'harga_jual' => 450000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'KA-KS30',
                    'nama_produk'      => 'Kapal Api Kopi Bubuk Special 30g',
                    'deskripsi'        => 'Kopi bubuk kapal api special kemasan 30g renteng',
                    'harga_beli_dasar' => 42500.00, // 425.000 / 10
                    'qty_stok'         => 0,
                    'stok_minimum'     => 10,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 42500.00,  'harga_jual' => 45000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 10, 'harga_beli' => 425000.00, 'harga_jual' => 450000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'KA-KSU',
                    'nama_produk'      => 'Kapal Api Kopi Susu',
                    'deskripsi'        => 'Kopi susu kapal api kemasan renteng',
                    'harga_beli_dasar' => 16500.00, // 198.000 / 12
                    'qty_stok'         => 0,
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 16500.00,  'harga_jual' => 17500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 12, 'harga_beli' => 198000.00, 'harga_jual' => 210000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'ABC-KGS',
                    'nama_produk'      => 'ABC Kopi Instant Kopi Gula Susu',
                    'deskripsi'        => 'Kopi instant ABC 3in1 kopi, gula, dan susu kemasan renteng',
                    'harga_beli_dasar' => 16000.00, // 192.000 / 12
                    'qty_stok'         => 0,
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 16000.00,  'harga_jual' => 17000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 12, 'harga_beli' => 192000.00, 'harga_jual' => 204000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'ABC-MOC',
                    'nama_produk'      => 'ABC Kopi Susu Coklat Mocca',
                    'deskripsi'        => 'Kopi susu coklat mocca ABC kemasan renteng',
                    'harga_beli_dasar' => 15833.33, // 190.000 / 12
                    'qty_stok'         => 0,
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 15833.33,  'harga_jual' => 16500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 12, 'harga_beli' => 190000.00, 'harga_jual' => 198000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'ABC-KPG',
                    'nama_produk'      => 'ABC Kopi Plus Gula',
                    'deskripsi'        => 'Kopi plus gula ABC kemasan renteng tanpa susu',
                    'harga_beli_dasar' => 7250.00, // 87.000 / 12
                    'qty_stok'         => 0,
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 7250.00,  'harga_jual' => 7750.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 12, 'harga_beli' => 87000.00, 'harga_jual' => 93000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'ICF-CM3',
                    'nama_produk'      => 'Indocafe Coffee Mix 3 In 1',
                    'deskripsi'        => 'Kopi mix 3in1 Indocafe kemasan renteng',
                    'harga_beli_dasar' => 15500.00, // 775.000 / 50
                    'qty_stok'         => 0,
                    'stok_minimum'     => 50,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 15500.00,  'harga_jual' => 16500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 50, 'harga_beli' => 775000.00, 'harga_jual' => 825000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'FRS-KKS',
                    'nama_produk'      => 'Fresco Kopi Krim Susu',
                    'deskripsi'        => 'Kopi krim susu Fresco kemasan renteng',
                    'harga_beli_dasar' => 15000.00, // 150.000 / 10
                    'qty_stok'         => 0,
                    'stok_minimum'     => 10,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 15000.00,  'harga_jual' => 16000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 10, 'harga_beli' => 150000.00, 'harga_jual' => 160000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'FRS-KCP',
                    'nama_produk'      => 'Fresco Kopi Cappuccino',
                    'deskripsi'        => 'Kopi cappuccino Fresco kemasan renteng',
                    'harga_beli_dasar' => 19500.00, // 195.000 / 10
                    'qty_stok'         => 0,
                    'stok_minimum'     => 10,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 19500.00,  'harga_jual' => 20500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 10, 'harga_beli' => 195000.00, 'harga_jual' => 205000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'TOP-KGS',
                    'nama_produk'      => 'Top Coffee Kopi Gula Susu',
                    'deskripsi'        => 'Kopi gula susu Top Coffee kemasan renteng',
                    'harga_beli_dasar' => 15000.00, // 150.000 / 10
                    'qty_stok'         => 0,
                    'stok_minimum'     => 10,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 15000.00,  'harga_jual' => 16000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 10, 'harga_beli' => 150000.00, 'harga_jual' => 160000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'TOP-KGA',
                    'nama_produk'      => 'Top Coffee Kopi Gula Aren',
                    'deskripsi'        => 'Kopi gula aren Top Coffee kemasan renteng',
                    'harga_beli_dasar' => 17083.33, // 205.000 / 12
                    'qty_stok'         => 0,
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 17083.33,  'harga_jual' => 18000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 12, 'harga_beli' => 205000.00, 'harga_jual' => 216000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'LWK-WK',
                    'nama_produk'      => 'Luwak White Koffie',
                    'deskripsi'        => 'Kopi putih Luwak White Koffie kemasan renteng',
                    'harga_beli_dasar' => 14333.33, // 172.000 / 12
                    'qty_stok'         => 0,
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'renteng',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'renteng', 'isi' => 1,  'harga_beli' => 14333.33,  'harga_jual' => 15000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus',     'isi' => 12, 'harga_beli' => 172000.00, 'harga_jual' => 180000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
        ];

        foreach ($produkList as $item) {
            // qty_stok awal disamakan dengan stok_minimum
            $item['produk']['qty_stok'] = $item['produk']['stok_minimum'];
            $produk = Produk::create($item['produk']);

            foreach ($item['satuan'] as $satuan) {
                ProdukSatuan::create(array_merge($satuan, ['produk_id' => $produk->id]));
            }
        }
    }
}
