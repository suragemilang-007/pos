<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 100);
            // no_telp sebagai identifier bisnis — harus unik
            $table->string('no_telp', 20)->unique();
            $table->text('alamat')->nullable();
            $table->enum('tipe_customer', ['reguler', 'grosir'])->default('reguler');
            // Soft delete via status, bukan deleted_at — sesuai aturan 2.5
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('created_at')->useCurrent();
            // ON UPDATE CURRENT_TIMESTAMP — update otomatis saat row diubah
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
