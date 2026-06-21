<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use Illuminate\Database\Seeder;

class RokokSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = KategoriProduk::where('nama_kategori', 'Rokok')->firstOrFail();

        // Satuan: pcs = bungkus (satuan_dasar), slop = satuan beli
        // harga_beli_dasar = harga_beli_slop / jumlah_pcs_per_slop
        $produkList = [

            // ── DJARUM 76 ─────────────────────────────────────────────────────────
            [
                'kode' => 'DJ76-KRT',  'nama' => 'Djarum 76 Kretek Bks 12s',
                'beli_dasar' => 15450.00, 'jual_dasar' => 16000.00,  // 154.500 / 10
                'beli_slop' => 154500.00, 'jual_slop' => 160000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Djarum 76 kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DJ76-NNS',  'nama' => 'Djarum 76 Kretek Nanas Bks 12s',
                'beli_dasar' => 13650.00, 'jual_dasar' => 14100.00,  // 136.500 / 10
                'beli_slop' => 136500.00, 'jual_slop' => 141000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Djarum 76 rasa nanas kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DJ76-APL',  'nama' => 'Djarum 76 Kretek Apel Bks 12s',
                'beli_dasar' => 14300.00, 'jual_dasar' => 14800.00,  // 143.000 / 10
                'beli_slop' => 143000.00, 'jual_slop' => 148000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Djarum 76 rasa apel kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DJ76-MGR',  'nama' => 'Djarum 76 Kretek Mangga Royal Bks 12s',
                'beli_dasar' => 14550.00, 'jual_dasar' => 15000.00,  // 145.500 / 10
                'beli_slop' => 145500.00, 'jual_slop' => 150000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Djarum 76 rasa mangga royal kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DJ76-SMG',  'nama' => 'Djarum 76 Sigaret Kretek Mangga Bks 12s',
                'beli_dasar' => 14550.00, 'jual_dasar' => 15000.00,  // 145.500 / 10
                'beli_slop' => 145500.00, 'jual_slop' => 150000.00, 'isi_slop' => 10,
                'deskripsi' => 'Sigaret kretek Djarum 76 rasa mangga kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DJ76-APR',  'nama' => 'Djarum 76 Kretek Apel Royal Bks 12s',
                'beli_dasar' => 14600.00, 'jual_dasar' => 15100.00,  // 146.000 / 10
                'beli_slop' => 146000.00, 'jual_slop' => 151000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Djarum 76 rasa apel royal kemasan bungkus 12 batang',
            ],

            // ── AROMA ─────────────────────────────────────────────────────────────
            [
                'kode' => 'ARM-FRT',   'nama' => 'Aroma Rokok Filter Royal Tea Bks 16s',
                'beli_dasar' => 13950.00, 'jual_dasar' => 14400.00,  // 139.500 / 10
                'beli_slop' => 139500.00, 'jual_slop' => 144000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Aroma rasa royal tea kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'ARM-FIB',   'nama' => 'Aroma Rokok Filter Ice Berry Tea Bks 16s',
                'beli_dasar' => 23650.00, 'jual_dasar' => 24400.00,  // 236.500 / 10
                'beli_slop' => 236500.00, 'jual_slop' => 244000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Aroma rasa ice berry tea kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'ARM-KFB12', 'nama' => 'Aroma Kretek Filter Bold Bks 12s',
                'beli_dasar' => 16250.00, 'jual_dasar' => 16800.00,  // 162.500 / 10
                'beli_slop' => 162500.00, 'jual_slop' => 168000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek filter Aroma Bold kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'ARM-KFB16', 'nama' => 'Aroma Kretek Filter Bold Bks 16s',
                'beli_dasar' => 21600.00, 'jual_dasar' => 22300.00,  // 216.000 / 10
                'beli_slop' => 216000.00, 'jual_slop' => 223000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek filter Aroma Bold kemasan bungkus 16 batang',
            ],

            // ── DJARUM ────────────────────────────────────────────────────────────
            [
                'kode' => 'DJR-DWR',   'nama' => 'Djarum Rokok Kretek D Wraps Bks 12s',
                'beli_dasar' => 13500.00, 'jual_dasar' => 14000.00,  // 135.000 / 10
                'beli_slop' => 135000.00, 'jual_slop' => 140000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Djarum D Wraps kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DJR-CKL',   'nama' => 'Djarum Kretek Coklat Bks 12s',
                'beli_dasar' => 16340.00, 'jual_dasar' => 16900.00,  // 163.400 / 10
                'beli_slop' => 163400.00, 'jual_slop' => 169000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Djarum rasa coklat kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DJR-CKX',   'nama' => 'Djarum Kretek Coklat Extra Bks 12s',
                'beli_dasar' => 15450.00, 'jual_dasar' => 16000.00,  // 154.500 / 10
                'beli_slop' => 154500.00, 'jual_slop' => 160000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Djarum rasa coklat extra kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DJR-CGR',   'nama' => 'Djarum Cerutu Cigarillos Bks 6s',
                'beli_dasar' => 12600.00, 'jual_dasar' => 13000.00,  // 126.000 / 10
                'beli_slop' => 126000.00, 'jual_slop' => 130000.00, 'isi_slop' => 10,
                'deskripsi' => 'Cerutu cigarillos Djarum kemasan bungkus 6 batang',
            ],
            [
                'kode' => 'DJR-BLK',   'nama' => 'Djarum Filter Black Bks 16s',
                'beli_dasar' => 33800.00, 'jual_dasar' => 34900.00,  // 338.000 / 10
                'beli_slop' => 338000.00, 'jual_slop' => 349000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Djarum Black kemasan bungkus 16 batang',
            ],

            // ── DJI SAM SOE ───────────────────────────────────────────────────────
            [
                'kode' => 'DSS-FMG',   'nama' => 'Dji Sam Soe Filter Magnum Bks 12s',
                'beli_dasar' => 26200.00, 'jual_dasar' => 27000.00,  // 262.000 / 10
                'beli_slop' => 262000.00, 'jual_slop' => 270000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Dji Sam Soe Magnum kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DSS-FMB',   'nama' => 'Dji Sam Soe Filter Magnum Edisi Bintang Bks 12s',
                'beli_dasar' => 23200.00, 'jual_dasar' => 24000.00,  // 232.000 / 10
                'beli_slop' => 232000.00, 'jual_slop' => 240000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Dji Sam Soe Magnum edisi bintang kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DSS-234',   'nama' => 'Dji Sam Soe Kretek Magnum 234 Pck 12s',
                'beli_dasar' => 13150.00, 'jual_dasar' => 13600.00,  // 131.500 / 10
                'beli_slop' => 131500.00, 'jual_slop' => 136000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Dji Sam Soe Magnum 234 kemasan pack 12 batang',
            ],
            [
                'kode' => 'DSS-KSF',   'nama' => 'Dji Sam Soe Kretek Super Premium Soft Pack Bks 12s',
                'beli_dasar' => 18250.00, 'jual_dasar' => 18800.00,  // 182.500 / 10
                'beli_slop' => 182500.00, 'jual_slop' => 188000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Dji Sam Soe Super Premium soft pack kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DSS-K16',   'nama' => 'Dji Sam Soe Kretek Bks 16s',
                'beli_dasar' => 26150.00, 'jual_dasar' => 27000.00,  // 261.500 / 10
                'beli_slop' => 261500.00, 'jual_slop' => 270000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Dji Sam Soe kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'DSS-K12',   'nama' => 'Dji Sam Soe Kretek Bks 12s',
                'beli_dasar' => 19300.00, 'jual_dasar' => 19900.00,  // 193.000 / 10
                'beli_slop' => 193000.00, 'jual_slop' => 199000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Dji Sam Soe kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DSS-KMG',   'nama' => 'Dji Sam Soe Kretek Magnum Bks 12s',
                'beli_dasar' => 16400.00, 'jual_dasar' => 16900.00,  // 164.000 / 10
                'beli_slop' => 164000.00, 'jual_slop' => 169000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Dji Sam Soe Magnum kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DSS-KSP12', 'nama' => 'Dji Sam Soe Kretek Super Premium Bks 12s',
                'beli_dasar' => 21250.00, 'jual_dasar' => 21900.00,  // 212.500 / 10
                'beli_slop' => 212500.00, 'jual_slop' => 219000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Dji Sam Soe Super Premium kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DSS-KMC',   'nama' => 'Dji Sam Soe Kretek Magnum Coklat Pck 12s',
                'beli_dasar' => 13350.00, 'jual_dasar' => 13800.00,  // 133.500 / 10
                'beli_slop' => 133500.00, 'jual_slop' => 138000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Dji Sam Soe Magnum Coklat kemasan pack 12 batang',
            ],
            [
                'kode' => 'DSS-KSP16', 'nama' => 'Dji Sam Soe Kretek Super Premium Bks 16s',
                'beli_dasar' => 25650.00, 'jual_dasar' => 26500.00,  // 205.200 / 8
                'beli_slop' => 205200.00, 'jual_slop' => 212000.00, 'isi_slop' => 8,
                'deskripsi' => 'Rokok kretek Dji Sam Soe Super Premium kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'DSS-KEK',   'nama' => 'Dji Sam Soe Kretek Edisi Khusus Bks 12s',
                'beli_dasar' => 19150.00, 'jual_dasar' => 19800.00,  // 191.500 / 10
                'beli_slop' => 191500.00, 'jual_slop' => 198000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Dji Sam Soe edisi khusus kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'DSS-KME',   'nama' => 'Dji Sam Soe Kretek Maestro Edition Super Premium Bks 12s',
                'beli_dasar' => 18270.00, 'jual_dasar' => 18900.00,  // 182.700 / 10
                'beli_slop' => 182700.00, 'jual_slop' => 189000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Dji Sam Soe Maestro Edition Super Premium kemasan bungkus 12 batang',
            ],

            // ── GUDANG GARAM ──────────────────────────────────────────────────────
            [
                'kode' => 'GG-KSP12',  'nama' => 'Gudang Garam Kretek Softpack Bks 12s',
                'beli_dasar' => 15300.00, 'jual_dasar' => 15800.00,  // 153.000 / 10
                'beli_slop' => 153000.00, 'jual_slop' => 158000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Gudang Garam kemasan softpack bungkus 12 batang',
            ],
            [
                'kode' => 'GG-KDL16',  'nama' => 'Gudang Garam Kretek Deluxe Bks 16s',
                'beli_dasar' => 18300.00, 'jual_dasar' => 18900.00,  // 183.000 / 10
                'beli_slop' => 183000.00, 'jual_slop' => 189000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Gudang Garam Deluxe kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'GG-KMS16',  'nama' => 'Gudang Garam Kretek Filter Mild Shiver Bks 16s',
                'beli_dasar' => 32100.00, 'jual_dasar' => 33100.00,  // 321.000 / 10
                'beli_slop' => 321000.00, 'jual_slop' => 331000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek filter Gudang Garam Mild Shiver kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'GG-KMR16',  'nama' => 'Gudang Garam Kretek Merah Bks 16s',
                'beli_dasar' => 18300.00, 'jual_dasar' => 18900.00,  // 183.000 / 10
                'beli_slop' => 183000.00, 'jual_slop' => 189000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Gudang Garam Merah kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'GG-KDL12',  'nama' => 'Gudang Garam Kretek Deluxe Bks 12s',
                'beli_dasar' => 14430.00, 'jual_dasar' => 14900.00,  // 144.300 / 10
                'beli_slop' => 144300.00, 'jual_slop' => 149000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek Gudang Garam Deluxe kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'GG-FSY16',  'nama' => 'Gudang Garam Filter Surya Bks 16s',
                'beli_dasar' => 34850.00, 'jual_dasar' => 36000.00,  // 348.500 / 10
                'beli_slop' => 348500.00, 'jual_slop' => 360000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Gudang Garam Surya kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'GG-FMR12',  'nama' => 'Gudang Garam Filter Merah Bks 12s',
                'beli_dasar' => 25850.00, 'jual_dasar' => 26700.00,  // 517.000 / 20
                'beli_slop' => 517000.00, 'jual_slop' => 534000.00, 'isi_slop' => 20,
                'deskripsi' => 'Rokok filter Gudang Garam Merah kemasan bungkus 12 batang, 1 slop = 20 bungkus',
            ],
            [
                'kode' => 'GG-FSG12',  'nama' => 'Gudang Garam Filter Signature Bks 12s',
                'beli_dasar' => 25850.00, 'jual_dasar' => 26700.00,  // 258.500 / 10
                'beli_slop' => 258500.00, 'jual_slop' => 267000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Gudang Garam Signature kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'GG-FSP16',  'nama' => 'Gudang Garam Filter Surya Pro Bks 16s',
                'beli_dasar' => 32100.00, 'jual_dasar' => 33100.00,  // 321.000 / 10
                'beli_slop' => 321000.00, 'jual_slop' => 331000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Gudang Garam Surya Pro kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'GG-FSM16',  'nama' => 'Gudang Garam Filter Signature Mild Bks 16s',
                'beli_dasar' => 32100.00, 'jual_dasar' => 33100.00,  // 321.000 / 10
                'beli_slop' => 321000.00, 'jual_slop' => 331000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Gudang Garam Signature Mild kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'GG-FSC12',  'nama' => 'Gudang Garam Filter Surya Coklat Bks 12s',
                'beli_dasar' => 25900.00, 'jual_dasar' => 26700.00,  // 259.000 / 10
                'beli_slop' => 259000.00, 'jual_slop' => 267000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Gudang Garam Surya Coklat kemasan bungkus 12 batang',
            ],
            [
                'kode' => 'GG-FSR16',  'nama' => 'Gudang Garam Filter Surya Merah Bks 16s',
                'beli_dasar' => 34880.00, 'jual_dasar' => 36000.00,  // 348.800 / 10
                'beli_slop' => 348800.00, 'jual_slop' => 360000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok filter Gudang Garam Surya Merah kemasan bungkus 16 batang',
            ],
            [
                'kode' => 'GG-KSP16',  'nama' => 'Gudang Garam Kretek Filter Surya Pro Mild Bks 16s',
                'beli_dasar' => 32100.00, 'jual_dasar' => 33100.00,  // 321.000 / 10
                'beli_slop' => 321000.00, 'jual_slop' => 331000.00, 'isi_slop' => 10,
                'deskripsi' => 'Rokok kretek filter Gudang Garam Surya Pro Mild kemasan bungkus 16 batang',
            ],
        ];

        foreach ($produkList as $item) {
            $produk = Produk::create([
                'kategori_id'      => $kategori->id,
                'kode_produk'      => $item['kode'],
                'nama_produk'      => $item['nama'],
                'deskripsi'        => $item['deskripsi'],
                'harga_beli_dasar' => $item['beli_dasar'],
                'qty_stok'         => $item['isi_slop'], // stok awal = 1 slop
                'stok_minimum'     => $item['isi_slop'],
                'satuan_dasar'     => 'pcs',
                'status'           => 'aktif',
            ]);

            ProdukSatuan::create([
                'produk_id'   => $produk->id,
                'nama_satuan' => 'pcs',
                'isi'         => 1,
                'harga_beli'  => $item['beli_dasar'],
                'harga_jual'  => $item['jual_dasar'],
                'is_default'  => 1,
                'barcode'     => null,
            ]);

            ProdukSatuan::create([
                'produk_id'   => $produk->id,
                'nama_satuan' => 'slop',
                'isi'         => $item['isi_slop'],
                'harga_beli'  => $item['beli_slop'],
                'harga_jual'  => $item['jual_slop'],
                'is_default'  => 0,
                'barcode'     => null,
            ]);
        }
    }
}
