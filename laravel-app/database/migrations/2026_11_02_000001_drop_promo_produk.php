<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus fitur Promo Produk dari skema database.
     *
     * Fitur promo dikeluarkan dari ruang lingkup aplikasi dan tidak ada di ERD.md,
     * sehingga tabel produk_promo serta kolom detail_transaksi.promo_id & is_bonus
     * dihapus agar skema kembali sesuai ERD.
     *
     * Migration pembuatnya sudah dihapus, jadi pada database baru (migrate:fresh)
     * objek-objek ini tidak pernah ada — karena itu setiap langkah dicek dulu
     * keberadaannya agar migration ini aman dijalankan di database lama maupun baru.
     *
     * Urutan penting: FK detail_transaksi.promo_id → produk_promo.id harus
     * dilepas lebih dulu sebelum tabel produk_promo bisa di-drop.
     */
    public function up(): void
    {
        if (Schema::hasColumn('detail_transaksi', 'promo_id')) {
            Schema::table('detail_transaksi', function (Blueprint $table) {
                $table->dropForeign(['promo_id']);
                $table->dropColumn('promo_id');
            });
        }

        if (Schema::hasColumn('detail_transaksi', 'is_bonus')) {
            Schema::table('detail_transaksi', function (Blueprint $table) {
                $table->dropColumn('is_bonus');
            });
        }

        Schema::dropIfExists('produk_promo');
    }

    /**
     * Tidak dibuat ulang — fitur promo sudah dikeluarkan dari aplikasi.
     */
    public function down(): void
    {
    }
};
