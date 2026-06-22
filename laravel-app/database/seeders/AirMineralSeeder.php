<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use Illuminate\Database\Seeder;

class AirMineralSeeder extends Seeder
{
    use HasRandomStock;

    public function run(): void
    {
        $botol  = KategoriProduk::where('nama_kategori', 'Minuman Botol')->firstOrFail();
        $gelas  = KategoriProduk::where('nama_kategori', 'Minuman Gelas Cup')->firstOrFail();
        $galon  = KategoriProduk::where('nama_kategori', 'Minuman Galon')->firstOrFail();

        // Catatan pola satuan:
        // - Umumnya: satuan_dasar=pcs, beli per dus (1 dus = N pcs)
        // - satuan_dasar=dus + jumlah>1 : satuan terkecil adalah "dus", pembelian per "karton" (1 karton = N dus)
        // - satuan_dasar=dus + jumlah=1 : hanya 1 satuan, beli dan stok per dus
        // - satuan_dasar=pcs + jumlah=1 : hanya 1 satuan (produk satuan tunggal seperti galon)
        $produkList = [

            // ── AQUA ──────────────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'AQU-B1500',
                    'nama_produk'      => 'Aqua Air Mineral Botol 1500mL',
                    'deskripsi'        => 'Air mineral Aqua kemasan botol 1500mL, 1 dus = 12 pcs',
                    'harga_beli_dasar' => 4575.00, // 54.900 / 12
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 4575.00,  'harga_jual' => 5000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 12, 'harga_beli' => 54900.00, 'harga_jual' => 60000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'AQU-B330',
                    'nama_produk'      => 'Aqua Air Mineral Botol 330mL',
                    'deskripsi'        => 'Air mineral Aqua kemasan botol 330mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 1520.83, // 36.500 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 1520.83, 'harga_jual' => 1700.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 36500.00, 'harga_jual' => 40800.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'AQU-B600',
                    'nama_produk'      => 'Aqua Air Mineral Botol 600mL',
                    'deskripsi'        => 'Air mineral Aqua kemasan botol 600mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 2025.00, // 48.600 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 2025.00,  'harga_jual' => 2300.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 48600.00, 'harga_jual' => 55200.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                // Aqua Cup → Minuman Gelas Cup
                'produk' => [
                    'kategori_id'      => $gelas->id,
                    'kode_produk'      => 'AQU-CUP',
                    'nama_produk'      => 'Aqua Air Mineral Cup 220mL',
                    'deskripsi'        => 'Air mineral Aqua kemasan gelas cup 220mL, 1 dus = 48 pcs',
                    'harga_beli_dasar' => 718.75, // 34.500 / 48
                    'stok_minimum'     => 48,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 718.75,  'harga_jual' => 800.00,   'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 48, 'harga_beli' => 34500.00, 'harga_jual' => 38400.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],

            // ── CLEO ──────────────────────────────────────────────────────────────
            [
                // satuan_dasar=dus (terkecil=dus/pack), 1 karton = 24 dus
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'CLO-B220',
                    'nama_produk'      => 'Cleo Air Mineral Botol 220mL',
                    'deskripsi'        => 'Air mineral Cleo kemasan botol 220mL, 1 karton = 24 dus',
                    'harga_beli_dasar' => 812.50, // 19.500 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'dus',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'dus',    'isi' => 1,  'harga_beli' => 812.50,  'harga_jual' => 900.00,   'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'karton', 'isi' => 24, 'harga_beli' => 19500.00, 'harga_jual' => 21600.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],

            // ── CRYSTALIN ─────────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'CRS-B600',
                    'nama_produk'      => 'Crystalin Air Mineral Botol 600mL',
                    'deskripsi'        => 'Air mineral Crystalin kemasan botol 600mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 1770.83, // 42.500 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 1770.83, 'harga_jual' => 2000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 42500.00, 'harga_jual' => 48000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                // Galon → satuan tunggal pcs
                'produk' => [
                    'kategori_id'      => $galon->id,
                    'kode_produk'      => 'CRS-GLN',
                    'nama_produk'      => 'Crystalin Air Mineral Galon 15L',
                    'deskripsi'        => 'Air mineral Crystalin kemasan galon 15 liter isi ulang',
                    'harga_beli_dasar' => 18500.00,
                    'stok_minimum'     => 5,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1, 'harga_beli' => 18500.00, 'harga_jual' => 20000.00, 'is_default' => 1, 'barcode' => null],
                ],
            ],

            // ── LARISST ───────────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'LRS-B330',
                    'nama_produk'      => 'Larisst Air Mineral Botol 330mL',
                    'deskripsi'        => 'Air mineral Larisst kemasan botol 330mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 1329.17, // 31.900 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 1329.17, 'harga_jual' => 1500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 31900.00, 'harga_jual' => 36000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],

            // ── LE MINERALE ───────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'LMN-B1500',
                    'nama_produk'      => 'Le Minerale Air Mineral Botol 1500mL',
                    'deskripsi'        => 'Air mineral Le Minerale kemasan botol 1500mL, 1 dus = 12 pcs',
                    'harga_beli_dasar' => 4825.00, // 57.900 / 12
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 4825.00,  'harga_jual' => 5500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 12, 'harga_beli' => 57900.00, 'harga_jual' => 66000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                // satuan_dasar=dus, jumlah=1 → beli dan stok per dus (satuan tunggal)
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'LMN-B330',
                    'nama_produk'      => 'Le Minerale Air Mineral Botol 330mL',
                    'deskripsi'        => 'Air mineral Le Minerale kemasan botol 330mL, beli dan stok per dus',
                    'harga_beli_dasar' => 43500.00,
                    'stok_minimum'     => 1,
                    'satuan_dasar'     => 'dus',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'dus', 'isi' => 1, 'harga_beli' => 43500.00, 'harga_jual' => 46000.00, 'is_default' => 1, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'LMN-B600',
                    'nama_produk'      => 'Le Minerale Air Mineral Botol 600mL',
                    'deskripsi'        => 'Air mineral Le Minerale kemasan botol 600mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 2495.83, // 59.900 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 2495.83, 'harga_jual' => 2700.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 59900.00, 'harga_jual' => 64800.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],

            // ── NESTLE ────────────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'NST-B1500',
                    'nama_produk'      => 'Nestle Pure Life Air Mineral Botol 1500mL',
                    'deskripsi'        => 'Air mineral Nestle Pure Life kemasan botol 1500mL, 1 dus = 12 pcs',
                    'harga_beli_dasar' => 4291.67, // 51.500 / 12
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 4291.67, 'harga_jual' => 4800.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 12, 'harga_beli' => 51500.00, 'harga_jual' => 57600.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                // satuan_dasar=dus, 1 karton = 24 dus
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'NST-B330',
                    'nama_produk'      => 'Nestle Pure Life Air Mineral Botol 330mL',
                    'deskripsi'        => 'Air mineral Nestle Pure Life kemasan botol 330mL, 1 karton = 24 dus',
                    'harga_beli_dasar' => 1658.33, // 39.800 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'dus',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'dus',    'isi' => 1,  'harga_beli' => 1658.33, 'harga_jual' => 1800.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'karton', 'isi' => 24, 'harga_beli' => 39800.00, 'harga_jual' => 43200.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'NST-B600',
                    'nama_produk'      => 'Nestle Pure Life Air Mineral Botol 600mL',
                    'deskripsi'        => 'Air mineral Nestle Pure Life kemasan botol 600mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 2083.33, // 50.000 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 2083.33, 'harga_jual' => 2300.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 50000.00, 'harga_jual' => 55200.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],

            // ── SANQUA ────────────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'SNQ-B220',
                    'nama_produk'      => 'Sanqua Air Mineral Botol 220mL',
                    'deskripsi'        => 'Air mineral Sanqua kemasan botol 220mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 833.33, // 20.000 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 833.33,  'harga_jual' => 1000.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 20000.00, 'harga_jual' => 24000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'SNQ-B600',
                    'nama_produk'      => 'Sanqua Air Mineral Botol 600mL',
                    'deskripsi'        => 'Air mineral Sanqua kemasan botol 600/550mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 1312.50, // 31.500 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 1312.50, 'harga_jual' => 1500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 31500.00, 'harga_jual' => 36000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                // Sanqua Cup → Minuman Gelas Cup
                'produk' => [
                    'kategori_id'      => $gelas->id,
                    'kode_produk'      => 'SNQ-CUP',
                    'nama_produk'      => 'Sanqua Air Mineral Cup 220mL',
                    'deskripsi'        => 'Air mineral Sanqua kemasan gelas cup 220mL, 1 dus = 48 pcs',
                    'harga_beli_dasar' => 416.67, // 20.000 / 48
                    'stok_minimum'     => 48,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 416.67,  'harga_jual' => 500.00,   'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 48, 'harga_beli' => 20000.00, 'harga_jual' => 24000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],

            // ── VIT ───────────────────────────────────────────────────────────────
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'VIT-B1500',
                    'nama_produk'      => 'Vit Air Mineral Botol 1500mL',
                    'deskripsi'        => 'Air mineral Vit kemasan botol 1500mL, 1 dus = 12 pcs',
                    'harga_beli_dasar' => 2825.00, // 33.900 / 12
                    'stok_minimum'     => 12,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 2825.00,  'harga_jual' => 3200.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 12, 'harga_beli' => 33900.00, 'harga_jual' => 38400.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
            [
                'produk' => [
                    'kategori_id'      => $botol->id,
                    'kode_produk'      => 'VIT-B550',
                    'nama_produk'      => 'Vit Air Mineral Botol 550mL',
                    'deskripsi'        => 'Air mineral Vit kemasan botol 550mL, 1 dus = 24 pcs',
                    'harga_beli_dasar' => 1345.83, // 32.300 / 24
                    'stok_minimum'     => 24,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => [
                    ['nama_satuan' => 'pcs', 'isi' => 1,  'harga_beli' => 1345.83, 'harga_jual' => 1500.00,  'is_default' => 1, 'barcode' => null],
                    ['nama_satuan' => 'dus', 'isi' => 24, 'harga_beli' => 32300.00, 'harga_jual' => 36000.00, 'is_default' => 0, 'barcode' => null],
                ],
            ],
        ];

        foreach ($produkList as $item) {
            $item['produk']['qty_stok'] = $this->randomQtyStok($item['produk']['stok_minimum']);
            $produk = Produk::create($item['produk']);

            foreach ($item['satuan'] as $satuan) {
                ProdukSatuan::create(array_merge($satuan, ['produk_id' => $produk->id]));
            }
        }
    }
}
