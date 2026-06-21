<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use Illuminate\Database\Seeder;

class MinyakGorengSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = KategoriProduk::where('nama_kategori', 'Minyak Goreng')->firstOrFail();

        // Semua produk: satuan_dasar=pcs, beli per dus
        // harga_beli_dasar = harga_beli_dus / jumlah_pcs_per_dus
        $produkList = [

            // ── ROSE BRAND ────────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'RB-MG2L',
                    'nama_produk'      => 'Rose Brand Minyak Goreng Pouch 2L',
                    'deskripsi'        => 'Minyak goreng Rose Brand kemasan pouch 2 liter, 1 dus = 6 pcs',
                    'harga_beli_dasar' => 43400.00, // 260.400 / 6
                    'stok_minimum'     => 6,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1, 'harga_beli' => 43400.00,  'harga_jual' => 46000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 6, 'harga_beli' => 260400.00, 'harga_jual' => 276000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'RB-MGCUP',
                    'nama_produk'      => 'Rose Brand Minyak Goreng Cup 220mL',
                    'deskripsi'        => 'Minyak goreng Rose Brand kemasan cup 220mL, 1 dus = 48 pcs',
                    'harga_beli_dasar' => 5000.00, // 240.000 / 48
                    'stok_minimum'     => 48,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 5000.00,   'harga_jual' => 5500.00,   'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 48, 'harga_beli' => 240000.00, 'harga_jual' => 264000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'RB-MG1L',
                    'nama_produk'      => 'Rose Brand Minyak Goreng Pouch 1L',
                    'deskripsi'        => 'Minyak goreng Rose Brand kemasan pouch 1 liter, 1 dus = 12 pcs',
                    'harga_beli_dasar' => 21700.00, // 260.400 / 12
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 21700.00,  'harga_jual' => 23000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 12, 'harga_beli' => 260400.00, 'harga_jual' => 276000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'RB-MG500',
                    'nama_produk'      => 'Rose Brand Minyak Goreng Pouch 500mL',
                    'deskripsi'        => 'Minyak goreng Rose Brand kemasan pouch 500mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 11250.00, // 270.000 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 11250.00,  'harga_jual' => 12000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 270000.00, 'harga_jual' => 288000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],

            // ── RIZKY ─────────────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'RZK-MG',
                    'nama_produk'      => 'Rizky Minyak Goreng Botol',
                    'deskripsi'        => 'Minyak goreng Rizky kemasan botol, 1 dus = 12 pcs',
                    'harga_beli_dasar' => 18083.33, // 217.000 / 12
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 18083.33, 'harga_jual' => 19000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 12, 'harga_beli' => 217000.00, 'harga_jual' => 228000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],

            // ── TROPICAL ──────────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'TRP-MG1L',
                    'nama_produk'      => 'Tropical Minyak Goreng Botol 1000mL',
                    'deskripsi'        => 'Minyak goreng Tropical kemasan botol 1000mL, 1 dus = 12 pcs',
                    'harga_beli_dasar' => 22900.00, // 274.800 / 12
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 22900.00,  'harga_jual' => 24000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 12, 'harga_beli' => 274800.00, 'harga_jual' => 288000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'TRP-MG2L',
                    'nama_produk'      => 'Tropical Minyak Goreng Botol 2000mL',
                    'deskripsi'        => 'Minyak goreng Tropical kemasan botol 2000mL, 1 dus = 6 pcs',
                    'harga_beli_dasar' => 45900.00, // 275.400 / 6
                    'stok_minimum'     => 6,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1, 'harga_beli' => 45900.00,  'harga_jual' => 48500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 6, 'harga_beli' => 275400.00, 'harga_jual' => 291000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'TRP-MG15',
                    'nama_produk'      => 'Tropical Minyak Goreng Botol 1.5L',
                    'deskripsi'        => 'Minyak goreng Tropical kemasan botol 1.5 liter, 1 dus = 8 pcs',
                    'harga_beli_dasar' => 33900.00, // 271.200 / 8
                    'stok_minimum'     => 8,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1, 'harga_beli' => 33900.00,  'harga_jual' => 36000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 8, 'harga_beli' => 271200.00, 'harga_jual' => 288000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],

            // ── BIMOLI ────────────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'BIM-MG620',
                    'nama_produk'      => 'Bimoli Minyak Goreng Botol 620mL',
                    'deskripsi'        => 'Minyak goreng Bimoli kemasan botol 620mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 15700.00, // 376.800 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 15700.00,  'harga_jual' => 16500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 376800.00, 'harga_jual' => 396000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'BIM-MG2L',
                    'nama_produk'      => 'Bimoli Minyak Goreng Botol 2000mL',
                    'deskripsi'        => 'Minyak goreng Bimoli kemasan botol 2000mL, 1 dus = 6 pcs',
                    'harga_beli_dasar' => 46900.00, // 281.400 / 6
                    'stok_minimum'     => 6,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1, 'harga_beli' => 46900.00,  'harga_jual' => 49500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 6, 'harga_beli' => 281400.00, 'harga_jual' => 297000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'BIM-RF2L',
                    'nama_produk'      => 'Bimoli Minyak Goreng Refill Pouch 2000mL',
                    'deskripsi'        => 'Minyak goreng Bimoli kemasan refill pouch 2000mL, 1 dus = 6 pcs',
                    'harga_beli_dasar' => 41900.00, // 251.400 / 6
                    'stok_minimum'     => 6,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1, 'harga_beli' => 41900.00,  'harga_jual' => 44500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 6, 'harga_beli' => 251400.00, 'harga_jual' => 267000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategori->id,
                    'kode_produk'      => 'BIM-RF1L',
                    'nama_produk'      => 'Bimoli Minyak Goreng Refill Pouch 1000mL',
                    'deskripsi'        => 'Minyak goreng Bimoli kemasan refill pouch 1000mL, 1 dus = 12 pcs',
                    'harga_beli_dasar' => 21500.00, // 258.000 / 12
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 21500.00,  'harga_jual' => 22500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 12, 'harga_beli' => 258000.00, 'harga_jual' => 270000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
        ];

        foreach ($produkList as $item) {
            $item['produk']['qty_stok'] = $item['produk']['stok_minimum'];
            $produk = Produk::create($item['produk']);

            foreach ($item['satuan'] as $satuan) {
                ProdukSatuan::create(array_merge($satuan, ['produk_id' => $produk->id]));
            }
        }
    }
}
