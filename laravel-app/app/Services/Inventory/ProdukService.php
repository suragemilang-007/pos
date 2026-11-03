<?php

namespace App\Services\Inventory;

use App\Models\DetailPembelian;
use App\Models\DetailTransaksi;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use Illuminate\Support\Facades\DB;

class ProdukService
{
    public function __construct(
        private readonly ProdukSatuanService $produkSatuanService
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Buat produk baru beserta semua satuan secara atomik.
     *
     * DB::transaction dipakai karena produk dan satuan_list harus satu kesatuan:
     * jika salah satu satuan gagal (misal barcode duplicate), produk yang sudah
     * INSERT harus di-rollback agar tidak ada produk tanpa satuan di database.
     *
     * @param  array  $data  Termasuk key 'satuan_list' berisi array satuan
     */
    public function create(array $data): Produk
    {
        return DB::transaction(function () use ($data) {
            $satuanList = $data['satuan_list'] ?? [];
            unset($data['satuan_list']);

            $produk = Produk::create($data);

            // Cari satuan default dari satuan_list untuk set is_default
            // Tidak pakai ProdukSatuanService::create() agar tidak ada extra query
            // reset-default yang tidak perlu (produk baru, belum ada satuan lain)
            foreach ($satuanList as $s) {
                ProdukSatuan::create([
                    'produk_id'   => $produk->id,
                    'nama_satuan' => $s['nama_satuan'],
                    'isi'         => $s['isi'],
                    'harga_beli'  => $s['harga_beli'],
                    'harga_jual'  => $s['harga_jual'],
                    'is_default'  => (bool) $s['is_default'],
                    'barcode'     => $s['barcode'] ?? null,
                ]);
            }

            return $produk->load('satuanList');
        });
    }

    /**
     * Update header produk saja.
     * Satuan dikelola terpisah via ProdukSatuanService (sub-resource endpoint).
     */
    public function update(int $id, array $data): bool
    {
        // Buang satuan_list jika ada (tidak seharusnya masuk ke sini)
        unset($data['satuan_list']);

        return (bool) Produk::where('id', $id)->update($data);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE (soft/hard)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cek apakah produk pernah dipakai di transaksi penjualan ATAU pembelian.
     *
     * Cek keduanya karena:
     * - detail_transaksi.produk_id → produk tidak boleh hard delete jika pernah terjual
     * - detail_pembelian.produk_id → produk tidak boleh hard delete jika pernah dibeli
     * Keduanya punya FK restrictOnDelete sehingga DB akan reject DELETE jika ada record.
     * Cek aplikatif ini memberi pesan error yang rapi sebelum DB melempar constraint error.
     */
    public function hasTransaksi(int $produkId): bool
    {
        return DetailTransaksi::where('produk_id', $produkId)->exists()
            || DetailPembelian::where('produk_id', $produkId)->exists();
    }

    /**
     * Soft delete — ubah status ke 'nonaktif'.
     * Produk tetap ada di DB, riwayat transaksi tetap valid.
     */
    public function softDelete(int $id): bool
    {
        return (bool) Produk::where('id', $id)->update(['status' => 'nonaktif']);
    }

    /**
     * Hard delete — hapus baris dari database.
     * CASCADE ke produk_satuan otomatis via FK cascadeOnDelete di migration.
     * HANYA boleh dipanggil jika hasTransaksi() = false.
     */
    public function hardDelete(int $id): bool
    {
        return (bool) Produk::destroy($id);
    }

    /**
     * "Smart delete" — pilih soft atau hard berdasarkan riwayat transaksi.
     * Sesuai pola AD-08 dan laravel-app/CLAUDE.md Soft Delete Pattern.
     *
     * @return string  'soft' jika di-nonaktifkan, 'hard' jika dihapus permanen
     */
    public function destroy(int $id): string
    {
        if ($this->hasTransaksi($id)) {
            $this->softDelete($id);
            return 'soft';
        }

        $this->hardDelete($id);
        return 'hard';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STOK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Update stok secara manual (admin correction / inventory count).
     *
     * Catatan penting: Dalam arsitektur event-driven ini, stok SEHARUSNYA
     * hanya diubah oleh Kafka Consumer (transactionConsumer, purchaseConsumer).
     * Method ini disediakan untuk koreksi manual oleh admin gudang —
     * BUKAN untuk dipanggil dari alur transaksi/pembelian normal.
     *
     * @param  string  $operasi  'tambah' | 'kurangi'
     */
    public function updateStok(int $produkId, int $qtyDasar, string $operasi): bool
    {
        $produk = Produk::find($produkId);

        if (! $produk) return false;

        if ($operasi === 'tambah') {
            $produk->qty_stok += $qtyDasar;
        } elseif ($operasi === 'kurangi') {
            // Jangan sampai stok negatif
            $produk->qty_stok = max(0, $produk->qty_stok - $qtyDasar);
        } else {
            return false;
        }

        return $produk->save();
    }

    /**
     * Stok produk dipecah per satuan — delegate ke ProdukSatuanService.
     * Tidak duplikasi logika: ProdukSatuanService::buildSatuanTersedia() adalah
     * sumber kebenaran untuk kalkulasi stok per satuan.
     */
    public function getStokDalamSatuan(int $produkId): array
    {
        $produk = Produk::with('satuanList')->find($produkId);

        if (! $produk) return [];

        return $this->produkSatuanService->buildSatuanTersedia($produk);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CEK STOK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cek apakah stok mencukupi untuk qty dalam satuan tertentu.
     *
     * Signature berubah dari versi sebelumnya: tambah $isiSatuan sesuai CLASS_DIAGRAM.
     * Konversi dilakukan di sini: kebutuhan_dasar = qty × isiSatuan.
     * Contoh: jual 2 karton (isi=96) → butuh 192 pcs dari stok.
     */
    public function isStokCukup(int $produkId, int $qty, int $isiSatuan = 1): bool
    {
        $produk = Produk::select('qty_stok')->find($produkId);

        if (! $produk) return false;

        return $produk->qty_stok >= ($qty * $isiSatuan);
    }

    /**
     * Cek apakah stok sudah di bawah atau sama dengan batas minimum.
     * Dipakai untuk highlight baris di tampilan stok kasir.
     */
    public function isStokRendah(int $produkId): bool
    {
        $produk = Produk::select('qty_stok', 'stok_minimum')->find($produkId);

        if (! $produk) return false;

        return $produk->qty_stok <= $produk->stok_minimum;
    }

    /**
     * Cari produk aktif berdasarkan kode barcode.
     */
    public function getByKode(string $kode): ?Produk
    {
        // Cari berdasarkan kode_produk ATAU barcode di produk_satuan.
        // Barcode disimpan di produk_satuan (bukan di tabel produk) karena setiap
        // satuan (pcs, dus, slop) bisa punya barcode berbeda — hanya satuan default
        // yang diisi barcode, satuan beli (karton/slop) dibiarkan null.
        return Produk::where('status', 'aktif')
            ->where(function ($q) use ($kode) {
                $q->where('kode_produk', $kode)
                  ->orWhereHas('satuanList', fn ($s) => $s->where('barcode', $kode));
            })
            ->with('satuanList')
            ->first();
    }
}
