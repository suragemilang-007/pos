<?php

namespace App\Services\Inventory;

use App\Models\Customer;
use App\Models\Transaksi;

class CustomerService
{
    // ─────────────────────────────────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────────────────────────────────

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(int $customerId, array $data): bool
    {
        return (bool) Customer::where('id', $customerId)->update($data);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE (soft / hard)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cek apakah customer pernah bertransaksi.
     * Customer yang pernah bertransaksi tidak boleh di-hard delete —
     * data riwayat harus tetap ada untuk keperluan laporan dan audit.
     */
    public function hasTransaksi(int $customerId): bool
    {
        return Transaksi::where('customer_id', $customerId)->exists();
    }

    public function softDelete(int $customerId): bool
    {
        return (bool) Customer::where('id', $customerId)->update(['status' => 'nonaktif']);
    }

    public function hardDelete(int $customerId): bool
    {
        return (bool) Customer::destroy($customerId);
    }

    /**
     * Smart delete: soft jika pernah transaksi, hard jika belum.
     *
     * @return string  'soft' | 'hard'
     */
    public function destroy(int $customerId): string
    {
        if ($this->hasTransaksi($customerId)) {
            $this->softDelete($customerId);
            return 'soft';
        }

        $this->hardDelete($customerId);
        return 'hard';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RIWAYAT BELANJA
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Riwayat transaksi customer dengan pagination.
     *
     * Agregasi total_belanja dan frekuensi hanya dari transaksi COMPLETED —
     * transaksi PENDING/FAILED tidak dihitung karena belum/tidak jadi dibayar.
     * Sedangkan riwayat list menampilkan semua status agar kasir bisa lihat
     * transaksi yang gagal pun jika dibutuhkan.
     *
     * jumlah_item menggunakan withCount('details') — satu query eager load,
     * tidak N+1.
     *
     * @return array  {riwayat: paginated, total_belanja: float, frekuensi: int, total_page: int}
     */
    public function getRiwayatBelanja(int $customerId, int $page = 1, int $limit = 20): array
    {
        $paginated = Transaksi::withCount('details as jumlah_item')
            ->where('customer_id', $customerId)
            ->orderByDesc('tanggal_transaksi')
            ->paginate($limit, ['*'], 'page', $page);

        $riwayat = $paginated->getCollection()->map(fn ($t) => [
            'id'           => $t->id,
            'no_transaksi' => $t->no_transaksi,
            'tanggal'      => $t->tanggal_transaksi,
            'total'        => (float) $t->total,
            'metode_bayar' => $t->metode_bayar,
            'status'       => $t->status,
            'jumlah_item'  => $t->jumlah_item,
        ]);

        return [
            'riwayat'       => $riwayat,
            'total_belanja' => $this->getTotalBelanja($customerId),
            'frekuensi'     => $this->getFrekuensiBelanja($customerId),
            'total_page'    => $paginated->lastPage(),
        ];
    }

    /**
     * Total nilai belanja dari semua transaksi COMPLETED customer.
     */
    public function getTotalBelanja(int $customerId): float
    {
        return (float) Transaksi::where('customer_id', $customerId)
            ->where('status', 'COMPLETED')
            ->sum('total');
    }

    /**
     * Jumlah transaksi COMPLETED customer.
     */
    public function getFrekuensiBelanja(int $customerId): int
    {
        return Transaksi::where('customer_id', $customerId)
            ->where('status', 'COMPLETED')
            ->count();
    }
}
