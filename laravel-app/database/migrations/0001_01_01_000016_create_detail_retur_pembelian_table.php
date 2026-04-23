<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_retur_pembelian', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('retur_pembelian_id');
            $table->unsignedInteger('produk_id');
            // Snapshot dari detail_pembelian — bukan nilai terkini dari produk_satuan
            $table->string('nama_satuan', 30)->default('pcs');
            // Snapshot isi satuan — Consumer pakai ini: qty_stok_berkurang = qty_retur x isi_satuan
            $table->integer('isi_satuan')->default(1);
            $table->integer('qty_retur');
            // Snapshot harga dari detail_pembelian asal
            $table->decimal('harga_satuan', 10, 2);
            // subtotal_kredit = harga_satuan x qty_retur
            $table->decimal('subtotal_kredit', 10, 2);

            // Tidak ada timestamps — tabel ini immutable setelah insert
            $table->foreign('retur_pembelian_id')->references('id')->on('retur_pembelian')->restrictOnDelete();
            $table->foreign('produk_id')->references('id')->on('produk')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_retur_pembelian');
    }
};
