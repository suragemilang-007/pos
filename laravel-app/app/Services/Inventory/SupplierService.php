<?php

namespace App\Services\Inventory;

use App\Models\Pembelian;
use App\Models\Supplier;

class SupplierService
{
    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) Supplier::where('id', $id)->update($data);
    }

    /**
     * Cek apakah supplier pernah memiliki Purchase Order.
     * Supplier yang pernah di PO tidak boleh di-hard delete —
     * riwayat pembelian harus tetap ada untuk laporan keuangan dan audit.
     */
    public function hasPembelian(int $supplierId): bool
    {
        return Pembelian::where('supplier_id', $supplierId)->exists();
    }

    public function softDelete(int $id): bool
    {
        return (bool) Supplier::where('id', $id)->update(['status' => 'nonaktif']);
    }

    public function hardDelete(int $id): bool
    {
        return (bool) Supplier::destroy($id);
    }

    /**
     * Smart delete: soft jika pernah ada PO, hard jika belum.
     *
     * @return string  'soft' | 'hard'
     */
    public function destroy(int $id): string
    {
        if ($this->hasPembelian($id)) {
            $this->softDelete($id);
            return 'soft';
        }

        $this->hardDelete($id);
        return 'hard';
    }
}
