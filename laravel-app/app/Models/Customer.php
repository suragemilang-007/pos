<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'nama',
        'no_telp',
        'alamat',
        'tipe_customer',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'customer_id');
    }
}
