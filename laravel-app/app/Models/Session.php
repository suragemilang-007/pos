<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    // Tabel hanya punya created_at — expired_at bukan timestamp sistem
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'token',
        'status',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            // expired_at perlu di-cast datetime untuk perbandingan Carbon
            'expired_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Helper: cek apakah session masih valid
    public function isValid(): bool
    {
        return $this->status === 'active' && $this->expired_at->isFuture();
    }
}
