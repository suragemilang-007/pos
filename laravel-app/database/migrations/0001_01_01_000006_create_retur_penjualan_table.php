<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_penjualan', function (Blueprint $table) {
            $table->increments('id');
            // Hanya transaksi berstatus COMPLETED yang boleh diretur —
            // validasi dilakukan di ReturPenjualanService, bukan di DB constraint
            $table->unsignedInteger('transaksi_id');
            $table->unsignedInteger('user_id');
            // Nomor dokumen bisnis retur — format: RTJ-YYYYMMDD-XXXXX
            $table->string('no_retur', 30)->unique();
            $table->text('alasan');
            $table->decimal('total_refund', 10, 2)->default(0.00);
            // REJECTED berbeda dengan CANCELLED — retur ditolak admin
            $table->enum('status', ['PENDING', 'COMPLETED', 'REJECTED'])->default('PENDING');
            $table->timestamp('tanggal_retur')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('transaksi_id')->references('id')->on('transaksi')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retur_penjualan');
    }
};
