<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenyesuaianStok extends Model
{
    protected $table = 'penyesuaian_stok';

    // Tabel hanya punya created_at — record penyesuaian tidak diedit setelah dibuat
    const UPDATED_AT = null;

    protected $fillable = [
        'produk_id',
        'user_id',
        'tipe',
        'qty_satuan_dasar',
        'keterangan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'qty_satuan_dasar' => 'integer',
            'created_at'       => 'datetime',
        ];
    }

    // ── Relasi ────────────────────────────────────────────────────────────────

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Accessor ──────────────────────────────────────────────────────────────

    /**
     * Apakah tipe ini menambah stok?
     * Hanya koreksi_plus yang menambah — semua tipe lain mengurangi.
     * Dipakai Consumer dan Service untuk menentukan arah UPDATE qty_stok.
     */
    public function getIsTipeTambahAttribute(): bool
    {
        return $this->tipe === 'koreksi_plus';
    }

    /**
     * Apakah tipe ini mengurangi stok?
     * Menggunakan in_array eksplisit (sesuai CLASS_DIAGRAM.md) agar
     * penambahan tipe baru di masa depan tidak mempengaruhi logika secara diam-diam.
     */
    public function getIsTipeKurangAttribute(): bool
    {
        return in_array($this->tipe, [
            'barang_rusak',
            'barang_hilang',
            'barang_expired',
            'koreksi_minus',
        ]);
    }
}
