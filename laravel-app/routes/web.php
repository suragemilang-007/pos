<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');
Route::get('/kasir', function () {
    return view('kasir.dashboard');
})->name('kasir');
Route::get('/produk', function () {
    return view('gudang.dashboard');
})->name('gudang');
Route::get('/owner', function () {
    return view('owner.dashboard');
})->name('owner');

Route::get('/dashboard', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    $user = auth()->user();
    $role = $user->role->nama_role;

    if ($role === 'owner') {
        return redirect()->route('owner.dashboard');
    }
    if ($role === 'admin_gudang') {
        return redirect()->route('gudang.dashboard');
    }
    if ($role === 'kasir') {
        return redirect()->route('kasir.dashboard');
    }

    // Fallback for any other case
    return view('dashboard');
})->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/kasir/dashboard', function () {
        return view('kasir.dashboard');
    })->name('kasir.dashboard')->middleware('role:kasir,owner');

    Route::get('/gudang/dashboard', function () {
        return view('gudang.dashboard');
    })->name('gudang.dashboard')->middleware('role:admin_gudang,owner');

    Route::get('/owner/dashboard', function () {
        return view('owner.dashboard');
    })->name('owner.dashboard')->middleware('role:owner');
});