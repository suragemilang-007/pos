<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Extend Model biasa — BUKAN Authenticatable.
// Autentikasi ditangani JWT custom di SessionService, bukan Laravel Auth.
class User extends Model
{
    // Tabel hanya punya created_at, tidak ada updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'role_id',
        'nama',
        'username',
        'status',
        'last_login',
        // password_hash SENGAJA TIDAK ADA di $fillable —
        // wajib di-set eksplisit via $user->password_hash = Hash::make($plain)
        // agar tidak bisa di-mass assign dari request
    ];

    // Pastikan password_hash tidak pernah muncul di JSON response apapun
    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_login'  => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'user_id');
    }

    public function returPenjualan(): HasMany
    {
        return $this->hasMany(ReturPenjualan::class, 'user_id');
    }

    public function pembelian(): HasMany
    {
        return $this->hasMany(Pembelian::class, 'user_id');
    }

    public function returPembelian(): HasMany
    {
        return $this->hasMany(ReturPembelian::class, 'user_id');
    }
}
