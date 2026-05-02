<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdukPromo extends Model
{
    protected $table = 'produk_promo';

    protected $fillable = [
        'produk_id',
        'produk_satuan_id',
        'nama_promo',
        'tipe_promo',
        'min_qty',
        'bonus_qty',
        'bonus_produk_id',
        'bonus_satuan_id',
        'diskon_nilai',
        'keterangan',
        'is_active',
        'berlaku_dari',
        'berlaku_sampai',
    ];

    protected function casts(): array
    {
        return [
            // Sesuai CLASS_DIAGRAM.md §MODEL ELOQUENT ProdukPromo
            'is_active'      => 'boolean',
            'min_qty'        => 'integer',
            'bonus_qty'      => 'integer',
            'diskon_nilai'   => 'decimal:2',
            // DATE columns — cast ke string format Y-m-d agar konsisten
            'berlaku_dari'   => 'date:Y-m-d',
            'berlaku_sampai' => 'date:Y-m-d',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }

    // ── Scope ─────────────────────────────────────────────────────────────────

    /**
     * Promo yang berlaku hari ini.
     * Query sesuai CLASS_DIAGRAM.md PromoService::getPromoAktif():
     *   WHERE is_active = 1
     *     AND (berlaku_dari IS NULL OR berlaku_dari <= CURDATE())
     *     AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query
            ->where('is_active', 1)
            ->where(fn (Builder $q) => $q
                ->whereNull('berlaku_dari')
                ->orWhereDate('berlaku_dari', '<=', today())
            )
            ->where(fn (Builder $q) => $q
                ->whereNull('berlaku_sampai')
                ->orWhereDate('berlaku_sampai', '>=', today())
            );
    }

    // ── Relasi ────────────────────────────────────────────────────────────────

    // Produk yang memiliki promo ini
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    // Satuan syarat beli — "beli N [satuan ini] baru dapat promo"
    public function satuanSyarat(): BelongsTo
    {
        return $this->belongsTo(ProdukSatuan::class, 'produk_satuan_id');
    }

    // Produk bonus — hanya untuk tipe bonus_produk_lain, nullable untuk tipe lain
    public function bonusProduk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'bonus_produk_id');
    }

    // Satuan produk bonus — hanya untuk tipe bonus_produk_lain, nullable untuk tipe lain
    public function bonusSatuan(): BelongsTo
    {
        return $this->belongsTo(ProdukSatuan::class, 'bonus_satuan_id');
    }
}
