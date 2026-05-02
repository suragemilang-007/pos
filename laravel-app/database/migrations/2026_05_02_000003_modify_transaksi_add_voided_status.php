<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah nilai 'VOIDED' ke ENUM status tabel transaksi.
     *
     * Perubahan v3 (CLAUDE.md §5.5): status lifecycle transaksi diperluas:
     *   PENDING → COMPLETED | FAILED
     *   COMPLETED → VOIDED (via void, dalam batas VOID_TIME_LIMIT_MINUTES)
     *
     * Menggunakan DB::statement() raw SQL karena Laravel Schema Builder
     * tidak mendukung modifikasi ENUM secara langsung tanpa doctrine/dbal,
     * dan ALTER COLUMN dengan ENUM harus mencantumkan ulang semua nilai.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE transaksi
            MODIFY COLUMN status
                ENUM('PENDING','COMPLETED','FAILED','CANCELLED','VOIDED')
                NOT NULL DEFAULT 'PENDING'
        ");
    }

    public function down(): void
    {
        // Rollback: hapus nilai VOIDED
        // Baris dengan status='VOIDED' harus dipindah dulu — jika ada, rollback akan gagal
        // karena MySQL tidak bisa menghapus nilai ENUM yang masih dipakai
        DB::statement("
            ALTER TABLE transaksi
            MODIFY COLUMN status
                ENUM('PENDING','COMPLETED','FAILED','CANCELLED')
                NOT NULL DEFAULT 'PENDING'
        ");
    }
};
