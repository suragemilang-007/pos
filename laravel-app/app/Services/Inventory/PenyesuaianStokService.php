<?php

namespace App\Services\Inventory;

use App\Models\Produk;
use App\Models\PenyesuaianStok;
use App\Services\Kafka\KafkaProducerService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * PenyesuaianStokService — logika penyesuaian stok manual (AD-20).
 *
 * Dipakai untuk kondisi fisik barang di gudang yang tidak berasal dari
 * transaksi penjualan/pembelian/retur:
 *   - barang_rusak, barang_hilang, barang_expired → kurangi stok
 *   - koreksi_plus  → tambah stok (selisih positif setelah opname)
 *   - koreksi_minus → kurangi stok (selisih negatif setelah opname)
 *
 * Alur: INSERT PENDING → publish Kafka → Consumer UPDATE qty_stok → COMPLETED.
 * Sama dengan transaksi — stok diubah oleh Consumer, bukan Service ini.
 */
class PenyesuaianStokService
{
    public function __construct(
        private readonly KafkaProducerService $kafkaProducer
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Buat record penyesuaian stok baru dan publish ke Kafka.
     *
     * @param  array  $data  { produk_id, tipe, qty_satuan_dasar, keterangan }
     * @param  int    $userId
     * @throws \RuntimeException        kode 404 jika produk tidak aktif
     * @throws \Illuminate\Validation\ValidationException kode 422 jika qty > stok
     */
    public function create(array $data, int $userId): PenyesuaianStok
    {
        // ── Validasi 1: Produk harus ada dan status='aktif' ───────────────────
        $produk = Produk::where('id', $data['produk_id'])
            ->where('status', 'aktif')
            ->first();

        if (! $produk) {
            throw new \RuntimeException(
                "Produk tidak ditemukan atau sudah tidak aktif (id: {$data['produk_id']})",
                404
            );
        }

        // ── Validasi 2: qty_satuan_dasar harus > 0 ────────────────────────────
        $qty = (int) $data['qty_satuan_dasar'];

        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty_satuan_dasar' => ['Qty harus lebih dari 0'],
            ]);
        }

        // ── Validasi 3: Cegah stok negatif untuk tipe yang mengurangi stok ────
        // Validasi WAJIB dilakukan di sini (aplikatif) sebelum Kafka,
        // agar admin mendapat pesan error yang jelas — bukan nunggu Consumer gagal.
        // Consumer JUGA melakukan double-check sebagai last-resort safety net.
        if ($this->isTipeKurang($data['tipe'])) {
            $stokSaatIni = (int) $produk->qty_stok;

            if ($qty > $stokSaatIni) {
                throw ValidationException::withMessages([
                    'qty_satuan_dasar' => [
                        "Qty melebihi stok tersedia " .
                        "(stok saat ini: {$stokSaatIni} {$produk->satuan_dasar})",
                    ],
                ]);
            }
        }

        // ── INSERT penyesuaian_stok status='PENDING' ──────────────────────────
        $penyesuaian = PenyesuaianStok::create([
            'produk_id'       => $produk->id,
            'user_id'         => $userId,
            'tipe'            => $data['tipe'],
            'qty_satuan_dasar'=> $qty,
            'keterangan'      => $data['keterangan'],
            'status'          => 'PENDING',
        ]);

        // ── Publish ke Kafka — Consumer yang UPDATE qty_stok ─────────────────
        $this->kafkaProducer->publishStockAdjustment($penyesuaian);

        Log::info('[PENYESUAIAN STOK] Created', [
            'id'              => $penyesuaian->id,
            'produk_id'       => $produk->id,
            'produk'          => $produk->nama_produk,
            'tipe'            => $data['tipe'],
            'qty_satuan_dasar'=> $qty,
            'stok_sebelumnya' => $produk->qty_stok,
            'user_id'         => $userId,
        ]);

        return $penyesuaian;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // READ
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ambil riwayat penyesuaian stok dengan filter opsional.
     *
     * filters: { produk_id?, tipe?, dari?, sampai? }
     * Relasi produk dan user di-eager load untuk menghindari N+1.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(array $filters = [], int $limit = 20): LengthAwarePaginator
    {
        $query = PenyesuaianStok::with([
            'produk:id,kode_produk,nama_produk,satuan_dasar',
            'user:id,nama',
        ]);

        if (! empty($filters['produk_id'])) {
            $query->where('produk_id', (int) $filters['produk_id']);
        }

        if (! empty($filters['tipe'])) {
            $query->where('tipe', $filters['tipe']);
        }

        if (! empty($filters['dari'])) {
            $query->whereDate('created_at', '>=', $filters['dari']);
        }

        if (! empty($filters['sampai'])) {
            $query->whereDate('created_at', '<=', $filters['sampai']);
        }

        return $query->orderByDesc('created_at')->paginate($limit);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Apakah tipe ini akan mengurangi stok?
     * Dipakai create() untuk validasi anti-stok-negatif
     * dan Consumer untuk menentukan arah UPDATE.
     */
    public function isTipeKurang(string $tipe): bool
    {
        return in_array($tipe, [
            'barang_rusak',
            'barang_hilang',
            'barang_expired',
            'koreksi_minus',
        ]);
    }

    /**
     * Apakah tipe ini akan menambah stok?
     * Kebalikan dari isTipeKurang() — disediakan agar caller tidak hardcode string.
     */
    public function isTipeTambah(string $tipe): bool
    {
        return $tipe === 'koreksi_plus';
    }
}
