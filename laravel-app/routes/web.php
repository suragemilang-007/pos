<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/login'));
Route::get('/login', fn() => view('auth.login'))->name('login');

// ─── Dashboard per role ────────────────────────────────────────────────────────
// Auth ditangani client-side via JWT + Axios interceptor (bukan Laravel session).
Route::get('/kasir', fn() => view('kasir.dashboard'))->name('kasir.dashboard');
Route::get('/produk', fn() => view('gudang.dashboard'))->name('gudang.dashboard');
Route::get('/laporan', fn() => view('owner.laporan.index'))->name('owner.laporan');
Route::get('/owner/dashboard', fn() => view('owner.dashboard'))->name('owner.dashboard');

// ─── Kasir ────────────────────────────────────────────────────────────────────
Route::get('/kasir/transaksi', fn() => view('kasir.transaksi'))->name('kasir.transaksi');
Route::get('/kasir/retur-penjualan', fn() => view('kasir.retur-penjualan'))->name('kasir.retur');
Route::get('/customer', fn() => view('kasir.customer.index'))->name('kasir.customer.index');
Route::get('/customer/{id}/riwayat', fn($id) => view('kasir.customer.riwayat', ['customerId' => (int) $id]))->name('kasir.customer.riwayat');
Route::get('/stok', fn() => view('kasir.stok'))->name('kasir.stok');

// ─── Gudang ───────────────────────────────────────────────────────────────────
Route::get('/gudang/kategori', fn() => view('gudang.kategori.index'))->name('gudang.kategori');
Route::get('/gudang/produk', fn() => view('gudang.produk.index'))->name('gudang.produk.index');
Route::get('/gudang/produk/create', fn() => view('gudang.produk.form'))->name('gudang.produk.create');
Route::get('/gudang/produk/{id}/edit', fn($id) => view('gudang.produk.form', ['produkId' => (int) $id]))->name('gudang.produk.edit');
Route::get('/gudang/pembelian', fn() => view('gudang.pembelian.index'))->name('gudang.pembelian.index');
Route::get('/gudang/pembelian/create', fn() => view('gudang.pembelian.create'))->name('gudang.pembelian.create');
Route::get('/gudang/pembelian/{id}', fn($id) => view('gudang.pembelian.show', ['pembelianId' => (int) $id]))->name('gudang.pembelian.show');
Route::get('/gudang/supplier', fn() => view('gudang.supplier'))->name('gudang.supplier');

// ─── Owner ────────────────────────────────────────────────────────────────────
Route::get('/user', fn() => view('owner.user.index'))->name('owner.user');
Route::get('/dashboard', fn() => view('owner.dashboard'))->name('owner.dashboard');