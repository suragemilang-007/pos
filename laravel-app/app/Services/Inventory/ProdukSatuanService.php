<?php

namespace App\Services\Inventory;

use App\Models\DetailPembelian;
use App\Models\DetailTransaksi;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProdukSatuanService
{
    // ─────────────────────────────────────────────────────────────────────────
    // QUERY
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * List semua satuan satu produk, urut is_default DESC → isi ASC.
     * Urutan ini konsisten dengan relasi satuanList() di model Produk.
     */
    public function getSatuanList(int $produkId): Collection
    {
        return ProdukSatuan::where('produk_id', $produkId)
            ->orderByDesc('is_default')
            ->orderBy('isi')
            ->get();
    }

    /**
     * Ambil satuan default (is_default=1) untuk sebuah produk.
     * Dipakai saat form transaksi perlu pre-select satuan yang paling umum.
     */
    public function getSatuanDefault(int $produkId): ?ProdukSatuan
    {
        return ProdukSatuan::where('produk_id', $produkId)
            ->where('is_default', 1)
            ->first();
    }

    /**
     * Cari satuan berdasarkan barcode.
     * Dipakai kasir saat scan barcode kemasan besar (karton, dus) yang
     * barcodenya berbeda dari barcode satuan dasar produk.
     */
    public function getSatuanByBarcode(string $barcode): ?ProdukSatuan
    {
        return ProdukSatuan::where('barcode', $barcode)->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tambah satuan baru untuk sebuah produk.
     *
     * Jika is_default=true: reset semua satuan lain di produk ini ke 0 dulu
     * sebelum INSERT satuan baru. Ini memastikan hanya ada tepat 1 satuan default
     * per produk di database, mencegah ambiguitas di form transaksi kasir.
     *
     * DB::transaction menjamin reset + insert atomik — tidak bisa setengah jalan.
     */
    public function create(int $produkId, array $data): ProdukSatuan
    {
        return DB::transaction(function () use ($produkId, $data) {
            if (! empty($data['is_default'])) {
                $this->resetDefault($produkId);
            }

            return ProdukSatuan::create([
                'produk_id'   => $produkId,
                'nama_satuan' => $data['nama_satuan'],
                'isi'         => $data['isi'],
                'harga_beli'  => $data['harga_beli'],
                'harga_jual'  => $data['harga_jual'],
                'is_default'  => (bool) ($data['is_default'] ?? false),
                'barcode'     => $data['barcode'] ?? null,
            ]);
        });
    }

    /**
     * Update satuan yang sudah ada.
     * Jika is_default diubah menjadi true: reset satuan lain ke 0.
     */
    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $satuan = ProdukSatuan::findOrFail($id);

            if (! empty($data['is_default'])) {
                $this->resetDefault($satuan->produk_id, excludeId: $id);
            }

            return $satuan->update($data);
        });
    }

    /**
     * Hapus satuan.
     *
     * Satuan tidak boleh dihapus jika pernah dipakai di detail_transaksi atau
     * detail_pembelian — data snapshot di kedua tabel mereferensi produk_satuan_id.
     * Meski FK nullable (nullOnDelete), menghapus satuan yang pernah dipakai
     * akan membuat riwayat transaksi lama kehilangan referensi satuannya.
     *
     * @throws \RuntimeException jika satuan masih dipakai
     */
    public function delete(int $id): bool
    {
        $dipakai = DetailTransaksi::where('produk_satuan_id', $id)->exists()
            || DetailPembelian::where('produk_satuan_id', $id)->exists();

        if ($dipakai) {
            throw new \RuntimeException(
                'Satuan sudah pernah dipakai di riwayat transaksi atau pembelian, tidak bisa dihapus'
            );
        }

        return (bool) ProdukSatuan::destroy($id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KALKULASI
    // ─────────────────────────────────────────────────────────────────────────

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
     * @param  ProdukSatuan  $satuan
     * @param  ProdukSatuan  $defaultSatuan  Satuan default produk (is_default=1)
     * @return float         Persentase penghematan, dibulatkan 1 desimal. 0 jika satuan default.
     */
    public function getHematPersen(ProdukSatuan $satuan, ProdukSatuan $defaultSatuan): float
    {
        if ($satuan->id === $defaultSatuan->id) return 0.0;

        $hargaDefault = $this->getHargaEfektifPerDasar($defaultSatuan);

        if ($hargaDefault <= 0) return 0.0;

        $hargaIni = $this->getHargaEfektifPerDasar($satuan);

        return round(($hargaDefault - $hargaIni) / $hargaDefault * 100, 1);
    }

    /**
     * Build array satuan_tersedia lengkap dengan stok_dalam_satuan dan hemat_persen.
     * Dipakai oleh ProdukController untuk menyusun response SD-07 dan SD-08.
     *
     * @param  Produk  $produk  Harus sudah eager load relasi satuanList()
     */
    public function buildSatuanTersedia(Produk $produk): array
    {
        $satuanList   = $produk->satuanList;
        $qtyStok      = $produk->qty_stok;

        $defaultSatuan = $satuanList->firstWhere('is_default', true)
            ?? $satuanList->first();

        if (! $defaultSatuan) return [];

        return $satuanList->map(function (ProdukSatuan $s) use ($qtyStok, $defaultSatuan) {
            return [
                'id'                => $s->id,
                'nama_satuan'       => $s->nama_satuan,
                'isi'               => $s->isi,
                'harga_jual'        => (float) $s->harga_jual,
                'stok_dalam_satuan' => (int) floor($qtyStok / $s->isi),
                'hemat_persen'      => $this->getHematPersen($s, $defaultSatuan),
                'is_default'        => (bool) $s->is_default,
            ];
        })->values()->all();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Reset semua satuan produk menjadi is_default=0.
     * Dipanggil sebelum set satuan baru/tertentu sebagai default.
     *
     * @param  int|null  $excludeId  ID satuan yang dikecualikan dari reset (saat update)
     */
    private function resetDefault(int $produkId, ?int $excludeId = null): void
    {
        $query = ProdukSatuan::where('produk_id', $produkId)
            ->where('is_default', 1);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_default' => 0]);
    }
}
