@extends('layouts.app')

@section('title', 'Dashboard Owner')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Tombol laporan lengkap --}}
    <div class="flex justify-end">
        <a href="/laporan/penjualan"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Lihat Laporan Lengkap
        </a>
    </div>

    {{-- 4 kartu angka besar --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Omzet Hari Ini</p>
            <p id="stat-omzet-hari" class="mt-2 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">
                <span class="animate-pulse inline-block w-32 h-8 bg-gray-200 dark:bg-gray-700 rounded"></span>
            </p>
            <p id="stat-trx-hari" class="mt-1 text-xs text-gray-400 dark:text-gray-500">— transaksi</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Omzet Bulan Ini</p>
            <p id="stat-omzet-bulan" class="mt-2 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">
                <span class="animate-pulse inline-block w-36 h-8 bg-gray-200 dark:bg-gray-700 rounded"></span>
            </p>
            <p id="stat-trx-bulan" class="mt-1 text-xs text-gray-400 dark:text-gray-500">— transaksi</p>
        </div>

        {{-- Perbandingan vs bulan lalu: naik=hijau, turun=merah --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">vs. Bulan Lalu</p>
            <div class="mt-2 flex items-baseline gap-2">
                <p id="stat-persen-vs" class="text-3xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
                <span id="ikon-vs" class="text-lg"></span>
            </div>
            <p id="label-vs" class="mt-1 text-xs text-gray-400 dark:text-gray-500">perbandingan omzet</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transaksi Hari Ini</p>
            <p id="stat-total-trx-hari" class="mt-2 text-3xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
        </div>
    </div>

    {{-- Grafik + Top 5 Produk --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Grafik omzet 7 hari (Chart.js) --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Omzet 7 Hari Terakhir</h3>
            <div class="mt-4 h-56 relative">
                <div id="chart-skeleton" class="animate-pulse space-y-3 mt-4">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                    <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                    <div class="flex justify-between">
                        @for ($i = 0; $i < 7; $i++)<div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-8"></div>@endfor
                    </div>
                </div>
                <canvas id="omzetChart" class="hidden"></canvas>
            </div>
        </div>

        {{-- Top 5 Produk Terlaris --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Top 5 Terlaris</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500">bulan ini</p>
            </div>
            <div id="top-produk-skeleton" class="p-4 space-y-3">
                @for ($i = 0; $i < 5; $i++)
                <div class="animate-pulse flex justify-between">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                </div>
                @endfor
            </div>
            <ul id="list-top-produk" class="hidden divide-y divide-gray-200 dark:divide-gray-700">
                {{-- Diisi JS --}}
            </ul>
        </div>
    </div>

    {{-- Tabel 5 transaksi terbesar hari ini --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">5 Transaksi Terbesar Hari Ini</h3>
        </div>
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 5; $i++)
            <div class="animate-pulse flex gap-4">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
            </div>
            @endfor
        </div>
        <div id="tbl-besar" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No Transaksi</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Waktu</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody id="tbody-besar" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>
            <p id="tbl-empty" class="hidden px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Belum ada transaksi hari ini.</p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const isBD = document.documentElement.classList.contains('dark');
let chartInstance = null;

async function loadDashboard() {
    const today        = new Date().toLocaleDateString('sv');
    const firstOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toLocaleDateString('sv');
    const firstOfLastM = new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1).toLocaleDateString('sv');
    const lastOfLastM  = new Date(new Date().getFullYear(), new Date().getMonth(), 0).toLocaleDateString('sv');
    const sevenDaysAgo = new Date(Date.now() - 6 * 86400000).toLocaleDateString('sv');

    try {
        // 3 panggilan paralel — hemat waktu vs sequential
        const [resHari, resBulan, resLastBulan, res7Hari] = await Promise.all([
            axios.get(`/api/owner/laporan/penjualan?dari=${today}&sampai=${today}`),
            axios.get(`/api/owner/laporan/penjualan?dari=${firstOfMonth}&sampai=${today}`),
            axios.get(`/api/owner/laporan/penjualan?dari=${firstOfLastM}&sampai=${lastOfLastM}`),
            axios.get(`/api/owner/laporan/penjualan?dari=${sevenDaysAgo}&sampai=${today}`),
        ]);

        const dHari     = resHari.data;
        const dBulan    = resBulan.data;
        const dLastBulan= resLastBulan.data;
        const d7Hari    = res7Hari.data;

        // ── Widget omzet hari ini ─────────────────────────────────────────────
        document.getElementById('stat-omzet-hari').textContent =
            'Rp ' + new Intl.NumberFormat('id-ID').format(dHari.data.ringkasan.omzet);
        document.getElementById('stat-trx-hari').textContent =
            dHari.data.ringkasan.total_transaksi + ' transaksi';
        document.getElementById('stat-total-trx-hari').textContent =
            dHari.data.ringkasan.total_transaksi;

        // ── Widget omzet bulan ini ────────────────────────────────────────────
        document.getElementById('stat-omzet-bulan').textContent =
            'Rp ' + new Intl.NumberFormat('id-ID').format(dBulan.data.ringkasan.omzet);
        document.getElementById('stat-trx-bulan').textContent =
            dBulan.data.ringkasan.total_transaksi + ' transaksi';

        // ── Perbandingan vs bulan lalu ────────────────────────────────────────
        const omzetBulan    = dBulan.data.ringkasan.omzet    || 0;
        const omzetLastBulan = dLastBulan.data.ringkasan.omzet || 0;
        const elVs   = document.getElementById('stat-persen-vs');
        const elIkon = document.getElementById('ikon-vs');

        if (omzetLastBulan > 0) {
            const persen = ((omzetBulan - omzetLastBulan) / omzetLastBulan * 100).toFixed(1);
            const naik   = persen >= 0;
            elVs.textContent   = (naik ? '+' : '') + persen + '%';
            elVs.className     = 'text-3xl font-bold font-mono ' + (naik
                ? 'text-green-600 dark:text-green-400'
                : 'text-red-600 dark:text-red-400');
            elIkon.textContent = naik ? '↑' : '↓';
            elIkon.className   = 'text-lg ' + (naik
                ? 'text-green-500 dark:text-green-400'
                : 'text-red-500 dark:text-red-400');
        } else {
            elVs.textContent = 'N/A';
        }

        // ── Grafik 7 hari (Chart.js) ──────────────────────────────────────────
        const perHari = d7Hari.data.per_hari || [];
        renderChart(perHari);

        // ── Top 5 produk terlaris ─────────────────────────────────────────────
        const topProduk = (dBulan.data.produk_terlaris || []).slice(0, 5);
        renderTopProduk(topProduk);

        // ── Tabel 5 transaksi terbesar hari ini ───────────────────────────────
        // Laporan endpoint tidak memberi transaksi individual, gunakan per_hari
        // dan tampilkan summary; transaksi individual bisa ditambah endpoint khusus
        const topTransaksi = (dHari.data.per_hari || []).slice(0, 5);
        renderTabel(topTransaksi, dHari.data.ringkasan);

    } catch (err) {
        console.error('Gagal load dashboard owner:', err);
    }
}

function renderChart(perHari) {
    document.getElementById('chart-skeleton').classList.add('hidden');
    const canvas = document.getElementById('omzetChart');
    canvas.classList.remove('hidden');

    const isDark   = document.documentElement.classList.contains('dark');
    const gridColor= isDark ? 'rgba(75,85,99,0.4)' : 'rgba(229,231,235,0.8)';
    const textColor= isDark ? '#9ca3af' : '#6b7280';

    const labels = perHari.map(r => {
        const d = new Date(r.tanggal);
        return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric' });
    });
    const values = perHari.map(r => r.total_omzet || 0);

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Omzet',
                data: values,
                borderColor:     'rgba(99,102,241,1)',
                backgroundColor: 'rgba(99,102,241,0.08)',
                borderWidth:     2,
                tension:         0.4,
                fill:            true,
                pointBackgroundColor: 'rgba(99,102,241,1)',
                pointRadius:     4,
                pointHoverRadius:6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor, font: { size: 11 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor,
                        font: { size: 11 },
                        callback: v => 'Rp ' + new Intl.NumberFormat('id-ID',
                            { notation: 'compact', maximumFractionDigits: 1 }).format(v),
                    },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Rp ' + new Intl.NumberFormat('id-ID').format(ctx.raw),
                    },
                },
            },
        },
    });
}

function renderTopProduk(rows) {
    document.getElementById('top-produk-skeleton').classList.add('hidden');
    const list = document.getElementById('list-top-produk');
    list.classList.remove('hidden');

    if (!rows || rows.length === 0) {
        list.innerHTML = '<li class="px-6 py-4 text-sm text-gray-400 dark:text-gray-500">Belum ada data.</li>';
        return;
    }

    list.innerHTML = rows.map((p, i) => `
        <li class="px-6 py-3.5 flex items-center justify-between text-sm">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-5 h-5 shrink-0 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-xs font-bold flex items-center justify-center">
                    ${i + 1}
                </span>
                <span class="text-gray-800 dark:text-gray-200 truncate">${p.nama_produk}</span>
            </div>
            <span class="font-semibold text-gray-900 dark:text-gray-100 shrink-0 ml-3">
                ${new Intl.NumberFormat('id-ID').format(p.qty_terjual)} ${p.satuan_dasar}
            </span>
        </li>`).join('');
}

function renderTabel(rows, ringkasan) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-besar').classList.remove('hidden');

    const tbody = document.getElementById('tbody-besar');
    const empty = document.getElementById('tbl-empty');

    // Laporan per_hari tidak memiliki detail transaksi individual
    // Tampilkan ringkasan hari ini sebagai satu baris ringkasan
    if (!rows || rows.length === 0 || ringkasan.total_transaksi === 0) {
        empty.classList.remove('hidden');
        return;
    }

    // Tampilkan satu baris per hari jika data per transaksi belum tersedia
    tbody.innerHTML = rows.map(r => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">${r.tanggal}</td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">—</td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">${r.jumlah_transaksi} transaksi</td>
            <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-gray-100">
                Rp ${new Intl.NumberFormat('id-ID').format(r.total_omzet)}
            </td>
        </tr>`).join('');
}

// ── WebSocket real-time ───────────────────────────────────────────────────────
// Refresh dashboard owner setiap kali ada transaksi/pembelian/retur selesai diproses
// stok-updated di-dispatch oleh app.blade.php saat menerima stock.updated dari Consumer
document.addEventListener('stok-updated', function () { loadDashboard(); });

// Regenerate chart saat dark mode toggle agar warna grid menyesuaikan
document.getElementById('omzetChart')?.addEventListener('toggle-theme', function () {
    if (chartInstance) {
        const isDark = document.documentElement.classList.contains('dark');
        chartInstance.options.scales.x.grid.color = isDark ? 'rgba(75,85,99,0.4)' : 'rgba(229,231,235,0.8)';
        chartInstance.update();
    }
});

document.addEventListener('DOMContentLoaded', loadDashboard);
</script>
@endpush
