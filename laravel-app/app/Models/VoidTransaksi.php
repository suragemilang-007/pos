<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoidTransaksi extends Model
{
    protected $table = 'void_transaksi';

    // Tabel hanya punya created_at — void tidak diubah setelah COMPLETED/REJECTED
    const UPDATED_AT = null;

    protected $fillable = [
        'transaksi_id',
        'transaksi_baru_id',
        'user_id',
        'alasan',
        'keterangan',
        'status',
        'batas_waktu',
    ];

    protected function casts(): array
    {
        return [
            'batas_waktu' => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    // Transaksi yang di-void (asal)
    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    // Transaksi koreksi pengganti — nullable, hanya ada jika kasir membuat transaksi ulang
    public function transaksiBaruId(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_baru_id');
    }

    // Kasir yang mengajukan void
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
