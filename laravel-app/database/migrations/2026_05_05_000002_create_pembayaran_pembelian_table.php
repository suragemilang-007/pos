<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pencatatan pembayaran hutang dagang (ERD v4.0).
     *
     * Satu PO (pembelian) bisa punya BANYAK record pembayaran — mendukung cicilan.
     * Total lunas = SUM(pembayaran_pembelian.jumlah) WHERE pembelian_id = ?
     *
     * Tidak ada updated_at — record ini immutable setelah INSERT.
     * Jika ada kesalahan input, tambahkan record koreksi (kredit negatif) daripada UPDATE.
     *
     * PENTING (CLAUDE.md §7.3 Pembayaran Hutang = SINKRON):
     * Endpoint POST /pembelian/{id}/bayar TIDAK publish ke Kafka.
     * PembayaranPembelianService langsung UPDATE sisa_hutang dan status_pembayaran.
     */
    public function up(): void
    {
        Schema::create('pembayaran_pembelian', function (Blueprint $table) {
            $table->increments('id');

            // FK ke PO yang sedang dibayar
            $table->unsignedInteger('pembelian_id');

            // Admin yang mencatat pembayaran (audit trail)
            $table->unsignedInteger('user_id');

            // Jumlah yang dibayarkan pada transaksi ini (satu cicilan)
            $table->decimal('jumlah', 10, 2);

            // Metode pembayaran ke supplier
            $table->enum('metode', ['cash', 'transfer', 'cek', 'giro'])->default('cash');

            // Tanggal aktual pembayaran dilakukan (bisa berbeda dari tanggal input)
            $table->date('tanggal_bayar');

            $table->text('catatan')->nullable();

            // Tidak ada updated_at — record pembayaran tidak boleh diubah setelah dicatat
            $table->timestamp('created_at')->useCurrent();

            // ── Foreign Keys ──────────────────────────────────────────────────
            // restrictOnDelete: PO tidak boleh dihapus jika sudah ada pembayaran
            $table->foreign('pembelian_id')
                ->references('id')->on('pembelian')
                ->restrictOnDelete();

            // restrictOnDelete: user yang mencatat tidak boleh dihapus selama ada record
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_pembelian');
    }
};
