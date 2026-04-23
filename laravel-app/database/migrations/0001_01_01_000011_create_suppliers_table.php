<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama_supplier', 150);
            $table->string('kontak_person', 100)->nullable();
            // Identifier bisnis supplier — harus unik
            $table->string('no_telp', 20)->unique();
            $table->text('alamat')->nullable();
            // Soft delete — supplier tidak boleh dihapus jika ada riwayat pembelian
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
