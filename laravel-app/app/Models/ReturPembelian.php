<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturPembelian extends Model
{
    protected $table = 'retur_pembelian';

    // Tidak ada updated_at — status berubah sekali: PENDING → COMPLETED/REJECTED
    const UPDATED_AT = null;

    protected $fillable = [
        'pembelian_id',
        'user_id',
        'no_retur',
        'alasan',
        'total_kredit',
        'status',
        'tanggal_retur',
    ];

    protected function casts(): array
    {
        return [
            // total_kredit = kredit dari supplier ke toko, bukan refund ke customer
            'total_kredit'  => 'decimal:2',
            'tanggal_retur' => 'datetime',
            'created_at'    => 'datetime',
        ];
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailReturPembelian::class, 'retur_pembelian_id');
    }
}
