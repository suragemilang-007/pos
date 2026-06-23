@extends('layouts.app')

@section('title', 'Laporan')
@section('page-title', 'Laporan')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="space-y-5">

    {{-- ── Tab Navigation ──────────────────────────────────────────────────── --}}
    <div class="bg-gray-100 dark:bg-gray-700/50 p-1 rounded-xl flex gap-1 flex-wrap sm:flex-nowrap">
        @foreach([['penjualan','Penjualan'],['stok','Stok'],['pembelian','Pembelian'],['hutang','Hutang Dagang'],['penyesuaian-stok','Penyesuaian Stok']] as [$key,$label])
        <button id="tab-btn-{{ $key }}" onclick="switchTab('{{ $key }}')"
            class="tab-btn flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-all focus:outline-none whitespace-nowrap">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         TAB PENJUALAN
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div id="panel-penjualan" class="space-y-5">

        {{-- Filter --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3 items-end">
                <div class="space-y-1 flex-1">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Dari Tanggal</label>
                    <input id="jual-dari" type="date"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                <div class="space-y-1 flex-1">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Sampai Tanggal</label>
                    <input id="jual-sampai" type="date"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                <button id="btn-gen-jual" onclick="generatePenjualan()"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 shrink-0 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg id="spin-jual" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg class="w-4 h-4" id="icon-gen-jual" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Generate
                </button>
            </div>
        </div>

        {{-- Hasil laporan penjualan (disembunyikan sebelum generate) --}}
        <div id="hasil-jual" class="hidden space-y-5">

            {{-- 4 Kartu Metrik --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Omzet</p>
                    <p id="m-omzet" class="mt-1.5 text-2xl font-bold font-mono text-indigo-600 dark:text-indigo-400">—</p>
                    <p id="m-omzet-sub" class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">total pendapatan</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Jumlah Transaksi</p>
                    <p id="m-trx" class="mt-1.5 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">transaksi selesai</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Profit Kotor</p>
                    <p id="m-profit" class="mt-1.5 text-2xl font-bold font-mono text-green-600 dark:text-green-400">—</p>
                    <p id="m-margin" class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">margin —</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Rata-rata/Transaksi</p>
                    <p id="m-avg" class="mt-1.5 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">per transaksi selesai</p>
                </div>
            </div>

            {{-- Grafik + Pie Chart --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- Line Chart Omzet Harian --}}
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Omzet per Hari</h3>
                    <p id="label-periode-jual" class="text-xs text-gray-400 dark:text-gray-500 mb-4"></p>
                    <div class="h-56 relative">
                        <canvas id="chartOmzet"></canvas>
                    </div>
                </div>

                {{-- Pie Chart Metode Bayar --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Metode Pembayaran</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Distribusi berdasarkan omzet</p>
                    <div class="h-44 relative flex items-center justify-center">
                        <canvas id="chartMetode"></canvas>
                    </div>
                    <div id="legend-metode" class="mt-3 space-y-1"></div>
                </div>
            </div>

            {{-- Top 10 Produk Terlaris --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Top 10 Produk Terlaris</h3>
                    <div class="flex gap-2">
                        <button onclick="exportLaporan('penjualan','excel')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Excel/CSV
                        </button>
                        <button onclick="exportLaporan('penjualan','pdf')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Print PDF
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">#</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama Produk</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Satuan</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Qty Terjual</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Total Omzet</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-terlaris" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                    </table>
                    <p id="empty-terlaris" class="hidden px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Belum ada data penjualan pada periode ini.</p>
                </div>
            </div>
        </div>

        {{-- Empty state sebelum generate --}}
        <div id="empty-jual" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-6 py-16 text-center">
            <svg class="mx-auto w-14 h-14 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pilih rentang tanggal dan klik Generate untuk melihat laporan.</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         TAB STOK
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div id="panel-stok" class="hidden space-y-5">

        {{-- Filter --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3 items-end">
                <div class="space-y-1 flex-1">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Kategori</label>
                    <select id="stok-kategori"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                        <option value="">Semua Kategori</option>
                    </select>
                </div>
                <button id="btn-gen-stok" onclick="generateStok()"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 shrink-0 disabled:opacity-50">
                    <svg id="spin-stok" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Generate
                </button>
            </div>
        </div>

        {{-- Hasil stok --}}
        <div id="hasil-stok" class="hidden space-y-4">

            {{-- Summary badges --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Produk</p>
                    <p id="stok-total-produk" class="mt-1 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Nilai Stok</p>
                    <p id="stok-total-nilai" class="mt-1 text-xl font-bold font-mono text-indigo-600 dark:text-indigo-400">—</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 shadow-sm p-4 text-center">
                    <p class="text-xs text-red-600 dark:text-red-400">Stok Rendah/Habis</p>
                    <p id="stok-rendah-cnt" class="mt-1 text-2xl font-bold font-mono text-red-700 dark:text-red-400">—</p>
                </div>
            </div>

            {{-- Tabel stok --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Detail Stok Produk</h3>
                    <button onclick="exportLaporan('stok','excel')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export CSV
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama Produk</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Sat. Dasar</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Stok</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Min</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Nilai Stok</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Terjual 30hr</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-stok" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                        <tfoot id="tfoot-stok" class="bg-gray-50 dark:bg-gray-700/50 border-t-2 border-gray-300 dark:border-gray-600"></tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div id="empty-stok" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-6 py-16 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Klik Generate untuk melihat laporan stok.</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         TAB PEMBELIAN
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div id="panel-pembelian" class="hidden space-y-5">

        {{-- Filter --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3 items-end">
                <div class="space-y-1 flex-1">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Dari Tanggal</label>
                    <input id="beli-dari" type="date"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                <div class="space-y-1 flex-1">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Sampai Tanggal</label>
                    <input id="beli-sampai" type="date"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                <button id="btn-gen-beli" onclick="generatePembelian()"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 shrink-0 disabled:opacity-50">
                    <svg id="spin-beli" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Generate
                </button>
            </div>
        </div>

        {{-- Hasil pembelian --}}
        <div id="hasil-beli" class="hidden space-y-5">

            {{-- 3 Kartu Metrik --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Nilai PO</p>
                    <p id="beli-total" class="mt-1.5 text-2xl font-bold font-mono text-indigo-600 dark:text-indigo-400">—</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Jumlah PO</p>
                    <p id="beli-jumlah" class="mt-1.5 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
                    <p id="beli-jumlah-sub" class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Supplier Terbanyak</p>
                    <p id="beli-top-supplier" class="mt-1.5 text-lg font-bold text-gray-900 dark:text-gray-100 truncate">—</p>
                    <p id="beli-top-supplier-sub" class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"></p>
                </div>
            </div>

            {{-- Tabel PO --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Daftar Purchase Order</h3>
                    <button onclick="exportLaporan('pembelian','excel')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export CSV
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No PO</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Total</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-beli" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                    </table>
                    <p id="empty-beli-tbl" class="hidden px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Tidak ada PO pada periode ini.</p>
                </div>
            </div>
        </div>

        <div id="empty-beli" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-6 py-16 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Klik Generate untuk melihat laporan pembelian.</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         TAB HUTANG DAGANG
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div id="panel-hutang" class="hidden space-y-5">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 flex justify-between items-center">
            <h3 class="font-medium text-gray-900 dark:text-gray-100">Laporan Hutang Dagang</h3>
            <button id="btn-gen-hutang" onclick="generateHutang()"
                class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 shrink-0 disabled:opacity-50">
                <svg id="spin-hutang" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Generate
            </button>
        </div>

        <div id="hasil-hutang" class="hidden space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Hutang</p>
                    <p id="hutang-total" class="mt-1.5 text-2xl font-bold font-mono text-red-600 dark:text-red-400">—</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">PO Menunggak</p>
                    <p id="hutang-menunggak" class="mt-1.5 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Rekap Hutang per Supplier</h3>
                    <button onclick="exportLaporan('hutang','excel')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export CSV
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nama</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase text-center">Jumlah PO</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase text-right">Total Hutang</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase text-right">Sudah Bayar</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase text-right">Sisa</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-hutang" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                        <tfoot id="tfoot-hutang" class="bg-gray-50 dark:bg-gray-700/50 border-t-2 border-gray-300 dark:border-gray-600"></tfoot>
                    </table>
                    <p id="empty-hutang-tbl" class="hidden px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Tidak ada hutang tersisa.</p>
                </div>
            </div>
        </div>
        <div id="empty-hutang" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-6 py-16 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Klik Generate untuk melihat laporan hutang.</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         TAB PENYESUAIAN STOK
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div id="panel-penyesuaian-stok" class="hidden space-y-5">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3 items-end">
                <div class="space-y-1 flex-1">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Dari Tanggal</label>
                    <input id="sesuai-dari" type="date" class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                <div class="space-y-1 flex-1">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Sampai Tanggal</label>
                    <input id="sesuai-sampai" type="date" class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                <button id="btn-gen-sesuai" onclick="generatePenyesuaianStok()"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 shrink-0 disabled:opacity-50">
                    <svg id="spin-sesuai" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Generate
                </button>
            </div>
        </div>

        <div id="hasil-sesuai" class="hidden space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rusak</p>
                    <p id="sesuai-rusak" class="mt-1 text-2xl font-bold font-mono text-amber-600 dark:text-amber-400">—</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Hilang</p>
                    <p id="sesuai-hilang" class="mt-1 text-2xl font-bold font-mono text-red-600 dark:text-red-400">—</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Expired</p>
                    <p id="sesuai-expired" class="mt-1 text-2xl font-bold font-mono text-orange-600 dark:text-orange-400">—</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Riwayat Penyesuaian Stok</h3>
                    <button onclick="exportLaporan('penyesuaian-stok','excel')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export CSV
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Produk</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tipe</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase text-right">Qty</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Keterangan</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Admin</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-sesuai" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                    </table>
                    <p id="empty-sesuai-tbl" class="hidden px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Tidak ada penyesuaian stok pada periode ini.</p>
                </div>
            </div>
        </div>
        <div id="empty-sesuai" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-6 py-16 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Klik Generate untuk melihat laporan penyesuaian stok.</p>
        </div>
    </div>



</div>
@endsection

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let activeTab    = 'penjualan';
let chartOmzet   = null;
let chartMetode  = null;
let lastJualDari = ''; let lastJualSampai = '';
let lastBeliDari = ''; let lastBeliSampai = '';
let lastSesuaiDari = ''; let lastSesuaiSampai = '';

// ── Tab ───────────────────────────────────────────────────────────────────────
function switchTab(tab) {
    activeTab = tab;
    ['penjualan','stok','pembelian','hutang','penyesuaian-stok'].forEach(t => {
        const btn   = document.getElementById('tab-btn-' + t);
        const panel = document.getElementById('panel-' + t);
        const isActive = t === tab;

        panel.classList.toggle('hidden', !isActive);
        btn.className = isActive
            ? 'tab-btn flex-1 px-3 py-2 text-sm font-semibold rounded-lg bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm transition-all focus:outline-none whitespace-nowrap'
            : 'tab-btn flex-1 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-all focus:outline-none whitespace-nowrap';
    });
    sessionStorage.setItem('laporan-tab', tab);
}

// ── Default tanggal ───────────────────────────────────────────────────────────
function defaultDates() {
    const today    = new Date().toLocaleDateString('sv');
    const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toLocaleDateString('sv');

    ['jual-dari','beli-dari','sesuai-dari'].forEach(id => {
        document.getElementById(id).value = firstDay;
    });
    ['jual-sampai','beli-sampai','sesuai-sampai'].forEach(id => {
        document.getElementById(id).value = today;
    });
}

// ── Load dropdown kategori (untuk tab stok) ───────────────────────────────────
async function loadKategoriDropdown() {
    try {
        const { data } = await axios.get('/api/kategori');
        const sel = document.getElementById('stok-kategori');
        (data.data || []).forEach(k => {
            const o = document.createElement('option');
            o.value = k.id; o.textContent = k.nama_kategori;
            sel.appendChild(o);
        });
    } catch (_) {}
}

// ── TAB PENJUALAN ─────────────────────────────────────────────────────────────
async function generatePenjualan() {
    const dari   = document.getElementById('jual-dari').value;
    const sampai = document.getElementById('jual-sampai').value;
    if (!dari || !sampai) { showToast('warning', 'Filter diperlukan', 'Pilih rentang tanggal terlebih dahulu'); return; }

    lastJualDari = dari; lastJualSampai = sampai;
    setLoading('jual', true);

    try {
        const { data } = await axios.get(`/api/owner/laporan/penjualan?dari=${dari}&sampai=${sampai}`);
        const d = data.data;
        const m = data.metrik ?? {};

        // Sembunyikan empty state, tampilkan hasil
        document.getElementById('empty-jual').classList.add('hidden');
        document.getElementById('hasil-jual').classList.remove('hidden');

        // Metrik
        const omzet   = d.ringkasan?.omzet ?? 0;
        const trx     = d.ringkasan?.total_transaksi ?? 0;
        const profit  = m.profit_kotor ?? 0;
        const margin  = m.margin_persen ?? 0;
        const avg     = trx > 0 ? omzet / trx : 0;

        document.getElementById('m-omzet').textContent   = 'Rp ' + fmt(omzet);
        document.getElementById('m-trx').textContent     = fmt(trx);
        document.getElementById('m-profit').textContent  = 'Rp ' + fmt(profit);
        document.getElementById('m-margin').textContent  = `margin ${margin.toFixed(1)}%`;
        document.getElementById('m-avg').textContent     = 'Rp ' + fmt(Math.round(avg));
        document.getElementById('label-periode-jual').textContent = `${fmtTgl(dari)} – ${fmtTgl(sampai)}`;

        // Charts
        renderLineChart(d.per_hari ?? []);
        renderPieChart(d.metode_bayar ?? {});

        // Tabel terlaris
        renderTopProduk(d.produk_terlaris ?? []);

    } catch (err) {
        showToast('error', 'Gagal generate laporan', err.response?.data?.message || 'Periksa koneksi');
    } finally {
        setLoading('jual', false);
    }
}

function renderLineChart(perHari) {
    const isDark = document.documentElement.classList.contains('dark');
    const grid   = isDark ? 'rgba(75,85,99,0.4)' : 'rgba(229,231,235,0.8)';
    const txt    = isDark ? '#9ca3af' : '#6b7280';

    const labels = perHari.map(r => {
        const d = new Date(r.tanggal + 'T00:00:00');
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    });
    const values = perHari.map(r => r.total_omzet ?? 0);

    if (chartOmzet) chartOmzet.destroy();

    chartOmzet = new Chart(document.getElementById('chartOmzet'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Omzet',
                data: values,
                borderColor:          'rgba(34,197,94,1)',   // green-500
                backgroundColor:      'rgba(34,197,94,0.08)',
                borderWidth:          2.5,
                tension:              0.4,
                fill:                 true,
                pointBackgroundColor: 'rgba(34,197,94,1)',
                pointRadius:          4,
                pointHoverRadius:     6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { color: grid }, ticks: { color: txt, font: { size: 11 } } },
                y: {
                    beginAtZero: true,
                    grid:  { color: grid },
                    ticks: {
                        color: txt, font: { size: 11 },
                        callback: v => 'Rp ' + new Intl.NumberFormat('id-ID',
                            { notation: 'compact', maximumFractionDigits: 1 }).format(v),
                    },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => 'Rp ' + fmt(ctx.raw) } },
            },
        },
    });
}

function renderPieChart(metode) {
    const tunai    = metode.tunai    ?? 0;
    const transfer = metode.transfer ?? 0;
    const qris     = metode.qris     ?? 0;
    const total    = tunai + transfer + qris;

    if (chartMetode) chartMetode.destroy();

    chartMetode = new Chart(document.getElementById('chartMetode'), {
        type: 'doughnut',
        data: {
            labels: ['Tunai', 'Transfer', 'QRIS'],
            datasets: [{
                data: [tunai, transfer, qris],
                backgroundColor: [
                    'rgba(99,102,241,0.85)',   // indigo
                    'rgba(34,197,94,0.85)',    // green
                    'rgba(59,130,246,0.85)',   // blue
                ],
                borderColor: [
                    'rgba(99,102,241,1)',
                    'rgba(34,197,94,1)',
                    'rgba(59,130,246,1)',
                ],
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const val  = ctx.raw;
                            const pct  = total > 0 ? (val / total * 100).toFixed(1) : 0;
                            return `${ctx.label}: Rp ${fmt(val)} (${pct}%)`;
                        }
                    }
                },
            },
        },
    });

    // Custom legend
    const colors = ['bg-indigo-500','bg-green-500','bg-blue-500'];
    const labels = ['Tunai','Transfer','QRIS'];
    const vals   = [tunai, transfer, qris];
    document.getElementById('legend-metode').innerHTML = labels.map((l, i) => {
        const pct = total > 0 ? (vals[i] / total * 100).toFixed(1) : 0;
        return `<div class="flex items-center justify-between text-xs">
            <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full ${colors[i]}"></span>
                <span class="text-gray-600 dark:text-gray-400">${l}</span>
            </span>
            <span class="font-mono text-gray-900 dark:text-gray-100">${pct}%</span>
        </div>`;
    }).join('');
}

function renderTopProduk(rows) {
    const tbody = document.getElementById('tbody-terlaris');
    const empty = document.getElementById('empty-terlaris');

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }
    empty.classList.add('hidden');

    tbody.innerHTML = rows.map((p, i) => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold ${i < 3 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'}">
                    ${i + 1}
                </span>
            </td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">${esc(p.nama_produk)}</td>
            <td class="px-4 py-3 text-center">
                <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">${esc(p.satuan_dasar)}</span>
            </td>
            <td class="px-4 py-3 text-right font-mono text-sm text-gray-900 dark:text-gray-100">${fmt(p.qty_terjual)}</td>
            <td class="px-4 py-3 text-right font-mono text-sm font-semibold text-indigo-600 dark:text-indigo-400">Rp ${fmt(p.total_pendapatan)}</td>
        </tr>`).join('');
}

// ── TAB STOK ──────────────────────────────────────────────────────────────────
async function generateStok() {
    const kategoriId = document.getElementById('stok-kategori').value;
    setLoading('stok', true);

    try {
        const params = kategoriId ? `?kategori_id=${kategoriId}` : '';
        const { data } = await axios.get(`/api/owner/laporan/stok${params}`);
        const d = data.data;

        document.getElementById('empty-stok').classList.add('hidden');
        document.getElementById('hasil-stok').classList.remove('hidden');

        document.getElementById('stok-total-produk').textContent = fmt(d.total_produk ?? 0);
        document.getElementById('stok-total-nilai').textContent  = 'Rp ' + fmt(d.total_nilai_stok ?? 0);
        document.getElementById('stok-rendah-cnt').textContent   = fmt(d.stok_rendah ?? 0);

        renderTabelStok(d.produk ?? [], d.total_nilai_stok ?? 0);

    } catch (err) {
        showToast('error', 'Gagal generate laporan stok', err.response?.data?.message || '');
    } finally {
        setLoading('stok', false);
    }
}

function renderTabelStok(rows, totalNilai) {
    const tbody = document.getElementById('tbody-stok');
    const tfoot = document.getElementById('tfoot-stok');

    const BADGE = {
        normal: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        rendah: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        habis:  'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };

    tbody.innerHTML = rows.map(p => {
        const isHabis  = p.qty_stok === 0;
        const isRendah = p.is_rendah && !isHabis;
        const rowBg    = isHabis ? 'bg-red-50 dark:bg-red-900/10' : isRendah ? 'bg-amber-50 dark:bg-amber-900/10' : '';
        const badgeCls = isHabis ? BADGE.habis : isRendah ? BADGE.rendah : BADGE.normal;
        const badgeTxt = isHabis ? 'Habis' : isRendah ? 'Rendah' : 'Normal';

        return `
        <tr class="hover:brightness-95 transition-colors ${rowBg}">
            <td class="px-4 py-3">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${esc(p.nama_produk)}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">${esc(p.kode_produk)} · ${esc(p.kategori ?? '—')}</p>
            </td>
            <td class="px-4 py-3 text-center">
                <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">${esc(p.satuan_dasar)}</span>
            </td>
            <td class="px-4 py-3 text-right font-mono text-sm font-semibold text-gray-900 dark:text-gray-100">${fmt(p.qty_stok)}</td>
            <td class="px-4 py-3 text-right font-mono text-sm text-gray-500 dark:text-gray-400">${fmt(p.stok_minimum)}</td>
            <td class="px-4 py-3 text-right font-mono text-sm text-gray-900 dark:text-gray-100">Rp ${fmt(p.nilai_stok)}</td>
            <td class="px-4 py-3 text-right font-mono text-sm text-gray-600 dark:text-gray-400">${fmt(p.pergerakan_30hari)}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeCls}">${badgeTxt}</span>
            </td>
        </tr>`;
    }).join('');

    tfoot.innerHTML = `
        <tr>
            <td colspan="4" class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Total Nilai Stok</td>
            <td class="px-4 py-3 text-right font-mono text-sm font-bold text-indigo-600 dark:text-indigo-400">Rp ${fmt(totalNilai)}</td>
            <td colspan="2"></td>
        </tr>`;
}

// ── TAB PEMBELIAN ─────────────────────────────────────────────────────────────
async function generatePembelian() {
    const dari   = document.getElementById('beli-dari').value;
    const sampai = document.getElementById('beli-sampai').value;
    if (!dari || !sampai) { showToast('warning', 'Filter diperlukan', 'Pilih rentang tanggal terlebih dahulu'); return; }

    lastBeliDari = dari; lastBeliSampai = sampai;
    setLoading('beli', true);

    try {
        const { data } = await axios.get(`/api/owner/laporan/pembelian?dari=${dari}&sampai=${sampai}`);
        const d = data.data;

        document.getElementById('empty-beli').classList.add('hidden');
        document.getElementById('hasil-beli').classList.remove('hidden');

        // Metrik
        document.getElementById('beli-total').textContent  = 'Rp ' + fmt(d.ringkasan?.total_belanja ?? 0);
        document.getElementById('beli-jumlah').textContent = fmt(d.ringkasan?.total_po ?? 0);
        document.getElementById('beli-jumlah-sub').textContent =
            `${d.ringkasan?.po_selesai ?? 0} selesai · ${d.ringkasan?.po_batal ?? 0} batal`;

        const topSup = (d.per_supplier ?? [])[0];
        document.getElementById('beli-top-supplier').textContent     = topSup?.nama_supplier ?? '—';
        document.getElementById('beli-top-supplier-sub').textContent = topSup
            ? `${topSup.jumlah_po} PO · Rp ${fmt(topSup.total_belanja)}`
            : '';

        // Tabel PO
        renderTabelPembelian(d.daftar_po ?? []);

    } catch (err) {
        showToast('error', 'Gagal generate laporan pembelian', err.response?.data?.message || '');
    } finally {
        setLoading('beli', false);
    }
}

function renderTabelPembelian(rows) {
    const tbody = document.getElementById('tbody-beli');
    const empty = document.getElementById('empty-beli-tbl');

    if (!rows.length) {
        tbody.innerHTML = ''; empty.classList.remove('hidden'); return;
    }
    empty.classList.add('hidden');

    const BADGE = {
        COMPLETED: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        PENDING:   'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        CANCELLED: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    };
    const LABEL = { COMPLETED: 'Selesai', PENDING: 'Proses', CANCELLED: 'Batal' };

    tbody.innerHTML = rows.map(po => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">${esc(po.no_pembelian)}</td>
            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">${esc(po.nama_supplier)}</td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">${fmtTgl(po.tanggal_pembelian)}</td>
            <td class="px-4 py-3 text-right font-mono text-sm font-semibold text-gray-900 dark:text-gray-100">Rp ${fmt(po.total)}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${BADGE[po.status] ?? ''}">
                    ${LABEL[po.status] ?? po.status}
                </span>
            </td>
        </tr>`).join('');
}

// ── TAB HUTANG DAGANG ─────────────────────────────────────────────────────────
async function generateHutang() {
    setLoading('hutang', true);
    try {
        const { data } = await axios.get('/api/owner/laporan/hutang');
        const d = data.data;

        document.getElementById('empty-hutang').classList.add('hidden');
        document.getElementById('hasil-hutang').classList.remove('hidden');

        // API mengembalikan d.summary (bukan d.ringkasan)
        document.getElementById('hutang-total').textContent    = 'Rp ' + fmt(d.summary?.grand_total_hutang ?? 0);
        document.getElementById('hutang-menunggak').textContent = fmt(d.summary?.po_terlewat ?? 0) + ' PO';

        renderTabelHutang(d.per_supplier ?? [], d.summary?.grand_total_hutang ?? 0);
    } catch (err) {
        showToast('error', 'Gagal generate laporan hutang', err.response?.data?.message || '');
    } finally {
        setLoading('hutang', false);
    }
}

function renderTabelHutang(rows, grandTotalSisa) {
    const tbody = document.getElementById('tbody-hutang');
    const tfoot = document.getElementById('tfoot-hutang');
    const empty = document.getElementById('empty-hutang-tbl');

    if (!rows.length) {
        tbody.innerHTML = '';
        tfoot.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }
    empty.classList.add('hidden');

    // per_supplier fields: nama_supplier, jumlah_po, grand_total_po, sudah_dibayar, total_sisa
    tbody.innerHTML = rows.map(s => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">${esc(s.nama_supplier)}</td>
            <td class="px-4 py-3 text-center font-mono text-sm text-gray-600 dark:text-gray-400">${fmt(s.jumlah_po)}</td>
            <td class="px-4 py-3 text-right font-mono text-sm text-gray-900 dark:text-gray-100">Rp ${fmt(s.grand_total_po)}</td>
            <td class="px-4 py-3 text-right font-mono text-sm text-green-600 dark:text-green-400">Rp ${fmt(s.sudah_dibayar)}</td>
            <td class="px-4 py-3 text-right font-mono text-sm font-semibold text-red-600 dark:text-red-400">Rp ${fmt(s.total_sisa)}</td>
        </tr>`).join('');

    tfoot.innerHTML = `
        <tr>
            <td colspan="4" class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 text-right">Grand Total Sisa Hutang</td>
            <td class="px-4 py-3 text-right font-mono text-sm font-bold text-red-600 dark:text-red-400">Rp ${fmt(grandTotalSisa)}</td>
        </tr>`;
}

// ── TAB PENYESUAIAN STOK ──────────────────────────────────────────────────────
async function generatePenyesuaianStok() {
    const dari   = document.getElementById('sesuai-dari').value;
    const sampai = document.getElementById('sesuai-sampai').value;
    if (!dari || !sampai) { showToast('warning', 'Filter diperlukan', 'Pilih rentang tanggal terlebih dahulu'); return; }

    lastSesuaiDari = dari; lastSesuaiSampai = sampai;
    setLoading('sesuai', true);

    try {
        const { data } = await axios.get(`/api/owner/laporan/penyesuaian-stok?dari=${dari}&sampai=${sampai}`);
        const d = data.data;

        document.getElementById('empty-sesuai').classList.add('hidden');
        document.getElementById('hasil-sesuai').classList.remove('hidden');

        // API mengembalikan d.per_tipe.{tipe}.jumlah_item (bukan d.ringkasan)
        const pt = d.per_tipe ?? {};
        document.getElementById('sesuai-rusak').textContent   = fmt(pt.barang_rusak?.jumlah_item   ?? 0) + ' item';
        document.getElementById('sesuai-hilang').textContent  = fmt(pt.barang_hilang?.jumlah_item  ?? 0) + ' item';
        document.getElementById('sesuai-expired').textContent = fmt(pt.barang_expired?.jumlah_item ?? 0) + ' item';

        // d.daftar bukan d.riwayat
        renderTabelPenyesuaian(d.daftar ?? []);
    } catch (err) {
        showToast('error', 'Gagal generate laporan', err.response?.data?.message || '');
    } finally {
        setLoading('sesuai', false);
    }
}

// Peta tipe → badge (sesuai TIPE_CONFIG di penyesuaian-stok/index.blade.php)
const TIPE_BADGE = {
    barang_rusak:   { label: 'Rusak',      cls: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' },
    barang_hilang:  { label: 'Hilang',     cls: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400' },
    barang_expired: { label: 'Expired',    cls: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400' },
    koreksi_plus:   { label: '+ Koreksi',  cls: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' },
    koreksi_minus:  { label: '− Koreksi',  cls: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' },
};

function renderTabelPenyesuaian(rows) {
    const tbody = document.getElementById('tbody-sesuai');
    const empty = document.getElementById('empty-sesuai-tbl');

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }
    empty.classList.add('hidden');

    const KURANG = new Set(['barang_rusak', 'barang_hilang', 'barang_expired', 'koreksi_minus']);

    tbody.innerHTML = rows.map(r => {
        const badge = TIPE_BADGE[r.tipe] ?? { label: r.tipe, cls: 'bg-gray-100 text-gray-600' };
        const isKurang = KURANG.has(r.tipe);
        const qtyColor = isKurang ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
        const sign     = isKurang ? '−' : '+';
        // API mengembalikan r.tanggal (bukan r.tanggal_penyesuaian)
        const tgl = r.tanggal
            ? new Date(r.tanggal).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
            : '—';

        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">${tgl}</td>
            <td class="px-4 py-3">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${esc(r.nama_produk)}</p>
                <p class="text-xs font-mono text-gray-400 dark:text-gray-500">${esc(r.kode_produk ?? '')}</p>
            </td>
            <td class="px-4 py-3">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold ${badge.cls}">${badge.label}</span>
            </td>
            <td class="px-4 py-3 text-right font-mono text-sm font-semibold ${qtyColor}">
                ${sign}${fmt(r.qty_satuan_dasar)}
                <span class="text-xs font-normal text-gray-400 ml-0.5">${esc(r.satuan_dasar ?? '')}</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 max-w-[200px]">
                <span class="line-clamp-2" title="${esc(r.keterangan ?? '')}">${esc(r.keterangan ?? '')}</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">${esc(r.nama_admin ?? '—')}</td>
        </tr>`;
    }).join('');
}

// ── Export ────────────────────────────────────────────────────────────────────
async function exportLaporan(jenis, format) {
    let params = new URLSearchParams({ format });

    if (jenis === 'penjualan') {
        if (!lastJualDari)  { showToast('warning', 'Generate dahulu', 'Klik Generate sebelum export'); return; }
        params.set('dari', lastJualDari);  params.set('sampai', lastJualSampai);
    } else if (jenis === 'pembelian') {
        if (!lastBeliDari)  { showToast('warning', 'Generate dahulu', 'Klik Generate sebelum export'); return; }
        params.set('dari', lastBeliDari);  params.set('sampai', lastBeliSampai);
    } else if (jenis === 'penyesuaian-stok') {
        if (!lastSesuaiDari) { showToast('warning', 'Generate dahulu', 'Klik Generate sebelum export'); return; }
        params.set('dari', lastSesuaiDari); params.set('sampai', lastSesuaiSampai);
    } else if (jenis === 'stok') {
        const kat = document.getElementById('stok-kategori').value;
        if (kat) params.set('kategori_id', kat);
    }

    try {
        showToast('info', 'Mengunduh...', 'File sedang disiapkan');

        const response = await axios.get(`/api/owner/laporan/${jenis}/export?${params}`, {
            responseType: 'blob',
        });

        const ext      = format === 'pdf' ? 'html' : 'csv';
        const filename = `laporan-${jenis}-${new Date().toLocaleDateString('sv')}.${ext}`;
        const url      = URL.createObjectURL(new Blob([response.data]));
        const a        = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

    } catch (err) {
        showToast('error', 'Gagal export', err.response?.data?.message || 'Terjadi kesalahan');
    }
}

// ── Loading helpers ───────────────────────────────────────────────────────────
function setLoading(tab, on) {
    const btn  = document.getElementById(`btn-gen-${tab}`);
    const spin = document.getElementById(`spin-${tab}`);
    if (btn)  btn.disabled = on;
    if (spin) spin.classList.toggle('hidden', !on);
}

// ── Utilitas ──────────────────────────────────────────────────────────────────
function fmt(n) { return new Intl.NumberFormat('id-ID').format(n ?? 0); }
function esc(s) {
    if (s == null) return '—';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function fmtTgl(str) {
    if (!str) return '—';
    try {
        return new Date(str + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (_) { return str; }
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    defaultDates();
    loadKategoriDropdown();

    // Restore tab terakhir dari session
    const savedTab = sessionStorage.getItem('laporan-tab') || 'penjualan';
    switchTab(savedTab);
});
</script>
@endpush
