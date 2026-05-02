<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom promo_id dan is_bonus ke tabel detail_transaksi.
     *
     * Perubahan v3 — mendukung fitur Promo Produk (CLAUDE.md §4):
     * - promo_id: audit promo yang diterapkan pada item ini (nullable — item reguler = NULL)
     * - is_bonus: 1 = item gratis dari promo, harga_satuan=0, stok tetap dikurangi
     *
     * Migration ini harus berjalan SETELAH 2026_05_02_000002_create_produk_promo_table
     * karena FK promo_id → produk_promo.id
     */
    public function up(): void
    {
        Schema::table('detail_transaksi', function (Blueprint $table) {
            // Promo yang diterapkan pada item ini — NULL untuk item reguler tanpa promo
            // ON DELETE SET NULL: jika promo dihapus, riwayat transaksi tetap ada
            // (snapshot sudah ada di kolom lain — promo_id hanya untuk audit/laporan)
            $table->unsignedInteger('promo_id')->nullable()->after('produk_satuan_id');

            // 1 = item bonus gratis dari promo (harga_satuan=0)
            // Item bonus TIDAK dikembalikan stoknya saat void (CLAUDE.md §3 void rule)
            $table->tinyInteger('is_bonus')->default(0)->after('promo_id');

            $table->foreign('promo_id')
                ->references('id')->on('produk_promo')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->dropForeign(['promo_id']);
            $table->dropColumn(['promo_id', 'is_bonus']);
        });
    }
};
