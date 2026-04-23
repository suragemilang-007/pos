<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturPenjualan extends Model
{
    protected $table = 'retur_penjualan';

    // Tidak ada updated_at — status berubah sekali: PENDING → COMPLETED/REJECTED
    const UPDATED_AT = null;

    protected $fillable = [
        'transaksi_id',
        'user_id',
        'no_retur',
        'alasan',
        'total_refund',
        'status',
        'tanggal_retur',
    ];

    protected function casts(): array
    {
        return [
            'total_refund'  => 'decimal:2',
            'tanggal_retur' => 'datetime',
            'created_at'    => 'datetime',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details(): HasMany
    {
        // Foreign key di tabel detail_retur_penjualan adalah 'retur_id'
        return $this->hasMany(DetailReturPenjualan::class, 'retur_id');
    }
}
