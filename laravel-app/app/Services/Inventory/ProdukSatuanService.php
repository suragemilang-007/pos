<?php

namespace App\Services\Inventory;

use App\Models\Produk;
use App\Models\ProdukSatuan;

/**
 * ProdukSatuanService — kalkulasi berbasis multi-unit pricing.
 *
 * Dipisah dari ProdukService karena logika satuan (hemat, konversi stok)
 * cukup kompleks dan akan dipakai di beberapa titik:
 * endpoint cekStok, laporan margin, form input transaksi.
 */
class ProdukSatuanService
{
    /**
     * Harga efektif per satuan_dasar untuk satu entri produk_satuan.
     * Dipakai sebagai dasar perbandingan antar satuan.
     *
     * Contoh: karton kopi 280.000 / 96 pcs = 2.916,67/pcs
     */
    public function getHargaEfektifPerDasar(ProdukSatuan $satuan): float
    {
        if ($satuan->isi <= 0) return 0.0;

        return (float) $satuan->harga_jual / $satuan->isi;
    }

    /**
     * Persentase penghematan satuan ini dibanding satuan default.
     *
     * Formula: (harga_default_per_dasar - harga_ini_per_dasar) / harga_default_per_dasar × 100
     *
     * Contoh — Kopi Kapal Api:
     *   default (pcs)  : 3.500 / 1  = 3.500/pcs  → hemat 0%
     *   renteng        : 31.500 / 10 = 3.150/pcs  → hemat (3500-3150)/3500×100 = 10.0%
     *   karton         : 280.000/96 = 2.916,67/pcs → hemat (3500-2916,67)/3500×100 = 16.7%
     *
     * @param  ProdukSatuan  $satuan         Satuan yang akan dihitung hematnya
     * @param  ProdukSatuan  $defaultSatuan  Satuan default produk (is_default=1)
     * @return float         Persentase penghematan, dibulatkan 1 desimal. 0 jika satuan default.
     */
    public function getHematPersen(ProdukSatuan $satuan, ProdukSatuan $defaultSatuan): float
    {
        // Satuan default sendiri tidak punya penghematan
        if ($satuan->id === $defaultSatuan->id) return 0.0;

        $hargaDefault = $this->getHargaEfektifPerDasar($defaultSatuan);

        if ($hargaDefault <= 0) return 0.0;

        $hargaIni = $this->getHargaEfektifPerDasar($satuan);

        return round(($hargaDefault - $hargaIni) / $hargaDefault * 100, 1);
    }

    /**
     * Build array satuan_tersedia lengkap dengan stok_dalam_satuan dan hemat_persen.
     * Dipakai controller untuk menyusun response SD-07.
     *
     * @param  Produk  $produk  Harus sudah eager load relasi satuanList()
     * @return array
     */
    public function buildSatuanTersedia(Produk $produk): array
    {
        $satuanList   = $produk->satuanList;
        $qtyStok      = $produk->qty_stok;

        // Cari satuan default — jika tidak ada, fallback ke item pertama
        $defaultSatuan = $satuanList->firstWhere('is_default', true)
            ?? $satuanList->first();

        if (! $defaultSatuan) return [];

        return $satuanList->map(function (ProdukSatuan $s) use ($qtyStok, $defaultSatuan) {
            return [
                'id'                => $s->id,
                'nama_satuan'       => $s->nama_satuan,
                'isi'               => $s->isi,
                'harga_jual'        => (float) $s->harga_jual,
                // floor: tidak bisa jual 0.5 karton
                'stok_dalam_satuan' => (int) floor($qtyStok / $s->isi),
                'hemat_persen'      => $this->getHematPersen($s, $defaultSatuan),
                'is_default'        => (bool) $s->is_default,
            ];
        })->values()->all();
    }
}
