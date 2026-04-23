<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailReturPenjualan extends Model
{
    protected $table = 'detail_retur_penjualan';

    public $timestamps = false;

    protected $fillable = [
        'retur_id',
        'produk_id',
        'nama_satuan',
        'isi_satuan',
        'qty_retur',
        'harga_satuan',
        'subtotal_refund',
    ];

    protected function casts(): array
    {
        return [
            // Semua nilai adalah snapshot dari transaksi asal — tidak berubah
            'harga_satuan'    => 'decimal:2',
            'subtotal_refund' => 'decimal:2',
        ];
    }

    public function returPenjualan(): BelongsTo
    {
        return $this->belongsTo(ReturPenjualan::class, 'retur_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
