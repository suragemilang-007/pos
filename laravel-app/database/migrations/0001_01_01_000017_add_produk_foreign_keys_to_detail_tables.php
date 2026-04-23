<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FK ke tabel produk dan produk_satuan tidak bisa ditambahkan saat PAKET 2
    // karena tabel produk baru dibuat di PAKET 3. Migration ini menyelesaikan
    // constraint yang ditunda dari detail_transaksi dan detail_retur_penjualan.
    public function up(): void
    {
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->foreign('produk_id')->references('id')->on('produk')->restrictOnDelete();
            // Nullable FK — produk_satuan bisa null jika kasir input manual tanpa memilih satuan
            $table->foreign('produk_satuan_id')->references('id')->on('produk_satuan')->nullOnDelete();
        });

        Schema::table('detail_retur_penjualan', function (Blueprint $table) {
            $table->foreign('produk_id')->references('id')->on('produk')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->dropForeign(['produk_id']);
            $table->dropForeign(['produk_satuan_id']);
        });

        Schema::table('detail_retur_penjualan', function (Blueprint $table) {
            $table->dropForeign(['produk_id']);
        });
    }
};
