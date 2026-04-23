'use strict';

/**
 * config/database.js — MySQL connection pool dengan retry logic.
 *
 * Mengapa perlu retry?
 * Docker Compose menjalankan container secara paralel. Meski `depends_on` + healthcheck
 * memastikan MySQL container sudah "healthy" sebelum kafka-service start, ada jeda
 * antara healthcheck PASSED dan MySQL siap menerima koneksi sesungguhnya (buffer ~2-5 detik).
 * Tanpa retry, Node.js akan crash dengan ECONNREFUSED di startup pertama.
 *
 * Strategi: exponential-backoff sederhana dengan max 5 percobaan.
 * Jika semua gagal, proses exit agar Docker bisa restart container secara bersih.
 */

const mysql = require('mysql2/promise');

const MAX_RETRIES    = 5;
const RETRY_DELAY_MS = 5000; // 5 detik antar percobaan

/**
 * Buat connection pool MySQL dengan retry.
 * Dipanggil sekali saat startup, hasilnya di-pass ke semua consumer dan producer.
 *
 * Pool (bukan single connection) dipilih karena:
 * - Consumer bisa query secara concurrent (tiap event butuh koneksi sendiri)
 * - Pool otomatis recycle koneksi yang idle, mengurangi overhead reconnect
 *
 * @returns {Promise<mysql.Pool>} Pool yang sudah terverifikasi bisa connect
 * @throws  {Error} Jika semua retry habis
 */
async function createPool() {
    for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
        try {
            const pool = mysql.createPool({
                // 'mysql' adalah hostname service Docker — JANGAN ganti dengan 'localhost'
                host:     process.env.DB_HOST     || 'mysql',
                port:     parseInt(process.env.DB_PORT || '3306'),
                database: process.env.DB_DATABASE || 'pos_grosir',
                user:     process.env.DB_USER     || 'pos_user',
                password: process.env.DB_PASSWORD || '',

                // connectionLimit: batas pool — sesuaikan dengan max_connections MySQL.
                // 10 cukup untuk beban consumer transaction + purchase + return bersamaan.
                waitForConnections: true,
                connectionLimit:    10,
                queueLimit:         0,   // 0 = antrian tak terbatas (tidak reject saat sibuk)

                // Timezone UTC agar TIMESTAMP di MySQL konsisten dengan Node.js
                timezone: '+00:00',
            });

            // Ambil satu koneksi sebagai probe — kalau ini berhasil, pool siap dipakai.
            // Release langsung agar koneksi kembali ke pool.
            const conn = await pool.getConnection();
            conn.release();

            console.log(`[DB] MySQL connection pool siap (percobaan ${attempt}/${MAX_RETRIES})`);
            return pool;

        } catch (err) {
            console.error(`[DB] Koneksi gagal (percobaan ${attempt}/${MAX_RETRIES}): ${err.message}`);

            if (attempt < MAX_RETRIES) {
                console.log(`[DB] Retry dalam ${RETRY_DELAY_MS / 1000} detik...`);
                await sleep(RETRY_DELAY_MS);
            } else {
                // Semua retry habis — lempar error agar index.js bisa handle (exit graceful)
                throw new Error(
                    `[DB] MySQL tidak bisa diakses setelah ${MAX_RETRIES} percobaan. ` +
                    `Pastikan DB_HOST="${process.env.DB_HOST}" benar dan MySQL sudah running.`
                );
            }
        }
    }
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

module.exports = { createPool };
