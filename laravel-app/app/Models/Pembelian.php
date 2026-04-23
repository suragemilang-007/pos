<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembelian extends Model
{
    // Laravel pluralize → 'pembelians', harus di-set manual
    protected $table = 'pembelian';

    protected $fillable = [
        'user_id',
        'supplier_id',
        'no_pembelian',
        'total',
        'status',
        'tanggal_pembelian',
    ];

    protected function casts(): array
    {
        return [
            'total'             => 'decimal:2',
            // DATE bukan datetime — hanya perlu tanggal dokumen PO
            'tanggal_pembelian' => 'date',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailPembelian::class, 'pembelian_id');
    }

    public function returPembelian(): HasMany
    {
        return $this->hasMany(ReturPembelian::class, 'pembelian_id');
    }
}
