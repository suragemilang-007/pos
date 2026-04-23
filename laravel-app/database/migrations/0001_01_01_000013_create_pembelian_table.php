<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelian', function (Blueprint $table) {
            $table->increments('id');
            // Admin Gudang yang membuat Purchase Order
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('supplier_id');
            // Nomor dokumen PO — format: PO-YYYYMMDD-XXXXX
            $table->string('no_pembelian', 30)->unique();
            $table->decimal('total', 10, 2)->default(0.00);
            // Status CANCELLED bukan FAILED — pembelian dibatalkan, bukan gagal sistem
            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED'])->default('PENDING');
            // DATE bukan TIMESTAMP — hanya perlu tanggal PO, bukan jam pengiriman
            $table->date('tanggal_pembelian');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelian');
    }
};
