<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel penyesuaian stok manual (ERD v4.0).
     *
     * Digunakan untuk mencatat perubahan stok yang BUKAN dari transaksi/pembelian/retur:
     *   - barang_rusak   : barang cacat/tidak bisa dijual
     *   - barang_hilang  : kehilangan stok (pencurian, misplace)
     *   - barang_expired : barang kadaluarsa, harus dibuang
     *   - koreksi_plus   : stok fisik lebih banyak dari sistem (hasil opname)
     *   - koreksi_minus  : stok fisik lebih sedikit dari sistem (hasil opname)
     *
     * Aturan arah stok (sesuai ERD §Aturan Kritis Consumer Kafka):
     *   koreksi_plus                          → qty_stok BERTAMBAH
     *   barang_rusak/hilang/expired/koreksi_minus → qty_stok BERKURANG
     *
     * qty_satuan_dasar selalu POSITIF — arah ditentukan oleh kolom tipe, bukan tanda.
     * Consumer (penyesuaianConsumer.js) yang menafsirkan arah berdasarkan tipe.
     *
     * Tidak ada updated_at — penyesuaian yang salah dicatat ulang (tidak diedit).
     * Status PENDING → COMPLETED oleh Kafka Consumer setelah stok diupdate.
     */
    public function up(): void
    {
        Schema::create('penyesuaian_stok', function (Blueprint $table) {
            $table->increments('id');

            // Produk yang stoknya disesuaikan
            $table->unsignedInteger('produk_id');

            // Admin Gudang yang membuat penyesuaian (audit trail)
            $table->unsignedInteger('user_id');

            $table->enum('tipe', [
                'barang_rusak',
                'barang_hilang',
                'barang_expired',
                'koreksi_plus',
                'koreksi_minus',
            ]);

            // Selalu positif — arah stok (naik/turun) ditentukan oleh kolom tipe di atas.
            // Menggunakan satuan_dasar langsung (tidak perlu konversi isi_satuan)
            // karena penyesuaian dilakukan oleh admin yang tahu kondisi fisik gudang.
            $table->integer('qty_satuan_dasar')->unsigned();

            // Keterangan wajib — admin HARUS menjelaskan alasan penyesuaian
            // untuk keperluan audit dan laporan manajemen (CLAUDE.md §7.5)
            $table->text('keterangan');

            // PENDING: event sudah dikirim ke Kafka, Consumer belum selesai
            // COMPLETED: Consumer berhasil UPDATE qty_stok di tabel produk
            $table->enum('status', ['PENDING', 'COMPLETED'])->default('PENDING');

            // Tidak ada updated_at — record ini immutable setelah Consumer update status
            $table->timestamp('created_at')->useCurrent();

            // ── Foreign Keys ──────────────────────────────────────────────────
            // restrictOnDelete: produk tidak boleh dihapus jika ada riwayat penyesuaian
            $table->foreign('produk_id')
                ->references('id')->on('produk')
                ->restrictOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyesuaian_stok');
    }
};
