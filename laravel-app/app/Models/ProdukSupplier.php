<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model eksplisit untuk tabel junction produk_supplier.
// Berguna saat perlu query langsung ke junction (misal: set supplier utama)
// tanpa harus melalui relasi BelongsToMany di Produk/Supplier.
class ProdukSupplier extends Model
{
    protected $table = 'produk_supplier';

    const UPDATED_AT = null;

    protected $fillable = [
        'produk_id',
        'supplier_id',
        'harga_suplai',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'harga_suplai' => 'decimal:2',
            'is_primary'   => 'boolean',
            'created_at'   => 'datetime',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
