<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\KategoriProdukController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProdukSatuanController;
use App\Http\Controllers\ReturPembelianController;
use App\Http\Controllers\ReturPenjualanController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PembayaranPembelianController;
use App\Http\Controllers\PenyesuaianStokController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\VoidTransaksiController;
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
// KATEGORI — dropdown ringan (kasir, gudang, owner)
// GET /api/kategori → [{id, nama_kategori}] untuk form produk & filter
// ─────────────────────────────────────────────────────────
Route::middleware(['auth.jwt', 'role:kasir,admin_gudang,owner'])
    ->get('/kategori', [KategoriProdukController::class, 'index']);

// ─────────────────────────────────────────────────────────
// KASIR & OWNER
// ─────────────────────────────────────────────────────────
Route::middleware(['auth.jwt', 'role:kasir,owner'])->prefix('kasir')->group(function () {
    // Dashboard kasir — ringkasan harian
    Route::get('/ringkasan', [TransaksiController::class, 'ringkasan']);

    // SD-03: Input transaksi penjualan → async via Kafka → 202
    Route::post('/transaksi', [TransaksiController::class, 'store']);

    // SD-04: Cetak struk — hanya transaksi COMPLETED
    Route::get('/transaksi/{id}/struk', [TransaksiController::class, 'struk']);

    // SD-17: Void transaksi — async via Kafka → 202
    // POST harus didefinisikan sebelum GET /{id} tidak masalah (method berbeda)
    Route::post('/transaksi/{id}/void', [VoidTransaksiController::class, 'store']);

    // Cari transaksi by no_transaksi atau id (untuk form retur) — harus sebelum /{id}
    Route::get('/transaksi/cari', [TransaksiController::class, 'cariTransaksi']);

    // SD-05: Detail transaksi untuk keperluan retur
    Route::get('/transaksi/{id}', [TransaksiController::class, 'show']);

    // SD-06: Initial load stok semua produk aktif — real-time update via WebSocket
    Route::get('/stok', [ProdukController::class, 'stokRealTime']);

    // Autocomplete suggest — HARUS sebelum {kode} agar tidak ditangkap wildcard
    Route::get('/produk/suggest', [ProdukController::class, 'suggestProduk']);

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
    // SD-16: Manajemen Kategori (dengan jumlah_produk)
    Route::get   ('/kategori',      [KategoriProdukController::class, 'indexWithCount']);
    Route::post  ('/kategori',      [KategoriProdukController::class, 'store']);
    Route::put   ('/kategori/{id}', [KategoriProdukController::class, 'update']);
    Route::delete('/kategori/{id}', [KategoriProdukController::class, 'destroy']);

    // SD-08: CRUD Produk — apiResource generates: index, store, show, update, destroy
    Route::apiResource('/produk', ProdukController::class);

    // SD-08: Sub-resource satuan per produk
    Route::get   ('/produk/{produk}/satuan',                   [ProdukSatuanController::class, 'index']);
    Route::post  ('/produk/{produk}/satuan',                   [ProdukSatuanController::class, 'store']);
    Route::put   ('/produk/{produk}/satuan/{satuan}',          [ProdukSatuanController::class, 'update']);
    Route::delete('/produk/{produk}/satuan/{satuan}',          [ProdukSatuanController::class, 'destroy']);

    // Dashboard gudang — ringkasan stok & pembelian
    Route::get('/ringkasan', [PembelianController::class, 'ringkasan']);

    // SD-13: CRUD Supplier
    Route::apiResource('/supplier', SupplierController::class);

    // SD-09: Pembelian / Restock → async via Kafka → 202
    Route::post('/pembelian',       [PembelianController::class, 'store']);
    Route::get ('/pembelian',       [PembelianController::class, 'index']);
    Route::get ('/pembelian/{id}',  [PembelianController::class, 'show']);

    // SD-19: Pembayaran hutang dagang — SINKRON (tidak via Kafka, tidak ada perubahan stok)
    Route::post('/pembelian/{id}/bayar', [PembayaranPembelianController::class, 'bayar']);
    Route::get ('/hutang',               [PembelianController::class, 'daftarHutang']);

    // SD-10: Retur pembelian → async via Kafka → 202
    Route::post('/retur-pembelian', [ReturPembelianController::class, 'store']);

    // SD-20: Penyesuaian Stok → async via Kafka → 202
    Route::get ('/penyesuaian-stok', [PenyesuaianStokController::class, 'index']);
    Route::post('/penyesuaian-stok', [PenyesuaianStokController::class, 'store']);

    // SD-18: Manajemen Promo Produk — toggle SEBELUM {id} agar tidak terambil sebagai id
    Route::patch('/promo/{id}/toggle', [PromoController::class, 'toggle']);
    Route::get   ('/promo',            [PromoController::class, 'index']);
    Route::post  ('/promo',            [PromoController::class, 'store']);
    Route::put   ('/promo/{id}',       [PromoController::class, 'update']);
    Route::delete('/promo/{id}',       [PromoController::class, 'destroy']);
});

// ─────────────────────────────────────────────────────────
// OWNER
// ─────────────────────────────────────────────────────────
Route::middleware(['auth.jwt', 'role:owner'])->prefix('owner')->group(function () {
    // Daftar transaksi seluruh kasir — hanya owner
    Route::get('/transaksi', [TransaksiController::class, 'index']);

    // SD-14: Laporan
    Route::get('/laporan/{jenis}/export', [LaporanController::class, 'export']);
    Route::get('/laporan/{jenis}',        [LaporanController::class, 'generate']);

    // SD-15: Manajemen User
    // Route statis harus SEBELUM apiResource / route dengan parameter agar tidak bentrok
    Route::get  ('/user/cek-username',          [UserController::class, 'cekUsername']);
    Route::patch('/user/{user}/nonaktifkan',    [UserController::class, 'nonaktifkan']);
    Route::patch('/user/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::apiResource('/user', UserController::class)->except(['destroy']);
});
