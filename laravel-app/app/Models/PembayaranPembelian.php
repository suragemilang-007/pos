<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranPembelian extends Model
{
    protected $table = 'pembayaran_pembelian';

    // Tabel hanya punya created_at — record pembayaran immutable setelah dicatat
    const UPDATED_AT = null;

    protected $fillable = [
        'pembelian_id',
        'user_id',
        'jumlah',
        'metode',
        'tanggal_bayar',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah'       => 'decimal:2',
            'tanggal_bayar'=> 'date',
            'created_at'   => 'datetime',
        ];
    }

    // PO yang sedang dibayar (satu PO bisa banyak cicilan)
    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    // Admin yang mencatat pembayaran ini (audit trail)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
