<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            // TINYINT UNSIGNED agar cocok dengan tipe roles.id
            $table->unsignedTinyInteger('role_id');
            $table->string('nama', 100);
            $table->string('username', 50)->unique();
            // Kolom HARUS password_hash, BUKAN password — lihat ERD & aturan 2.6
            $table->string('password_hash', 255);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            // Nullable — hanya diisi saat login berhasil, bukan saat register
            $table->timestamp('last_login')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};