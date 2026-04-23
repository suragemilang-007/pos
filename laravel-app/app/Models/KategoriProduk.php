<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriProduk extends Model
{
    // Laravel pluralize → 'kategori_produks', harus di-set manual
    protected $table = 'kategori_produk';

    const UPDATED_AT = null;

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }
}
