<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaksi extends Model
{
    // Nama tabel eksplisit — Laravel akan salah pluralize menjadi 'transaksis'
    protected $table = 'transaksi';

    // Flag non-persistent — diisi Service jika Kafka publish gagal setelah DB commit.
    // Tidak ada di $fillable sehingga tidak pernah tersimpan ke database.
    public bool $kafkaFailed = false;

    // Tidak ada updated_at di tabel ini — transaksi immutable setelah Consumer selesai
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'customer_id',
        'no_transaksi',
        'subtotal',
        'diskon',
        'total',
        'bayar',
        'kembalian',
        'metode_bayar',
        'status',
        'tanggal_transaksi',
    ];

    protected function casts(): array
    {
        return [
            // Semua nilai keuangan di-cast decimal:2 untuk konsistensi DECIMAL(10,2) MySQL
            'subtotal'           => 'decimal:2',
            'diskon'             => 'decimal:2',
            'total'              => 'decimal:2',
            'bayar'              => 'decimal:2',
            'kembalian'          => 'decimal:2',
            'tanggal_transaksi'  => 'datetime',
            'created_at'         => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        // nullable — transaksi walk-in boleh tanpa customer
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'transaksi_id');
    }

    public function returPenjualan(): HasMany
    {
        return $this->hasMany(ReturPenjualan::class, 'transaksi_id');
    }

    // Satu transaksi maksimal satu void — UNIQUE KEY uq_void_transaksi di DB menjamin ini
    public function voidTransaksi(): HasOne
    {
        return $this->hasOne(VoidTransaksi::class, 'transaksi_id');
    }
}
