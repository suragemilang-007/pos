<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailReturPembelian extends Model
{
    protected $table = 'detail_retur_pembelian';

    public $timestamps = false;

    protected $fillable = [
        'retur_pembelian_id',
        'produk_id',
        'nama_satuan',
        'isi_satuan',
        'qty_retur',
        'harga_satuan',
        'subtotal_kredit',
    ];

    protected function casts(): array
    {
        return [
            // Snapshot dari detail_pembelian asal — nilai tidak berubah
            'harga_satuan'    => 'decimal:2',
            'subtotal_kredit' => 'decimal:2',
        ];
    }

    public function returPembelian(): BelongsTo
    {
        return $this->belongsTo(ReturPembelian::class, 'retur_pembelian_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
