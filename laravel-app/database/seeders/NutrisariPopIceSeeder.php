<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use Illuminate\Database\Seeder;

class NutrisariPopIceSeeder extends Seeder
{
    use HasRandomStock;

    public function run(): void
    {
        $kategori = KategoriProduk::where('nama_kategori', 'Minuman Renteng')->firstOrFail();

        // Semua produk ini: satuan=renteng, satuan_dasar=renteng, jumlah=1
        // → satuan tunggal, hanya 1 entri produk_satuan per produk
        $produkList = [

            // ── NUTRISARI ─────────────────────────────────────────────────────────
            [
                'kode_produk'      => 'NTS-BLW',
                'nama_produk'      => 'Nutrisari Instant Drink Blewah Sct 11g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa blewah kemasan sachet renteng',
                'harga_beli_dasar' => 12800.00,
                'harga_jual'       => 13500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'NTS-JPN',
                'nama_produk'      => 'Nutrisari Instant Drink Jeruk Nipis Sct 11g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa jeruk nipis kemasan sachet renteng',
                'harga_beli_dasar' => 12800.00,
                'harga_jual'       => 13500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'NTS-SRS',
                'nama_produk'      => 'Nutrisari Instant Drink Sirsak Sct 11g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa sirsak kemasan sachet renteng',
                'harga_beli_dasar' => 12500.00,
                'harga_jual'       => 13200.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'NTS-SMG',
                'nama_produk'      => 'Nutrisari Instant Drink Sweet Mango Sct 11g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa mangga manis kemasan sachet renteng',
                'harga_beli_dasar' => 12800.00,
                'harga_jual'       => 13500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'NTS-ECN',
                'nama_produk'      => 'Nutrisari Instant Drink Es Cincau Sct 13g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa es cincau kemasan sachet renteng',
                'harga_beli_dasar' => 12800.00,
                'harga_jual'       => 13500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'NTS-JJO',
                'nama_produk'      => 'Nutrisari Instant Drink Jeju Orange Sct 14g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa jeruk Jeju kemasan sachet renteng',
                'harga_beli_dasar' => 12800.00,
                'harga_jual'       => 13500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'NTS-JMR',
                'nama_produk'      => 'Nutrisari Instant Drink Jeruk Maroko Sct 14g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa jeruk Maroko kemasan sachet renteng',
                'harga_beli_dasar' => 12500.00,
                'harga_jual'       => 13200.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'NTS-MJK',
                'nama_produk'      => 'Nutrisari Instant Drink Madu Jeruk Sct 14g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa madu jeruk kemasan sachet renteng',
                'harga_beli_dasar' => 12800.00,
                'harga_jual'       => 13500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'NTS-FLO',
                'nama_produk'      => 'Nutrisari Instant Drink Florida Orange Sct 12/14g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa Florida orange kemasan sachet renteng',
                'harga_beli_dasar' => 12800.00,
                'harga_jual'       => 13500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'NTS-JEM',
                'nama_produk'      => 'Nutrisari Instant Drink Jeruk Extra Manis Sct 11g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa jeruk extra manis kemasan sachet renteng',
                'harga_beli_dasar' => 12800.00,
                'harga_jual'       => 13500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'NTS-JPR',
                'nama_produk'      => 'Nutrisari Instant Drink Jeruk Peras Sct 14g',
                'deskripsi'        => 'Minuman serbuk instan Nutrisari rasa jeruk peras kemasan sachet renteng',
                'harga_beli_dasar' => 12800.00,
                'harga_jual'       => 13500.00,
                'stok_minimum'     => 5,
            ],

            // ── POP ICE ───────────────────────────────────────────────────────────
            [
                'kode_produk'      => 'PPI-ANG',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Anggur Sct 23g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa anggur kemasan sachet renteng',
                'harga_beli_dasar' => 11700.00,
                'harga_jual'       => 12500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-CCP',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Cocopandan Sct 25g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa cocopandan kemasan sachet renteng',
                'harga_beli_dasar' => 11700.00,
                'harga_jual'       => 12500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-CCR',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Choco Cream Sct 25g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa choco cream kemasan sachet renteng',
                'harga_beli_dasar' => 11700.00,
                'harga_jual'       => 12500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-DUR',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Durian Sct 23g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa durian kemasan sachet renteng',
                'harga_beli_dasar' => 11500.00,
                'harga_jual'       => 12200.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-MEL',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Melon Sct 25g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa melon kemasan sachet renteng',
                'harga_beli_dasar' => 11500.00,
                'harga_jual'       => 12200.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-PKR',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Permen Karet Sct 23g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa permen karet kemasan sachet renteng',
                'harga_beli_dasar' => 11500.00,
                'harga_jual'       => 12200.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-VBL',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Vanilla Blue Sct 25g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa vanilla blue kemasan sachet renteng',
                'harga_beli_dasar' => 11500.00,
                'harga_jual'       => 12200.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-CAP',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Cappuccino Rcg 10x25g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa cappuccino kemasan renceng isi 10',
                'harga_beli_dasar' => 11700.00,
                'harga_jual'       => 12500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-MNG',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Mangga Rcg 10x23g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa mangga kemasan renceng isi 10',
                'harga_beli_dasar' => 11700.00,
                'harga_jual'       => 12500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-TAR',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Talas Taro Rcg 10x25g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa talas/taro kemasan renceng isi 10',
                'harga_beli_dasar' => 11700.00,
                'harga_jual'       => 12500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-CHC',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Chocolate Rcg 10x25g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa coklat kemasan renceng isi 10',
                'harga_beli_dasar' => 11700.00,
                'harga_jual'       => 12500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-AVO',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Avocado Rcg 10x25g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa alpukat kemasan renceng isi 10',
                'harga_beli_dasar' => 11700.00,
                'harga_jual'       => 12500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-STR',
                'nama_produk'      => 'Pop Ice Drink Powder Es Blender Strawberry Rcg 10x23g',
                'deskripsi'        => 'Minuman serbuk es blender Pop Ice rasa stroberi kemasan renceng isi 10',
                'harga_beli_dasar' => 11700.00,
                'harga_jual'       => 12500.00,
                'stok_minimum'     => 5,
            ],
            [
                'kode_produk'      => 'PPI-PCR',
                'nama_produk'      => 'Pop Ice Drink Powder Sultan Popcorn Caramel Sct 25g',
                'deskripsi'        => 'Minuman serbuk Pop Ice varian sultan rasa popcorn caramel kemasan sachet renteng',
                'harga_beli_dasar' => 11700.00,
                'harga_jual'       => 12500.00,
                'stok_minimum'     => 5,
            ],
        ];

        foreach ($produkList as $item) {
            $produk = Produk::create([
                'kategori_id'      => $kategori->id,
                'kode_produk'      => $item['kode_produk'],
                'nama_produk'      => $item['nama_produk'],
                'deskripsi'        => $item['deskripsi'],
                'harga_beli_dasar' => $item['harga_beli_dasar'],
                'qty_stok'         => $this->randomQtyStok($item['stok_minimum']),
                'stok_minimum'     => $item['stok_minimum'],
                'satuan_dasar'     => 'renteng',
                'status'           => 'aktif',
            ]);

            // Satuan tunggal — tidak ada satuan beli yang lebih besar
            ProdukSatuan::create([
                'produk_id'   => $produk->id,
                'nama_satuan' => 'renteng',
                'isi'         => 1,
                'harga_beli'  => $item['harga_beli_dasar'],
                'harga_jual'  => $item['harga_jual'],
                'is_default'  => 1,
                'barcode'     => null,
            ]);
        }
    }
}
