<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus kolom diskon dari transaksi penjualan.
     *
     * Fitur diskon dikeluarkan dari ruang lingkup aplikasi bersama fitur promo,
     * sehingga kolom transaksi.diskon dan detail_transaksi.diskon_item dihapus
     * (ERD.md sudah diperbarui). Setelah ini: subtotal detail = harga_satuan × qty
     * dan total transaksi = subtotal.
     *
     * Migration pembuat tabel sudah tidak membuat kolom ini, jadi pada database
     * baru (migrate:fresh) kolomnya tidak pernah ada — karena itu dicek dulu
     * keberadaannya agar migration ini aman di database lama maupun baru.
     *
     * Nilai total & subtotal transaksi lama TIDAK diubah: angka yang sudah
     * tercatat adalah nilai yang benar-benar dibayar pelanggan saat itu.
     */
    public function up(): void
    {
        if (Schema::hasColumn('detail_transaksi', 'diskon_item')) {
            Schema::table('detail_transaksi', function (Blueprint $table) {
                $table->dropColumn('diskon_item');
            });
        }

        if (Schema::hasColumn('transaksi', 'diskon')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn('diskon');
            });
        }
    }

    /**
     * Tidak dibuat ulang — fitur diskon sudah dikeluarkan dari aplikasi.
     */
    public function down(): void
    {
    }
};
