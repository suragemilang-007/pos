<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    // Laravel pluralize → 'produks', harus di-set manual
    protected $table = 'produk';

    protected $fillable = [
        'kategori_id',
        'kode_produk',
        'nama_produk',
        'deskripsi',
        'harga_beli_dasar',
        'qty_stok',
        'stok_minimum',
        'satuan_dasar',
        'status',
        // qty_stok TIDAK diubah via mass assignment — hanya Kafka Consumer yang boleh update stok
    ];

    protected function casts(): array
    {
        return [
            'harga_beli_dasar' => 'decimal:2',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
        ];
    }

    // Scope default: hanya tampilkan produk aktif.
    // Gunakan Produk::aktif()->get() atau Produk::aktif()->where(...)->first()
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    // Scope untuk cek stok kritis — qty_stok di bawah stok_minimum
    public function scopeStokKritis(Builder $query): Builder
    {
        return $query->whereColumn('qty_stok', '<', 'stok_minimum');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriProduk::class, 'kategori_id');
    }

    public function satuan(): HasMany
    {
        return $this->hasMany(ProdukSatuan::class, 'produk_id');
    }

    // Satuan terurut: default tampil pertama, lalu urut isi ASC (pcs < renteng < karton)
    // Dipakai ProdukService::getByKode() dan endpoint cekStok
    public function satuanList(): HasMany
    {
        return $this->hasMany(ProdukSatuan::class, 'produk_id')
            ->orderByDesc('is_default')
            ->orderBy('isi');
    }

    public function satuanDefault(): HasMany
    {
        // Shortcut untuk mengambil satuan yang is_default = 1
        return $this->hasMany(ProdukSatuan::class, 'produk_id')->where('is_default', 1);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'produk_supplier', 'produk_id', 'supplier_id')
            ->withPivot(['harga_suplai', 'is_primary'])
            ->withTimestamps();
    }

    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'produk_id');
    }

    public function detailPembelian(): HasMany
    {
        return $this->hasMany(DetailPembelian::class, 'produk_id');
    }

    // Semua promo produk ini (aktif maupun tidak) — untuk halaman kelola promo
    public function promo(): HasMany
    {
        return $this->hasMany(ProdukPromo::class, 'produk_id');
    }

    // Promo yang sedang berlaku hari ini — dipakai endpoint cekStok untuk kasir
    // Scope aktif: is_active=1 AND periode berlaku mencakup hari ini
    public function promoAktif(): HasMany
    {
        return $this->hasMany(ProdukPromo::class, 'produk_id')->aktif();
    }
}
