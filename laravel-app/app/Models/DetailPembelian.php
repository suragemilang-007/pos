<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPembelian extends Model
{
    protected $table = 'detail_pembelian';

    public $timestamps = false;

    protected $fillable = [
        'pembelian_id',
        'produk_id',
        'produk_satuan_id',
        'nama_satuan',
        'isi_satuan',
        'qty',
        'harga_beli_satuan',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            // Snapshot harga beli per unit satuan saat PO dibuat
            'harga_beli_satuan' => 'decimal:2',
            'subtotal'          => 'decimal:2',
        ];
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function produkSatuan(): BelongsTo
    {
        // nullable — jika satuan tidak terdaftar di produk_satuan
        return $this->belongsTo(ProdukSatuan::class, 'produk_satuan_id');
    }
}
