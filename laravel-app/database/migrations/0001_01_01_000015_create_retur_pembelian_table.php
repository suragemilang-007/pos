<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_pembelian', function (Blueprint $table) {
            $table->increments('id');
            // Hanya pembelian berstatus COMPLETED yang boleh diretur —
            // validasi dilakukan di ReturPembelianService, bukan di DB constraint
            $table->unsignedInteger('pembelian_id');
            // Admin Gudang yang mengajukan retur ke supplier
            $table->unsignedInteger('user_id');
            // Nomor dokumen retur pembelian — format: RTP-YYYYMMDD-XXXXX
            $table->string('no_retur', 30)->unique();
            $table->text('alasan');
            // Kredit dari supplier ke toko — bukan refund ke customer
            $table->decimal('total_kredit', 10, 2)->default(0.00);
            $table->enum('status', ['PENDING', 'COMPLETED', 'REJECTED'])->default('PENDING');
            $table->timestamp('tanggal_retur')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            // Tidak ada updated_at — status diubah sekali (PENDING → COMPLETED/REJECTED)
            $table->foreign('pembelian_id')->references('id')->on('pembelian')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retur_pembelian');
    }
};
