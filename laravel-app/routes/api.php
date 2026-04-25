<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProdukSatuanController;
use App\Http\Controllers\ReturPembelianController;
use App\Http\Controllers\ReturPenjualanController;
use App\Http\Controllers\SupplierController;
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

    // SD-06: Initial load stok semua produk aktif — real-time update via WebSocket
    Route::get('/stok', [ProdukController::class, 'stokRealTime']);

    // SD-07: Cek stok produk by kode barcode — dipakai saat kasir scan
    Route::get('/produk/{kode}/stok', [ProdukController::class, 'cekStok']);

    // SD-11, SD-12: CRUD Customer + riwayat belanja
    // riwayat harus didefinisikan SEBELUM apiResource agar tidak diambil alih {customer} param
    Route::get('/customer/{customer}/riwayat', [CustomerController::class, 'riwayatBelanja']);
    Route::apiResource('/customer', CustomerController::class);

    // SD-05: Retur penjualan → async via Kafka → 202
    Route::post('/retur-penjualan', [ReturPenjualanController::class, 'store']);
});

// ─────────────────────────────────────────────────────────
// ADMIN GUDANG & OWNER
// ─────────────────────────────────────────────────────────
Route::middleware(['auth.jwt', 'role:admin_gudang,owner'])->prefix('gudang')->group(function () {
    // SD-08: CRUD Produk — apiResource generates: index, store, show, update, destroy
    Route::apiResource('/produk', ProdukController::class);

    // SD-08: Sub-resource satuan per produk
    Route::get   ('/produk/{produk}/satuan',                   [ProdukSatuanController::class, 'index']);
    Route::post  ('/produk/{produk}/satuan',                   [ProdukSatuanController::class, 'store']);
    Route::put   ('/produk/{produk}/satuan/{satuan}',          [ProdukSatuanController::class, 'update']);
    Route::delete('/produk/{produk}/satuan/{satuan}',          [ProdukSatuanController::class, 'destroy']);

    // SD-13: CRUD Supplier
    Route::apiResource('/supplier', SupplierController::class);

    // SD-09: Pembelian / Restock → async via Kafka → 202
    Route::post('/pembelian',       [PembelianController::class, 'store']);
    Route::get ('/pembelian',       [PembelianController::class, 'index']);
    Route::get ('/pembelian/{id}',  [PembelianController::class, 'show']);

    // SD-10: Retur pembelian → async via Kafka → 202
    Route::post('/retur-pembelian', [ReturPembelianController::class, 'store']);
});

// ─────────────────────────────────────────────────────────
// OWNER
// ─────────────────────────────────────────────────────────
Route::middleware(['auth.jwt', 'role:owner'])->prefix('owner')->group(function () {
    // SD-14: Laporan — export harus didefinisikan SEBELUM generate agar
    // '{jenis}/export' tidak diinterpretasikan sebagai {jenis} = 'penjualan' dan param lain
    Route::get('/laporan/{jenis}/export', [LaporanController::class, 'export']);
    Route::get('/laporan/{jenis}',        [LaporanController::class, 'generate']);
});
