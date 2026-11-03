<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('transaksi_id');
            // FK ke produk dan produk_satuan ditambahkan di migration PAKET 3,
            // karena tabel produk belum ada saat migration PAKET 2 dijalankan
            $table->unsignedInteger('produk_id');
            $table->unsignedInteger('produk_satuan_id')->nullable();
            // Snapshot nama satuan — disimpan agar riwayat tidak berubah
            // jika admin mengedit satuan di kemudian hari
            $table->string('nama_satuan', 30)->default('pcs');
            // Snapshot isi satuan — dipakai Consumer untuk konversi stok:
            // qty_stok_berkurang = qty x isi_satuan
            $table->integer('isi_satuan')->default(1);
            $table->integer('qty');
            // Snapshot harga saat transaksi — BUKAN FK, nilai ini tidak boleh berubah
            $table->decimal('harga_satuan', 10, 2);
            // subtotal = harga_satuan x qty
            $table->decimal('subtotal', 10, 2);

            // Tidak ada timestamps — tabel ini immutable setelah insert
            $table->foreign('transaksi_id')->references('id')->on('transaksi')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_transaksi');
    }
};
