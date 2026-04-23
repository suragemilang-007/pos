<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_retur_penjualan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('retur_id');
            // FK ke produk ditambahkan di migration PAKET 3 — tabel produk belum ada
            $table->unsignedInteger('produk_id');
            // Snapshot dari transaksi asal — bukan dari produk_satuan saat ini
            // Melindungi data historis jika satuan diedit di kemudian hari
            $table->string('nama_satuan', 30)->default('pcs');
            // Snapshot isi satuan — Consumer pakai ini: qty_stok_bertambah = qty_retur x isi_satuan
            $table->integer('isi_satuan')->default(1);
            $table->integer('qty_retur');
            // Snapshot harga dari detail_transaksi asal
            $table->decimal('harga_satuan', 10, 2);
            // subtotal_refund = harga_satuan x qty_retur
            $table->decimal('subtotal_refund', 10, 2);

            // Tidak ada timestamps — tabel ini immutable setelah insert
            $table->foreign('retur_id')->references('id')->on('retur_penjualan')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_retur_penjualan');
    }
};
