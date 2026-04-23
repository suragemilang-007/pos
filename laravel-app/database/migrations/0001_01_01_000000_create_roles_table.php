<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            // TINYINT UNSIGNED — cukup untuk max 255 role, hemat storage
            $table->tinyIncrements('id');
            $table->string('nama_role', 50);
            // JSON array izin akses, nullable karena bisa dikonfigurasi belakangan
            $table->json('permissions')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};