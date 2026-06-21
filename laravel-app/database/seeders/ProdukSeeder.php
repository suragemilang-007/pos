<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriMiePasta = KategoriProduk::where('nama_kategori', 'Mie & Pasta')->firstOrFail();

        // Satuan Indomie varian premium: pcs dan dus (1 dus = 40 pcs, beli 112.000/dus)
        $satuanIndomie = [
            [
                'nama_satuan' => 'pcs',
                'isi'         => 1,
                'harga_beli'  => 2800.00,
                'harga_jual'  => 3000.00,
                'is_default'  => 1,
                'barcode'     => null,
            ],
            [
                'nama_satuan' => 'dus',
                'isi'         => 40,
                'harga_beli'  => 112000.00,
                'harga_jual'  => 116000.00,
                'is_default'  => 0,
                'barcode'     => null,
            ],
        ];

        // Satuan Indomie varian reguler: pcs dan dus (1 dus = 40 pcs, beli 106.000/dus)
        // harga_beli_dasar per pcs = 106.000 / 40 = 2.650
        $satuanIndomie106 = [
            [
                'nama_satuan' => 'pcs',
                'isi'         => 1,
                'harga_beli'  => 2650.00,
                'harga_jual'  => 2800.00,
                'is_default'  => 1,
                'barcode'     => null,
            ],
            [
                'nama_satuan' => 'dus',
                'isi'         => 40,
                'harga_beli'  => 106000.00,
                'harga_jual'  => 110000.00,
                'is_default'  => 0,
                'barcode'     => null,
            ],
        ];

        $produkList = [
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-GOR',
                    'nama_produk'      => 'Indomie Goreng',
                    'deskripsi'        => 'Mie instan goreng original rasa ayam panggang',
                    'harga_beli_dasar' => 2800.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie,
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-ACH',
                    'nama_produk'      => 'Indomie Aceh',
                    'deskripsi'        => 'Mie instan goreng rasa mie aceh pedas khas Aceh',
                    'harga_beli_dasar' => 2800.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie,
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-RDG',
                    'nama_produk'      => 'Indomie Rendang',
                    'deskripsi'        => 'Mie instan goreng rasa rendang daging sapi',
                    'harga_beli_dasar' => 2800.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie,
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-AGP',
                    'nama_produk'      => 'Indomie Ayam Geprek',
                    'deskripsi'        => 'Mie instan goreng rasa ayam geprek pedas',
                    'harga_beli_dasar' => 2800.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie,
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-BGL',
                    'nama_produk'      => 'Indomie Bangladesh',
                    'deskripsi'        => 'Mie instan goreng rasa kari khas Bangladesh',
                    'harga_beli_dasar' => 2800.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie,
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-NJR',
                    'nama_produk'      => 'Indomie Nyemek Jogja Rendang',
                    'deskripsi'        => 'Mie instan nyemek khas Jogja dengan cita rasa rendang',
                    'harga_beli_dasar' => 2800.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie,
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-AB',
                    'nama_produk'      => 'Indomie Ayam Bawang',
                    'deskripsi'        => 'Mie instan rasa ayam bawang gurih',
                    'harga_beli_dasar' => 2650.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie106,
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-STO',
                    'nama_produk'      => 'Indomie Soto',
                    'deskripsi'        => 'Mie instan kuah rasa soto ayam khas Indonesia',
                    'harga_beli_dasar' => 2650.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie106,
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-ASP',
                    'nama_produk'      => 'Indomie Ayam Special',
                    'deskripsi'        => 'Mie instan rasa ayam special dengan bumbu lengkap',
                    'harga_beli_dasar' => 2650.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie106,
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-KAY',
                    'nama_produk'      => 'Indomie Kari Ayam',
                    'deskripsi'        => 'Mie instan kuah rasa kari ayam kental',
                    'harga_beli_dasar' => 2650.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie106,
            ],
            [
                'produk' => [
                    'kategori_id'      => $kategoriMiePasta->id,
                    'kode_produk'      => 'IND-SBL',
                    'nama_produk'      => 'Indomie Seblak',
                    'deskripsi'        => 'Mie instan rasa seblak pedas khas Bandung',
                    'harga_beli_dasar' => 2650.00,
                    'qty_stok'         => 40,
                    'stok_minimum'     => 40,
                    'satuan_dasar'     => 'pcs',
                    'status'           => 'aktif',
                ],
                'satuan' => $satuanIndomie106,
            ],
        ];

        foreach ($produkList as $item) {
            $produk = Produk::create($item['produk']);

            foreach ($item['satuan'] as $satuan) {
                ProdukSatuan::create(array_merge($satuan, ['produk_id' => $produk->id]));
            }
        }
    }
}
