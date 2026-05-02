<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('void_transaksi', function (Blueprint $table) {
            $table->increments('id');

            // FK ke transaksi yang di-void
            // UNIQUE — satu transaksi hanya bisa di-void satu kali (CLAUDE.md §5 constraint)
            $table->unsignedInteger('transaksi_id')->unique();

            // Transaksi koreksi pengganti (opsional — dibuat kasir setelah void jika perlu)
            $table->unsignedInteger('transaksi_baru_id')->nullable();

            // Kasir yang mengajukan void
            $table->unsignedInteger('user_id');

            // Alasan void — dibatasi via ENUM untuk kebutuhan laporan (tidak boleh free-text sembarangan)
            $table->enum('alasan', [
                'kesalahan_input',
                'barang_rusak_saat_transaksi',
                'permintaan_pembeli',
                'lainnya',
            ]);

            // Keterangan bebas — wajib diisi jika alasan='lainnya' (validasi di Service)
            $table->text('keterangan')->nullable();

            $table->enum('status', ['PENDING', 'COMPLETED', 'REJECTED'])->default('PENDING');

            // Batas waktu void — dihitung saat void diajukan:
            // batas_waktu = created_at + VOID_TIME_LIMIT_MINUTES (env variable)
            // Consumer menolak void yang sudah melewati batas ini
            $table->timestamp('batas_waktu');

            // Tidak ada updated_at — status void tidak diubah setelah COMPLETED/REJECTED
            $table->timestamp('created_at')->useCurrent();

            // Restrik delete — void tidak boleh hilang jika transaksinya masih ada
            $table->foreign('transaksi_id')
                ->references('id')->on('transaksi')
                ->restrictOnDelete();

            // transaksi_baru_id nullable → SET NULL jika transaksi pengganti dihapus
            $table->foreign('transaksi_baru_id')
                ->references('id')->on('transaksi')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('void_transaksi');
    }
};
