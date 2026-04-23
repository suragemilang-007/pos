<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel junction N:M antara produk dan suppliers
        // Ditambah kolom bisnis: harga_suplai dan is_primary
        Schema::create('produk_supplier', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produk_id');
            $table->unsignedInteger('supplier_id');
            // Harga beli dari supplier ini per satuan_dasar
            // Bisa berbeda antar supplier untuk produk yang sama
            $table->decimal('harga_suplai', 10, 2)->default(0.00);
            // 1 = supplier utama untuk produk ini — dipakai saat buat PO otomatis
            $table->tinyInteger('is_primary')->default(0);
            $table->timestamp('created_at')->useCurrent();

            // Satu produk tidak boleh didaftarkan dua kali ke supplier yang sama
            $table->unique(['produk_id', 'supplier_id'], 'uq_produk_supplier');

            $table->foreign('produk_id')->references('id')->on('produk')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_supplier');
    }
};
