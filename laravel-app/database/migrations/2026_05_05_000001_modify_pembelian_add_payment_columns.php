<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom hutang dagang ke tabel pembelian (ERD v4.0).
     *
     * Kolom disisipkan setelah `total` agar urutan kolom di DESCRIBE
     * konsisten dengan dokumentasi ERD. MySQL mendukung AFTER pada ALTER TABLE.
     *
     * Mengapa ini migration terpisah (bukan ubah migration lama)?
     * Tabel pembelian sudah ada di production/development — mengubah migration
     * lama akan rusak jika sudah di-migrate. Migration baru adalah cara aman.
     *
     * Kolom yang ditambahkan (sesuai CLAUDE.md §7.1 Zero Hallucination DB):
     *   tipe_pembayaran   : cash | kredit
     *   jumlah_dp         : DP yang dibayar saat terima barang
     *   sisa_hutang       : total - jumlah_dp (dihitung Service, bukan DB trigger)
     *   tanggal_jatuh_tempo: wajib diisi jika kredit, NULL untuk cash
     *   status_pembayaran : lunas | belum_lunas | sebagian
     *   catatan_pembayaran: opsional, untuk catatan terms pembayaran
     */
    public function up(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->enum('tipe_pembayaran', ['cash', 'kredit'])
                ->default('cash')
                ->after('total');

            // DP yang dibayar saat barang diterima — 0 untuk cash atau kredit tanpa DP
            $table->decimal('jumlah_dp', 10, 2)
                ->default(0.00)
                ->after('tipe_pembayaran');

            // sisa_hutang = total - jumlah_dp
            // Dihitung dan di-set oleh PembelianService::create(), BUKAN trigger DB
            // agar logika bisnis tetap terpusat di Service layer (CLAUDE.md §Aturan Service)
            $table->decimal('sisa_hutang', 10, 2)
                ->default(0.00)
                ->after('jumlah_dp');

            // NULL = cash (tidak relevan) | DATE = tanggal jatuh tempo kredit
            // WAJIB diisi jika tipe_pembayaran = 'kredit' — enforced di FormRequest
            $table->date('tanggal_jatuh_tempo')
                ->nullable()
                ->after('sisa_hutang');

            // Lifecycle: lunas (cash/pelunasan) → belum_lunas (kredit penuh) → sebagian (ada DP)
            // Diperbarui oleh PembayaranPembelianService::create() saat ada cicilan/pelunasan
            $table->enum('status_pembayaran', ['lunas', 'belum_lunas', 'sebagian'])
                ->default('lunas')
                ->after('tanggal_jatuh_tempo');

            $table->text('catatan_pembayaran')
                ->nullable()
                ->after('status_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->dropColumn([
                'tipe_pembayaran',
                'jumlah_dp',
                'sisa_hutang',
                'tanggal_jatuh_tempo',
                'status_pembayaran',
                'catatan_pembayaran',
            ]);
        });
    }
};
