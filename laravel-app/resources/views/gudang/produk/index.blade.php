@extends('layouts.app')

@section('title', 'Data Produk')
@section('page-title', 'Data Produk')

@section('content')
<div class="space-y-5">

    {{-- ── Filter Bar ───────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">

            {{-- Cari nama produk --}}
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input id="filter-nama" type="text" placeholder="Cari nama produk..."
                    class="block w-full pl-9 pr-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    oninput="filterDebounce()">
            </div>

            {{-- Filter kategori --}}
            <select id="filter-kategori"
                class="sm:w-44 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadProduk(1)">
                <option value="">Semua Kategori</option>
            </select>

            {{-- Filter status --}}
            <select id="filter-status"
                class="sm:w-36 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadProduk(1)">
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
                <option value="semua">Semua Status</option>
            </select>

            {{-- Tombol Tambah --}}
            <a href="/gudang/produk/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 shrink-0 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Produk
            </a>
        </div>
    </div>

    {{-- ── Tabel ─────────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Skeleton --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 6; $i++)
            <div class="animate-pulse flex gap-3">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-12"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 ml-auto"></div>
            </div>
            @endfor
        </div>

        {{-- Tabel data --}}
        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kode</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama Produk</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategori</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Satuan Dasar</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">
                            <button onclick="toggleSortStok()"
                                class="inline-flex items-center justify-end gap-1 w-full hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                Stok
                                <span id="sort-stok-indicator">
                                    <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                                    </svg>
                                </span>
                            </button>
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-produk" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            {{-- Empty state --}}
            <div id="tbl-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada produk ditemukan.</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Coba ubah filter atau
                    <a href="/gudang/produk/create" class="text-indigo-600 dark:text-indigo-400 hover:underline">tambah produk baru</a>.
                </p>
            </div>
        </div>

        {{-- Pagination --}}
        <div id="pagination-wrapper" class="hidden px-6 py-3 border-t border-gray-200 dark:border-gray-700 items-center justify-between">
            <p id="pagination-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div id="pagination-btns" class="flex items-center gap-1"></div>
        </div>
    </div>

</div>
@endsection

{{-- ══ MODAL RIWAYAT PENJUALAN ════════════════════════════════════════════════ --}}
<div id="modal-penjualan"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shrink-0">
            <div>
                <h3 id="penjualan-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">Riwayat Penjualan</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Jumlah terjual per bulan berdasarkan satuan jual</p>
            </div>
            <div class="flex items-center gap-3">
                <select id="penjualan-tahun"
                    class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                    onchange="muatDataPenjualan()">
                </select>
                <button onclick="tutupModalPenjualan()"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="overflow-y-auto px-6 py-5 space-y-5">

            {{-- Skeleton --}}
            <div id="penjualan-skeleton" class="space-y-3">
                <div class="animate-pulse h-48 bg-gray-100 dark:bg-gray-700 rounded-xl"></div>
                <div class="animate-pulse grid grid-cols-3 gap-3">
                    <div class="h-16 bg-gray-100 dark:bg-gray-700 rounded-xl"></div>
                    <div class="h-16 bg-gray-100 dark:bg-gray-700 rounded-xl"></div>
                    <div class="h-16 bg-gray-100 dark:bg-gray-700 rounded-xl"></div>
                </div>
            </div>

            {{-- Konten --}}
            <div id="penjualan-content" class="hidden space-y-5">

                {{-- Ringkasan stat --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-3 text-center">
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Total Terjual</p>
                        <p id="stat-total-qty" class="text-xl font-bold text-emerald-700 dark:text-emerald-300 font-mono mt-0.5">0</p>
                        <p id="stat-satuan-dasar" class="text-xs text-emerald-500 dark:text-emerald-500">pcs</p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 text-center">
                        <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Total Pendapatan</p>
                        <p id="stat-total-pendapatan" class="text-xl font-bold text-blue-700 dark:text-blue-300 font-mono mt-0.5">Rp 0</p>
                        <p class="text-xs text-blue-500 dark:text-blue-500">dari transaksi</p>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-3 text-center">
                        <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Bulan Terlaris</p>
                        <p id="stat-bulan-terlaris" class="text-xl font-bold text-amber-700 dark:text-amber-300 mt-0.5">—</p>
                        <p id="stat-bulan-terlaris-qty" class="text-xs text-amber-500 dark:text-amber-500">—</p>
                    </div>
                </div>

                {{-- Grafik --}}
                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                    <canvas id="chart-penjualan" height="200"></canvas>
                </div>

                {{-- Empty state --}}
                <div id="penjualan-empty" class="hidden py-8 text-center">
                    <svg class="mx-auto w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada transaksi di tahun ini.</p>
                </div>

            </div>

            {{-- Error state --}}
            <div id="penjualan-error" class="hidden py-6 text-center">
                <p class="text-sm text-red-500 dark:text-red-400">Gagal memuat data penjualan.</p>
            </div>
        </div>
    </div>
</div>

{{-- ══ MODAL KONFIRMASI HAPUS ══════════════════════════════════════════════════ --}}
<div id="modal-hapus"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Hapus Produk</h3>
        </div>

        {{-- Panel: ada riwayat transaksi → soft delete --}}
        <div id="hapus-soft" class="hidden px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="shrink-0 w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Produk akan dinonaktifkan</p>
                    <p id="hapus-soft-pesan" class="text-sm text-gray-600 dark:text-gray-400 mt-1"></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                        Produk tidak dapat dihapus permanen karena masih ada di riwayat transaksi.
                        Setelah dinonaktifkan, produk tidak akan muncul di daftar aktif.
                    </p>
                </div>
            </div>
        </div>

        {{-- Panel: belum ada transaksi → hard delete --}}
        <div id="hapus-hard" class="hidden px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Produk akan dihapus permanen</p>
                    <p id="hapus-hard-pesan" class="text-sm text-gray-600 dark:text-gray-400 mt-1"></p>
                    <p class="text-xs text-red-500 dark:text-red-400 mt-2">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalHapus()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-hapus-ok" onclick="eksekusiHapus()"
                class="inline-flex items-center gap-2 px-4 py-2 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-hapus" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-hapus">Konfirmasi</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let produkList        = [];
let deleteTarget      = null;
let currentPage       = 1;
let lastPage          = 1;
let filterTimer       = null;
let sortDir           = null; // null = default (nama_produk asc), 'asc', 'desc'
let penjualanProdukId = null;
let penjualanChart    = null; // referensi Chart.js instance (untuk destroy sebelum re-render)

// ── Modal Riwayat Penjualan ───────────────────────────────────────────────────
function bukaModalPenjualan(id) {
    penjualanProdukId = id;
    const produk = produkList.find(p => p.id === id);
    const nama = produk ? produk.nama_produk : '';
    document.getElementById('penjualan-title').textContent = 'Penjualan — ' + nama;

    // Isi dropdown tahun: tahun ini ± 2
    const tahunSel = document.getElementById('penjualan-tahun');
    const tahunIni = new Date().getFullYear();
    tahunSel.innerHTML = '';
    for (let y = tahunIni + 1; y >= tahunIni - 3; y--) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        if (y === tahunIni) opt.selected = true;
        tahunSel.appendChild(opt);
    }

    tampilkanModal('modal-penjualan');
    muatDataPenjualan();
}

function tutupModalPenjualan() {
    sembunyikanModal('modal-penjualan');
    if (penjualanChart) { penjualanChart.destroy(); penjualanChart = null; }
    penjualanProdukId = null;
}

async function muatDataPenjualan() {
    if (!penjualanProdukId) return;

    const tahun = document.getElementById('penjualan-tahun').value;

    // Reset state UI
    document.getElementById('penjualan-skeleton').classList.remove('hidden');
    document.getElementById('penjualan-content').classList.add('hidden');
    document.getElementById('penjualan-error').classList.add('hidden');
    document.getElementById('penjualan-empty').classList.add('hidden');
    if (penjualanChart) { penjualanChart.destroy(); penjualanChart = null; }

    try {
        const { data } = await axios.get(`/api/gudang/produk/${penjualanProdukId}/penjualan?tahun=${tahun}`);
        renderModalPenjualan(data.data);
    } catch {
        document.getElementById('penjualan-skeleton').classList.add('hidden');
        document.getElementById('penjualan-error').classList.remove('hidden');
        document.getElementById('penjualan-content').classList.remove('hidden');
    }
}

function renderModalPenjualan(d) {
    document.getElementById('penjualan-skeleton').classList.add('hidden');
    document.getElementById('penjualan-content').classList.remove('hidden');

    const satuanDasar = d.satuan_dasar || 'pcs';
    document.getElementById('stat-satuan-dasar').textContent = satuanDasar;

    // Jika tidak ada data sama sekali
    if (!d.bulanan || d.bulanan.length === 0) {
        document.getElementById('penjualan-empty').classList.remove('hidden');
        document.getElementById('stat-total-qty').textContent = '0';
        document.getElementById('stat-total-pendapatan').textContent = 'Rp 0';
        document.getElementById('stat-bulan-terlaris').textContent = '—';
        document.getElementById('stat-bulan-terlaris-qty').textContent = '—';
        return;
    }

    // ── Hitung statistik ──────────────────────────────────────────────────
    let totalQtyDasar = 0, totalPendapatan = 0;
    let bulanTerlaris = null, maxQtyBulan = 0;

    // Susun data per bulan: qty satuan dasar = qty × isi_satuan
    const semuaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                         'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const dataPerBulan = Array.from({ length: 12 }, (_, i) => ({
        bulan: i + 1, label: semuaBulan[i], satuan: {}
    }));

    d.bulanan.forEach(b => {
        const idx = b.bulan - 1;
        let qtyBulanDasar = 0;
        b.satuan.forEach(s => {
            const qtyDasar = s.total_qty * s.isi_satuan;
            dataPerBulan[idx].satuan[s.nama_satuan] = {
                total_qty: s.total_qty,
                total_pendapatan: s.total_pendapatan,
            };
            totalQtyDasar   += qtyDasar;
            totalPendapatan += s.total_pendapatan;
            qtyBulanDasar   += qtyDasar;
        });
        if (qtyBulanDasar > maxQtyBulan) {
            maxQtyBulan  = qtyBulanDasar;
            bulanTerlaris = b.nama_bulan;
        }
    });

    document.getElementById('stat-total-qty').textContent = fmt(totalQtyDasar);
    document.getElementById('stat-total-pendapatan').textContent = 'Rp ' + fmt(Math.round(totalPendapatan));
    document.getElementById('stat-bulan-terlaris').textContent = bulanTerlaris || '—';
    document.getElementById('stat-bulan-terlaris-qty').textContent = maxQtyBulan > 0
        ? fmt(maxQtyBulan) + ' ' + satuanDasar : '—';

    // ── Bangun dataset Chart.js per satuan ───────────────────────────────
    const satuanList = d.satuan_list || [];
    const palette = [
        ['rgba(16,185,129,0.8)', 'rgba(16,185,129,1)'],  // emerald
        ['rgba(59,130,246,0.8)', 'rgba(59,130,246,1)'],   // blue
        ['rgba(245,158,11,0.8)', 'rgba(245,158,11,1)'],   // amber
        ['rgba(239,68,68,0.8)',  'rgba(239,68,68,1)'],    // red
        ['rgba(139,92,246,0.8)', 'rgba(139,92,246,1)'],   // violet
    ];

    const datasets = satuanList.map((satuan, i) => {
        const [bg, border] = palette[i % palette.length];
        return {
            label: satuan,
            data: dataPerBulan.map(b => b.satuan[satuan]?.total_qty ?? 0),
            backgroundColor: bg,
            borderColor: border,
            borderWidth: 1,
            borderRadius: 4,
        };
    });

    // Jika satuan_list kosong (tidak ada penjualan), chart tidak perlu ditampilkan
    if (datasets.length === 0) {
        document.getElementById('penjualan-empty').classList.remove('hidden');
        return;
    }

    // ── Render Chart.js ───────────────────────────────────────────────────
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor  = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.07)';
    const labelColor = isDark ? '#9ca3af' : '#6b7280';

    const ctx = document.getElementById('chart-penjualan').getContext('2d');
    penjualanChart = new Chart(ctx, {
        type: 'bar',
        data: { labels: semuaBulan, datasets },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: satuanList.length > 1,
                    labels: { color: labelColor, font: { size: 11 }, boxWidth: 12, padding: 12 },
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${fmt(ctx.raw)} unit`,
                    },
                },
            },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: labelColor, font: { size: 11 } } },
                y: {
                    grid: { color: gridColor },
                    ticks: {
                        color: labelColor, font: { size: 11 },
                        callback: v => fmt(v),
                    },
                    beginAtZero: true,
                },
            },
        },
    });
}

// ── Sort by Stok ──────────────────────────────────────────────────────────────
function toggleSortStok() {
    if (sortDir === null)        sortDir = 'asc';
    else if (sortDir === 'asc')  sortDir = 'desc';
    else                          sortDir = null;
    updateSortIndicator();
    loadProduk(1);
}

function updateSortIndicator() {
    const el = document.getElementById('sort-stok-indicator');
    if (!el) return;
    if (sortDir === 'asc') {
        el.innerHTML = `<svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 13l4-4 4 4"/></svg>`;
    } else if (sortDir === 'desc') {
        el.innerHTML = `<svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 11l4 4 4-4"/></svg>`;
    } else {
        el.innerHTML = `<svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>`;
    }
}

// ── Load Kategori (untuk dropdown filter) ────────────────────────────────────
async function loadKategoriFilter() {
    try {
        const { data } = await axios.get('/api/kategori');
        const sel = document.getElementById('filter-kategori');
        (data.data || []).forEach(k => {
            const opt = document.createElement('option');
            opt.value = k.id;
            opt.textContent = k.nama_kategori;
            sel.appendChild(opt);
        });
    } catch (_) {}
}

// ── Load Produk ───────────────────────────────────────────────────────────────
async function loadProduk(page = 1) {
    currentPage = page;

    const nama      = document.getElementById('filter-nama').value.trim();
    const kategori  = document.getElementById('filter-kategori').value;
    const status    = document.getElementById('filter-status').value;

    const params = new URLSearchParams({ page, limit: 20 });
    if (nama)     params.set('nama', nama);
    if (kategori) params.set('kategori_id', kategori);
    if (status)   params.set('status', status);
    if (sortDir !== null) { params.set('sort_by', 'qty_stok'); params.set('sort_dir', sortDir); }

    document.getElementById('tbl-skeleton').classList.remove('hidden');
    document.getElementById('tbl-wrapper').classList.add('hidden');
    document.getElementById('pagination-wrapper').classList.add('hidden');

    try {
        const { data } = await axios.get('/api/gudang/produk?' + params.toString());
        produkList = data.data || [];
        lastPage   = data.pagination?.last_page ?? 1;
        renderTabel(produkList, data.pagination);
    } catch (err) {
        showToast('error', 'Gagal memuat produk', err.response?.data?.message || 'Periksa koneksi');
        document.getElementById('tbl-skeleton').classList.add('hidden');
        document.getElementById('tbl-wrapper').classList.remove('hidden');
    }
}

function renderTabel(rows, pagination) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');

    const tbody = document.getElementById('tbody-produk');
    const empty = document.getElementById('tbl-empty');

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');
    tbody.innerHTML = rows.map(p => {
        const stokKritis  = p.qty_stok <= p.stok_minimum;
        const satuanList  = p.satuan_list || [];
        const statusBadge = p.status === 'aktif'
            ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Aktif</span>`
            : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Nonaktif</span>`;

        // ── Baris sub-tabel satuan ────────────────────────────────────────────
        const satuanRows = satuanList.map(s => {
            const defaultBadge = s.is_default
                ? `<span class="ml-1 text-xs font-semibold px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded">Utama</span>`
                : '';
            const barcodeCell  = s.barcode
                ? `<span class="font-mono">${esc(s.barcode)}</span>`
                : `<span class="text-gray-300 dark:text-gray-600">—</span>`;
            return `
                <tr class="border-t border-gray-100 dark:border-gray-700/60 hover:bg-indigo-50/30 dark:hover:bg-indigo-950/10 transition-colors">
                    <td class="px-4 py-2">
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">${esc(s.nama_satuan)}</span>
                        ${defaultBadge}
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 text-center">
                        ${fmt(s.isi)} ${esc(p.satuan_dasar)}
                    </td>
                    <td class="px-4 py-2 text-sm font-mono text-gray-700 dark:text-gray-300 text-right">
                        Rp ${fmt(s.harga_beli)}
                    </td>
                    <td class="px-4 py-2 text-sm font-mono text-green-700 dark:text-green-400 font-semibold text-right">
                        Rp ${fmt(s.harga_jual)}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                        ${barcodeCell}
                    </td>
                </tr>`;
        }).join('');

        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">${esc(p.kode_produk)}</td>
            <td class="px-4 py-3">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${esc(p.nama_produk)}</p>
                ${p.deskripsi ? `<p class="text-xs text-gray-400 dark:text-gray-500 truncate max-w-xs">${esc(p.deskripsi)}</p>` : ''}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                ${p.kategori ? esc(p.kategori.nama) : '<span class="text-gray-300 dark:text-gray-600">—</span>'}
            </td>
            <td class="px-4 py-3 text-center">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
                    ${esc(p.satuan_dasar)}
                </span>
            </td>
            <td class="px-4 py-3 text-right">
                <span class="text-sm font-mono font-semibold ${stokKritis ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100'}">
                    ${fmt(p.qty_stok)}
                </span>
                ${stokKritis ? `<span class="block text-xs text-red-500 dark:text-red-400">↓ min ${fmt(p.stok_minimum)}</span>` : ''}
            </td>
            <td class="px-4 py-3 text-center">${statusBadge}</td>
            <td class="px-4 py-3 text-right">
                <div class="inline-flex items-center gap-1">
                    <a href="/gudang/produk/${p.id}/edit"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                    <button onclick="toggleSatuan(${p.id})"
                        title="Lihat daftar satuan"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 hover:text-indigo-700 dark:hover:text-indigo-300 rounded-lg transition-colors">
                        <svg id="chevron-${p.id}" class="w-3.5 h-3.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Satuan (${satuanList.length})
                    </button>
                    <button onclick="bukaModalPenjualan(${p.id})"
                        title="Riwayat penjualan per bulan"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Penjualan
                    </button>
                    <button onclick="bukaModalHapus(${p.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus
                    </button>
                </div>
            </td>
        </tr>
        <tr id="satuan-row-${p.id}" class="hidden">
            <td colspan="7" class="p-0 bg-indigo-50/40 dark:bg-indigo-950/20">
                <div class="mx-6 my-3 rounded-xl border border-indigo-100 dark:border-indigo-900/40 overflow-hidden shadow-sm">
                    <div class="px-4 py-2 bg-indigo-50 dark:bg-indigo-950/40 border-b border-indigo-100 dark:border-indigo-900/40 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                            Daftar Satuan — ${esc(p.nama_produk)}
                        </span>
                        <a href="/gudang/produk/${p.id}/edit#satuan-section"
                            class="ml-auto text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                            Kelola Satuan →
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">Satuan</th>
                                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center w-28">Isi</th>
                                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right w-36">Harga Beli</th>
                                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right w-36">Harga Jual</th>
                                    <th class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Barcode</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700/60">
                                ${satuanRows || '<tr><td colspan="5" class="px-4 py-4 text-center text-xs text-gray-400">Belum ada satuan.</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>
            </td>
        </tr>`;
    }).join('');

    renderPagination(pagination);
}

// ── Pagination ────────────────────────────────────────────────────────────────
function renderPagination(pg) {
    if (!pg || pg.total <= pg.per_page) {
        const pw = document.getElementById('pagination-wrapper');
        pw.classList.add('hidden');
        pw.classList.remove('flex');
        return;
    }

    const pw = document.getElementById('pagination-wrapper');
    pw.classList.remove('hidden');
    pw.classList.add('flex');
    document.getElementById('pagination-info').textContent =
        `Menampilkan ${Math.min((pg.current_page - 1) * pg.per_page + 1, pg.total)}–${Math.min(pg.current_page * pg.per_page, pg.total)} dari ${pg.total} produk`;

    const container = document.getElementById('pagination-btns');
    const pages = [];
    for (let i = 1; i <= pg.last_page; i++) {
        if (i === 1 || i === pg.last_page || Math.abs(i - pg.current_page) <= 1) {
            pages.push(i);
        } else if (pages[pages.length - 1] !== '...') {
            pages.push('...');
        }
    }

    container.innerHTML = pages.map(p =>
        p === '...'
            ? `<span class="px-2 py-1 text-xs text-gray-400">…</span>`
            : `<button onclick="loadProduk(${p})"
                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${p === pg.current_page
                    ? 'bg-indigo-600 text-white'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'}">${p}</button>`
    ).join('');
}

// ── Modal Hapus ───────────────────────────────────────────────────────────────
function bukaModalHapus(id) {
    const p = produkList.find(p => p.id === id);
    if (!p) return;

    deleteTarget = p;

    const softEl = document.getElementById('hapus-soft');
    const hardEl = document.getElementById('hapus-hard');
    const btnOk  = document.getElementById('btn-hapus-ok');

    if (p.has_transaksi) {
        document.getElementById('hapus-soft-pesan').innerHTML =
            `Produk <strong class="text-gray-900 dark:text-gray-100">"${esc(p.nama_produk)}"</strong> ` +
            `akan dinonaktifkan dan tidak lagi muncul di daftar aktif.`;
        softEl.classList.remove('hidden');
        hardEl.classList.add('hidden');
        // Tombol amber untuk soft delete
        btnOk.className = btnOk.className.replace(/bg-\w+-\d+/g, '');
        btnOk.classList.add('bg-amber-600', 'hover:bg-amber-700', 'focus:ring-amber-500');
        document.getElementById('lbl-hapus').textContent = 'Ya, Nonaktifkan';
    } else {
        document.getElementById('hapus-hard-pesan').innerHTML =
            `Produk <strong class="text-gray-900 dark:text-gray-100">"${esc(p.nama_produk)}"</strong> ` +
            `akan dihapus permanen beserta semua data satuannya.`;
        softEl.classList.add('hidden');
        hardEl.classList.remove('hidden');
        // Tombol merah untuk hard delete
        btnOk.className = btnOk.className.replace(/bg-\w+-\d+/g, '');
        btnOk.classList.add('bg-red-600', 'hover:bg-red-700', 'focus:ring-red-500');
        document.getElementById('lbl-hapus').textContent = 'Ya, Hapus Permanen';
    }

    tampilkanModal('modal-hapus');
}

async function eksekusiHapus() {
    if (!deleteTarget) return;

    const btn = document.getElementById('btn-hapus-ok');
    btn.disabled = true;
    document.getElementById('spinner-hapus').classList.remove('hidden');

    try {
        const { data } = await axios.delete(`/api/gudang/produk/${deleteTarget.id}`);
        const isSoft = data.message?.includes('dinonaktifkan');
        showToast(
            isSoft ? 'warning' : 'success',
            isSoft ? 'Produk Dinonaktifkan' : 'Produk Dihapus',
            `"${deleteTarget.nama_produk}" ${isSoft ? 'dinonaktifkan' : 'berhasil dihapus'}.`
        );
        tutupModalHapus();
        await loadProduk(currentPage);
    } catch (err) {
        showToast('error', 'Gagal', err.response?.data?.message || 'Terjadi kesalahan');
        tutupModalHapus();
    } finally {
        btn.disabled = false;
        document.getElementById('spinner-hapus').classList.add('hidden');
    }
}

function tutupModalHapus() {
    sembunyikanModal('modal-hapus');
    deleteTarget = null;
    document.getElementById('lbl-hapus').textContent = 'Konfirmasi';
}

// ── Utilitas Modal ────────────────────────────────────────────────────────────
function tampilkanModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
}
function sembunyikanModal(id) {
    const el = document.getElementById(id);
    el.classList.add('hidden');
    el.classList.remove('flex');
}

// ── Toggle detail satuan ──────────────────────────────────────────────────────
function toggleSatuan(id) {
    const row     = document.getElementById('satuan-row-' + id);
    const chevron = document.getElementById('chevron-' + id);
    if (!row) return;

    const isHidden = row.classList.toggle('hidden');
    // Rotasi chevron: → (tertutup) vs ↓ (terbuka)
    chevron.style.transform = isHidden ? '' : 'rotate(90deg)';
}

// ── Filter dengan debounce ────────────────────────────────────────────────────
function filterDebounce() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => loadProduk(1), 400);
}

// ── Utilitas umum ─────────────────────────────────────────────────────────────
function fmt(n) { return new Intl.NumberFormat('id-ID').format(n ?? 0); }
function esc(str) {
    if (str == null) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    loadKategoriFilter();
    loadProduk(1);
});
</script>
@endpush
