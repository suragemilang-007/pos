<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->increments('id');
            // INT UNSIGNED agar cocok dengan tipe users.id
            $table->unsignedInteger('user_id');
            // VARCHAR(500) — cukup untuk JWT token yang bisa panjang
            $table->string('token', 500);
            $table->enum('status', ['active', 'revoked'])->default('active');
            // Wajib diisi saat generate token, menentukan kapan token gugur
            $table->timestamp('expired_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};