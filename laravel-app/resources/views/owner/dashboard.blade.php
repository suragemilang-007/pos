@extends('layouts.app')

@section('title', 'Dashboard Owner')

@section('content')
<div class="space-y-6">
    <!-- Tombol Aksi Cepat -->
    <div class="flex items-center justify-start">
        <a href="{{-- route('owner.laporan.index') --}}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H3V9a4 4 0 004-4V3l4 4-4 4zM13 3l4 4-4 4M21 11h-4a4 4 0 00-4 4v2" /></svg>
            Lihat Laporan Lengkap
        </a>
    </div>

    <!-- Widget Baris Atas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Omzet Hari Ini -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Omzet Hari Ini</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">Rp 12.345.678</p>
        </div>
        <!-- Omzet Bulan Ini -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Omzet Bulan Ini</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">Rp 250.123.456</p>
        </div>
        <!-- Perbandingan vs Bulan Lalu -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">vs. Bulan Lalu</h3>
            <div class="mt-2 flex items-baseline gap-2">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">+15.7%</p>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
            </div>
        </div>
        <!-- Total Transaksi Bulan Ini -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Bulan Ini</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">3,456</p>
        </div>
    </div>

    <!-- Grafik dan Tabel -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Grafik 7 Hari Terakhir -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">Grafik Omzet 7 Hari Terakhir</h3>
            <div class="mt-4 h-64">
                <canvas id="omzetChart"></canvas>
            </div>
        </div>
        <!-- Top 5 Produk Terlaris -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">Top 5 Produk Terlaris (Bulan Ini)</h3>
            </div>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @for ($i = 1; $i <= 5; $i++)
                <li class="px-6 py-4 flex justify-between items-center text-sm">
                    <span class="text-gray-800 dark:text-gray-200">Produk {{ chr(70 - $i) }}</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ 150 - ($i * 12) }} terjual</span>
                </li>
                @endfor
            </ul>
        </div>
    </div>

    <!-- Tabel 5 Transaksi Terbesar Hari Ini -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">5 Transaksi Terbesar Hari Ini</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No Transaksi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Waktu</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @for ($i = 0; $i < 5; $i++)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">TRX-20240520-00{{ 5 - $i }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">15:{{ 42 - $i }}:10</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">Customer {{ chr(65 + $i) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-bold">Rp {{ number_format(2500000 - ($i * 150000), 0, ',', '.') }}</td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('omzetChart').getContext('2d');
        const omzetChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                datasets: [{
                    label: 'Omzet',
                    data: [12, 19, 3, 5, 2, 3, 9].map(n => n * 1000000),
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return 'Rp ' + (value / 1000000) + ' Jt';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>
@endpush
