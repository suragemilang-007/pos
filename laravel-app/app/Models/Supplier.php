<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'nama_supplier',
        'kontak_person',
        'no_telp',
        'alamat',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function produk(): BelongsToMany
    {
        return $this->belongsToMany(Produk::class, 'produk_supplier', 'supplier_id', 'produk_id')
            ->withPivot(['harga_suplai', 'is_primary'])
            ->withTimestamps();
    }

    public function pembelian(): HasMany
    {
        return $this->hasMany(Pembelian::class, 'supplier_id');
    }
}
