<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));
Route::get('/login', fn () => view('auth.login'))->name('login');

// Dashboard per role — auth ditangani client-side via JWT + Axios interceptor
Route::get('/kasir',   fn () => view('kasir.dashboard'))->name('kasir.dashboard');
Route::get('/produk',  fn () => view('gudang.dashboard'))->name('gudang.dashboard');
Route::get('/laporan', fn () => view('owner.dashboard'))->name('owner.dashboard');

Route::get('/kasir/transaksi', fn () => view('kasir.transaksi'))->name('kasir.transaksi');
Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
