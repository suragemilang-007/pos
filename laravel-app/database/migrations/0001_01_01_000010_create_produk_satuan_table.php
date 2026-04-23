<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk_satuan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produk_id');
            // Contoh: pcs, renteng, karton, lusin, kg
            $table->string('nama_satuan', 30);
            // Berapa satuan_dasar dalam 1 unit satuan ini
            // Contoh: karton kopi = 96 pcs, renteng = 10 pcs
            $table->integer('isi')->default(1);
            $table->decimal('harga_beli', 10, 2)->default(0.00);
            // Harga jual per unit satuan ini — semakin besar satuan, harga efektif makin murah
            $table->decimal('harga_jual', 10, 2)->default(0.00);
            // 1 = satuan yang tampil default di form transaksi kasir
            $table->tinyInteger('is_default')->default(0);
            // Barcode khusus satuan ini (opsional) — misal barcode khusus untuk karton
            $table->string('barcode', 50)->unique()->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Satu produk tidak boleh punya dua entri dengan nama satuan yang sama
            $table->unique(['produk_id', 'nama_satuan'], 'uq_produk_satuan');

            $table->foreign('produk_id')->references('id')->on('produk')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_satuan');
    }
};
