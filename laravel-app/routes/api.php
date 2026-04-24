<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Sistem POS Toko Melati
|--------------------------------------------------------------------------
| Semua route diawali /api secara otomatis (dikonfigurasi di bootstrap/app.php).
| Endpoint dan format response mengikuti kontrak di docs/SEQUENCE_FLOWS.md.
|
| Middleware alias yang tersedia:
|   auth.jwt → App\Http\Middleware\AuthenticateJWT
|   role     → App\Http\Middleware\CheckRole
*/

// ─────────────────────────────────────────────────────────
// PUBLIK — tidak perlu token
// ─────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    // SD-01: Login
    Route::post('/login', [AuthController::class, 'login']);
});

// ─────────────────────────────────────────────────────────
// PROTECTED — semua role, wajib token valid
// ─────────────────────────────────────────────────────────
Route::middleware('auth.jwt')->group(function () {
    // SD-02: Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// ─────────────────────────────────────────────────────────
// KASIR & OWNER
// ─────────────────────────────────────────────────────────
Route::middleware(['auth.jwt', 'role:kasir,owner'])->prefix('kasir')->group(function () {
    // SD-03: Input transaksi penjualan → async via Kafka → 202
    Route::post('/transaksi', [TransaksiController::class, 'store']);

    // SD-04: Cetak struk — hanya transaksi COMPLETED
    Route::get('/transaksi/{id}/struk', [TransaksiController::class, 'struk']);

    // SD-05: Detail transaksi untuk keperluan retur
    Route::get('/transaksi/{id}', [TransaksiController::class, 'show']);

    // SD-07: Cek stok produk by kode barcode — dipakai saat kasir scan
    Route::get('/produk/{kode}/stok', [ProdukController::class, 'cekStok']);

    // Placeholder — akan diisi saat fitur berikutnya diimplementasikan:
    // Route::post('/retur-penjualan', [ReturPenjualanController::class, 'store']);
    // Route::get('/produk/{kode}/stok', [ProdukController::class, 'cekStok']);
    // Route::get('/stok', [ProdukController::class, 'stokRealTime']);
    // Route::apiResource('/customer', CustomerController::class);
    // Route::get('/customer/{id}/riwayat', [CustomerController::class, 'riwayat']);
});

// ─────────────────────────────────────────────────────────
// ADMIN GUDANG & OWNER
// ─────────────────────────────────────────────────────────
Route::middleware(['auth.jwt', 'role:admin_gudang,owner'])->prefix('gudang')->group(function () {
    // Placeholder:
    // Route::apiResource('/produk', ProdukController::class);
    // Route::post('/pembelian', [PembelianController::class, 'store']);
    // Route::post('/retur-pembelian', [ReturPembelianController::class, 'store']);
    // Route::apiResource('/supplier', SupplierController::class);
});

// ─────────────────────────────────────────────────────────
// OWNER
// ─────────────────────────────────────────────────────────
Route::middleware(['auth.jwt', 'role:owner'])->prefix('owner')->group(function () {
    // Placeholder:
    // Route::get('/laporan/{jenis}', [LaporanController::class, 'generate']);
    // Route::apiResource('/user', UserController::class);
});
