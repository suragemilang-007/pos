<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    // Tabel hanya punya created_at, tidak ada updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'nama_role',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            // JSON array izin akses — disimpan sebagai array PHP
            'permissions' => 'array',
            'created_at'  => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
