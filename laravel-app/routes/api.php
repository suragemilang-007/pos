<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Sistem POS Toko Melati
|--------------------------------------------------------------------------
| Semua route diawali /api secara otomatis (dikonfigurasi di bootstrap/app.php).
| Endpoint dan format response mengikuti kontrak di docs/SEQUENCE_FLOWS.md.
|
| Middleware alias yang tersedia:
|   auth.jwt  → App\Http\Middleware\AuthenticateJWT
|   role      → App\Http\Middleware\CheckRole  (belum diimplementasikan)
*/

// ─────────────────────────────────────────────────────────
// PUBLIK — tidak perlu token
// ─────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    // SD-01: Login
    Route::post('/login', [AuthController::class, 'login']);
});

// ─────────────────────────────────────────────────────────
// PROTECTED — wajib token valid (semua role)
// ─────────────────────────────────────────────────────────
Route::middleware('auth.jwt')->group(function () {
    // SD-02: Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // ── Kasir & Owner ────────────────────────────────────
    // Route::middleware('role:kasir,owner')->prefix('kasir')->group(function () {
    //     Route::post('/transaksi', [\App\Http\Controllers\TransaksiController::class, 'store']);
    //     ...
    // });

    // ── Admin Gudang & Owner ─────────────────────────────
    // Route::middleware('role:admin_gudang,owner')->prefix('gudang')->group(function () {
    //     Route::apiResource('/produk', \App\Http\Controllers\ProdukController::class);
    //     ...
    // });

    // ── Owner saja ───────────────────────────────────────
    // Route::middleware('role:owner')->prefix('owner')->group(function () {
    //     Route::get('/laporan/{jenis}', [\App\Http\Controllers\LaporanController::class, 'generate']);
    //     ...
    // });
});
