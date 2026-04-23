<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('kategori_id');
            // Barcode untuk satuan terkecil (satuan_dasar)
            $table->string('kode_produk', 20)->unique();
            $table->string('nama_produk', 200);
            $table->text('deskripsi')->nullable();
            // Harga beli per satuan_dasar — harga jual ada di tabel produk_satuan
            $table->decimal('harga_beli_dasar', 10, 2)->default(0.00);
            // Stok SELALU dalam satuan_dasar — diperbarui oleh Kafka Consumer,
            // BUKAN oleh Controller atau Service Laravel
            $table->integer('qty_stok')->default(0);
            // Dalam satuan_dasar — trigger warning stok kritis di Consumer
            $table->integer('stok_minimum')->default(0);
            // Satuan terkecil: pcs, gram, ml, dll — VARCHAR bukan ENUM karena fleksibel
            $table->string('satuan_dasar', 30)->default('pcs');
            // Soft delete via status — tidak boleh hard delete jika ada transaksi
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('kategori_id')->references('id')->on('kategori_produk')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
