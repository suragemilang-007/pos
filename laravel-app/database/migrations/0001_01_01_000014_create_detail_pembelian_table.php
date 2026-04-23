<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pembelian', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pembelian_id');
            $table->unsignedInteger('produk_id');
            // Nullable — jika satuan pembelian tidak terdaftar di produk_satuan
            $table->unsignedInteger('produk_satuan_id')->nullable();
            // Snapshot nama satuan — melindungi data historis dari perubahan satuan
            $table->string('nama_satuan', 30)->default('pcs');
            // Snapshot isi satuan — Consumer pakai ini: qty_stok_bertambah = qty x isi_satuan
            $table->integer('isi_satuan')->default(1);
            $table->integer('qty');
            // Harga beli per unit satuan ini saat PO dibuat (bukan harga_beli_dasar di produk)
            $table->decimal('harga_beli_satuan', 10, 2);
            // subtotal = harga_beli_satuan x qty
            $table->decimal('subtotal', 10, 2);

            // Tidak ada timestamps — tabel ini immutable setelah insert
            $table->foreign('pembelian_id')->references('id')->on('pembelian')->restrictOnDelete();
            $table->foreign('produk_id')->references('id')->on('produk')->restrictOnDelete();
            $table->foreign('produk_satuan_id')->references('id')->on('produk_satuan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pembelian');
    }
};
