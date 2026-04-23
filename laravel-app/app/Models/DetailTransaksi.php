<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailTransaksi extends Model
{
    protected $table = 'detail_transaksi';

    // Tidak ada timestamps sama sekali — baris ini immutable setelah insert
    public $timestamps = false;

    protected $fillable = [
        'transaksi_id',
        'produk_id',
        'produk_satuan_id',
        'nama_satuan',
        'isi_satuan',
        'qty',
        'harga_satuan',
        'diskon_item',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            // harga_satuan adalah snapshot — nilai ini tidak berubah meski harga produk berubah
            'harga_satuan' => 'decimal:2',
            'diskon_item'  => 'decimal:2',
            'subtotal'     => 'decimal:2',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function produkSatuan(): BelongsTo
    {
        // nullable — produk_satuan_id bisa null jika kasir input manual
        return $this->belongsTo(ProdukSatuan::class, 'produk_satuan_id');
    }
}
