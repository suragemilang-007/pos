@extends('layouts.app')

@section('title', 'Dashboard Kasir')

@section('content')
<div class="space-y-6">
    <!-- Tombol Aksi Utama -->
    <div class="text-center">
        <a href="{{-- route('kasir.transaksi.create') --}}" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-base font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Transaksi Baru</span>
        </a>
    </div>

    <!-- Widget Baris Atas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Transaksi Hari Ini -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Hari Ini</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">124</p>
        </div>
        <!-- Omzet Hari Ini -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Omzet Hari Ini</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">Rp 12.345.678</p>
        </div>
        <!-- Produk Stok Rendah -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <a href="{{-- route('kasir.stok.index') --}}" class="block hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg -m-6 p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Produk Stok Rendah</h3>
                        <p id="stok-rendah-badge" class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            15
                            <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 text-xs font-semibold rounded-full">
                                Rendah
                            </span>
                        </p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
        </div>
        <!-- Transaksi Terakhir -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Terakhir</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">15:42</p>
        </div>
    </div>

    <!-- Tabel 5 Transaksi Terakhir -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">5 Transaksi Terakhir</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No Transaksi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Waktu</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Metode</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    {{-- Contoh Data --}}
                    @for ($i = 0; $i < 5; $i++)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">TRX-20240520-00{{ 5 - $i }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">15:{{ 42 - $i }}:10</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-medium">Rp {{ number_format(350000 - ($i * 15000), 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">Tunai</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                COMPLETED
                            </span>
                        </td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Implementasi WebSocket untuk update badge stok rendah
    // Contoh:
    // const socket = new WebSocket('ws://localhost:3001');
    // socket.onmessage = function(event) {
    //     const data = JSON.parse(event.data);
    //     if (data.event === 'stock.updated') {
    //         const badge = document.getElementById('stok-rendah-badge');
    //         badge.innerHTML = `${data.lowStockCount} <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 text-xs font-semibold rounded-full">Rendah</span>`;
    //         // Mungkin tambahkan efek visual untuk notifikasi
    //         badge.classList.add('animate-pulse');
    //         setTimeout(() => badge.classList.remove('animate-pulse'), 2000);
    //     }
    // };
</script>
@endpush
