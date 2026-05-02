<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk_promo', function (Blueprint $table) {
            $table->increments('id');

            // Produk induk yang punya promo ini
            $table->unsignedInteger('produk_id');

            // Satuan syarat beli — menentukan "beli berapa satuan apa"
            // Contoh: satuan=karton → beli 1 karton gratis 1 pcs
            $table->unsignedInteger('produk_satuan_id');

            // Teks deskriptif untuk ditampilkan kasir: "Beli 5 Gratis 1"
            $table->string('nama_promo', 200);

            // Tipe menentukan kolom mana yang wajib diisi (validasi di Service):
            // bonus_produk_sama  → wajib bonus_qty, bonus_produk_id NULL
            // bonus_produk_lain  → wajib bonus_qty + bonus_produk_id + bonus_satuan_id
            // diskon_persen      → wajib diskon_nilai (0-100), kolom bonus NULL
            // diskon_nominal     → wajib diskon_nilai (> 0), kolom bonus NULL
            $table->enum('tipe_promo', [
                'bonus_produk_sama',
                'bonus_produk_lain',
                'diskon_persen',
                'diskon_nominal',
            ]);

            // Minimum qty beli untuk memenuhi syarat promo
            $table->integer('min_qty')->default(1);

            // Qty barang bonus yang diberikan (NULL untuk tipe diskon)
            $table->integer('bonus_qty')->nullable();

            // Produk bonus berbeda (hanya untuk bonus_produk_lain)
            $table->unsignedInteger('bonus_produk_id')->nullable();

            // Satuan produk bonus (hanya untuk bonus_produk_lain)
            $table->unsignedInteger('bonus_satuan_id')->nullable();

            // Nilai diskon: persentase (0-100) atau nominal Rp (NULL untuk tipe bonus)
            $table->decimal('diskon_nilai', 10, 2)->nullable();

            // Catatan tambahan untuk kasir — ditampilkan di UI kasir saat promo aktif
            $table->text('keterangan')->nullable();

            // Toggle aktif/nonaktif tanpa hapus data historis
            $table->tinyInteger('is_active')->default(1);

            // NULL = berlaku mulai sekarang (tidak ada batas awal)
            $table->date('berlaku_dari')->nullable();

            // NULL = tidak ada batas akhir (berlaku selamanya selama is_active=1)
            $table->date('berlaku_sampai')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // ── Foreign Keys ──────────────────────────────────────────────────
            $table->foreign('produk_id')
                ->references('id')->on('produk')
                ->restrictOnDelete();

            $table->foreign('produk_satuan_id')
                ->references('id')->on('produk_satuan')
                ->restrictOnDelete();

            // Produk bonus — SET NULL jika produk bonus dihapus
            // (promo tetap ada, tapi bonus_produk_id hilang → Service akan menolak promo ini)
            $table->foreign('bonus_produk_id')
                ->references('id')->on('produk')
                ->nullOnDelete();

            $table->foreign('bonus_satuan_id')
                ->references('id')->on('produk_satuan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_promo');
    }
};
