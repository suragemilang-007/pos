<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->increments('id');
            // Kasir yang memproses — wajib ada, tidak bisa null
            $table->unsignedInteger('user_id');
            // Customer opsional — transaksi walk-in tanpa customer tetap valid
            $table->unsignedInteger('customer_id')->nullable();
            // Nomor dokumen bisnis — terpisah dari PK, format: TRX-YYYYMMDD-XXXXX
            $table->string('no_transaksi', 30)->unique();
            $table->decimal('subtotal', 10, 2)->default(0.00);
            // total dihitung di Service sebelum dikirim ke Kafka (tidak ada diskon, total = subtotal)
            $table->decimal('total', 10, 2)->default(0.00);
            $table->decimal('bayar', 10, 2)->default(0.00);
            $table->decimal('kembalian', 10, 2)->default(0.00);
            $table->enum('metode_bayar', ['tunai', 'transfer', 'qris'])->default('tunai');
            // CANCELLED ada di transaksi — berbeda dengan retur (status REJECTED)
            $table->enum('status', ['PENDING', 'COMPLETED', 'FAILED', 'CANCELLED'])->default('PENDING');
            // tanggal_transaksi = kapan transaksi terjadi (bisa berbeda dengan created_at)
            $table->timestamp('tanggal_transaksi')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            // Restrik delete — user dan customer tidak boleh dihapus jika ada transaksi
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
