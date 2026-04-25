@extends('layouts.app')

@section('title', 'Dashboard Admin Gudang')

@section('content')
<div class="space-y-6">
    <!-- Tombol Aksi Cepat -->
    <div class="flex items-center justify-start gap-4">
        <a href="{{-- route('gudang.pembelian.create') --}}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            Buat PO Baru
        </a>
        <a href="{{-- route('gudang.stok.index') --}}" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
            Lihat Stok
        </a>
    </div>

    <!-- Widget Baris Atas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Produk Aktif -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Produk Aktif</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">1,250</p>
        </div>
        <!-- Produk Stok Rendah -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
             <a href="{{-- route('gudang.stok.index', ['filter' => 'rendah']) --}}" class="block hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg -m-6 p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Produk Stok Rendah</h3>
                        <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">15</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
        </div>
        <!-- Total Pembelian Bulan Ini -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pembelian Bulan Ini</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">Rp 87.500.000</p>
        </div>
        <!-- PO Pending -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">PO Pending</h3>
            <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">3</p>
        </div>
    </div>

    <!-- Tabel 5 Pembelian Terakhir -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">5 Pembelian Terakhir</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No PO</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Supplier</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    {{-- Contoh Data --}}
                    @php
                        $statuses = [
                            ['class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'label' => 'COMPLETED'],
                            ['class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'label' => 'COMPLETED'],
                            ['class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400', 'label' => 'PENDING'],
                            ['class' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400', 'label' => 'CANCELLED'],
                            ['class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'label' => 'COMPLETED'],
                        ];
                    @endphp
                    @for ($i = 0; $i < 5; $i++)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">PO-202405-00{{ 15 - $i }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">Supplier {{ chr(65 + $i) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-medium">Rp {{ number_format(12500000 - ($i * 750000), 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statuses[$i]['class'] }}">
                                {{ $statuses[$i]['label'] }}
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
