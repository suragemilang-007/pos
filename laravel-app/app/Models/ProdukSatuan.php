<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProdukSatuan extends Model
{
    // Laravel pluralize → 'produk_satuans', harus di-set manual
    protected $table = 'produk_satuan';

    protected $fillable = [
        'produk_id',
        'nama_satuan',
        'isi',
        'harga_beli',
        'harga_jual',
        'is_default',
        'barcode',
    ];

    protected function casts(): array
    {
        return [
            'harga_beli' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            // is_default sebagai boolean untuk kemudahan cek di Blade/Service
            'is_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    // Relasi ke detail transaksi untuk melihat riwayat pemakaian satuan ini
    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'produk_satuan_id');
    }

    public function detailPembelian(): HasMany
    {
        return $this->hasMany(DetailPembelian::class, 'produk_satuan_id');
    }
}