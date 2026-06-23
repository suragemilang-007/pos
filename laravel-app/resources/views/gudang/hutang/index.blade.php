@extends('layouts.app')

@section('title', 'Hutang Dagang')
@section('page-title', 'Hutang Dagang')

@section('content')
<div class="space-y-5">

    {{-- ── Kartu Ringkasan ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- Total Hutang --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Hutang</p>
                    <p id="stat-total" class="text-xl font-bold text-red-600 dark:text-red-400 mt-0.5">—</p>
                </div>
            </div>
        </div>

        {{-- PO Jatuh Tempo < 3 Hari --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-amber-200 dark:border-amber-800 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">PO Jatuh Tempo &lt; 3 Hari</p>
                    <p id="stat-segera" class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-0.5">—</p>
                </div>
            </div>
        </div>

        {{-- Jumlah Supplier --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier Berutang</p>
                    <p id="stat-supplier" class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Filter ───────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

            {{-- Dropdown Supplier --}}
            <div>
                <select id="filter-supplier"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="loadData(1)">
                    <option value="">Semua Supplier</option>
                </select>
            </div>

            {{-- Status Pembayaran --}}
            <div>
                <select id="filter-status"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="loadData(1)">
                    <option value="belum_lunas,sebagian">Belum Lunas</option>
                    <option value="lunas">Sudah Lunas</option>
                    <option value="">Semua Status</option>
                </select>
            </div>

            {{-- Dari tanggal --}}
            <div class="relative">
                <label class="absolute -top-2 left-2.5 text-[10px] font-medium text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 px-1">
                    Jatuh Tempo Dari
                </label>
                <input id="filter-dari" type="date"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="applyClientFilter()">
            </div>

            {{-- Sampai tanggal --}}
            <div class="relative">
                <label class="absolute -top-2 left-2.5 text-[10px] font-medium text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 px-1">
                    Jatuh Tempo Sampai
                </label>
                <input id="filter-sampai" type="date"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="applyClientFilter()">
            </div>

        </div>
    </div>

    {{-- ── Tabel Daftar Hutang ───────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Skeleton --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 7; $i++)
            <div class="animate-pulse flex gap-3">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/8"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/8"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/8"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/8"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-24"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-16"></div>
                <div class="h-7 bg-gray-200 dark:bg-gray-700 rounded-lg w-16 ml-auto"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left min-w-[900px]">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No. PO</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Total</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Sudah Dibayar</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Sisa Hutang</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-data" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            <div id="tbl-empty" class="hidden px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Tidak ada hutang dagang</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Semua PO sudah lunas atau tidak ada data sesuai filter</p>
            </div>
        </div>

        {{-- Pagination --}}
        <div id="pagination" class="hidden px-4 py-3 border-t border-gray-200 dark:border-gray-700 items-center justify-between">
            <p id="pagination-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div id="pagination-btns" class="flex gap-1"></div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     MODAL BAYAR HUTANG
════════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-bayar"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        {{-- Header --}}
        <div class="flex items-start justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Catat Pembayaran Hutang</h3>
                <p id="modal-header-info" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-mono">—</p>
            </div>
            <button onclick="tutupModal()"
                class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors shrink-0 ml-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-5 space-y-4 max-h-[80vh] overflow-y-auto">

            {{-- Info PO: 4 kartu kecil --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Total PO</p>
                    <p id="info-total" class="text-sm font-bold text-gray-900 dark:text-gray-100">—</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                    <p class="text-[10px] font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider mb-0.5">Sudah Bayar</p>
                    <p id="info-bayar" class="text-sm font-bold text-green-700 dark:text-green-300">—</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                    <p class="text-[10px] font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider mb-0.5">Sisa Hutang</p>
                    <p id="info-sisa" class="text-sm font-bold text-red-700 dark:text-red-300">—</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                    <p class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-0.5">Jatuh Tempo</p>
                    <p id="info-jt" class="text-xs font-semibold text-amber-700 dark:text-amber-300">—</p>
                </div>
            </div>

            {{-- Riwayat pembayaran (tabel kecil) --}}
            <div id="riwayat-section" class="hidden">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    Riwayat Pembayaran Sebelumnya
                </p>
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-gray-500 dark:text-gray-400">Tanggal</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-500 dark:text-gray-400">Metode</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-500 dark:text-gray-400">Jumlah</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-500 dark:text-gray-400">Catatan</th>
                            </tr>
                        </thead>
                        <tbody id="riwayat-tbody" class="divide-y divide-gray-100 dark:divide-gray-700 max-h-28 overflow-y-auto"></tbody>
                    </table>
                </div>
            </div>

            {{-- Alert error --}}
            <div id="alert-error" class="hidden p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p id="alert-error-msg" class="text-sm text-red-700 dark:text-red-400"></p>
            </div>

            <input type="hidden" id="bayar-po-id">
            <input type="hidden" id="bayar-sisa">

            {{-- Form pembayaran --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Jumlah --}}
                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Jumlah Bayar (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">Rp</span>
                        <input id="input-jumlah" type="number" min="1" step="1" placeholder="0"
                            class="block w-full pl-9 pr-3 py-2 text-sm font-mono bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                            oninput="updateSisaPreview()">
                    </div>
                    <div class="flex items-center justify-between">
                        <p id="preview-sisa" class="text-xs text-gray-500 dark:text-gray-400"></p>
                        <button type="button" onclick="lunasiPenuh()"
                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                            Lunasi penuh
                        </button>
                    </div>
                </div>

                {{-- Metode --}}
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Metode Pembayaran <span class="text-red-500">*</span>
                    </label>
                    <select id="input-metode"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                        <option value="cash">Cash / Tunai</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="cek">Cek</option>
                        <option value="giro">Giro</option>
                    </select>
                </div>

                {{-- Tanggal Bayar --}}
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tanggal Bayar <span class="text-red-500">*</span>
                    </label>
                    <input id="input-tgl-bayar" type="date"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>

                {{-- Catatan --}}
                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Catatan <span class="text-xs font-normal text-gray-400">(opsional)</span>
                    </label>
                    <input id="input-catatan" type="text" placeholder="No. ref transfer, nama bank, dll."
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModal()"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-bayar" onclick="submitBayar()"
                class="inline-flex items-center gap-2 px-5 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-60 disabled:cursor-not-allowed">
                <svg id="btn-spinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Catat Pembayaran
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
'use strict';

// ─────────────────────────────────────────────────────────────────────────────
// STATE
// ─────────────────────────────────────────────────────────────────────────────
let allData    = [];   // semua PO dari server (sebelum filter tanggal client-side)
let filtered   = [];   // setelah filter tanggal diterapkan
let currentPage = 1;
let selectedPO  = null;  // PO yang sedang di-modal

// ─────────────────────────────────────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadSupplierFilter();
    loadData(1);
    // Escape tutup modal
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') tutupModal();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// LOAD SUPPLIER DROPDOWN FILTER
// ─────────────────────────────────────────────────────────────────────────────
async function loadSupplierFilter() {
    try {
        const { data } = await axios.get('/api/gudang/supplier?limit=200&status=aktif');
        const sel = document.getElementById('filter-supplier');
        (data.data || []).forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.nama_supplier;
            sel.appendChild(opt);
        });
    } catch (_) {}
}

// ─────────────────────────────────────────────────────────────────────────────
// LOAD DATA DARI API
// ─────────────────────────────────────────────────────────────────────────────
async function loadData(page = 1) {
    currentPage = page;
    showSkeleton(true);

    const supplierId = document.getElementById('filter-supplier').value;
    const status     = document.getElementById('filter-status').value;

    const params = new URLSearchParams({ page, limit: 50 });
    if (supplierId) params.set('supplier_id', supplierId);
    if (status)     params.set('status_pembayaran', status);

    try {
        const { data } = await axios.get(`/api/gudang/hutang?${params}`);
        allData = data.data ?? [];
        applyClientFilter();   // lanjut ke filter tanggal + render
    } catch (err) {
        console.error('[hutang] Gagal load:', err.message);
        showEmpty();
    } finally {
        showSkeleton(false);
    }
}

// Filter tanggal jatuh tempo dilakukan client-side karena data hutang
// biasanya kecil (< 200 PO) dan tidak perlu round-trip ke server lagi.
function applyClientFilter() {
    const dari   = document.getElementById('filter-dari').value;
    const sampai = document.getElementById('filter-sampai').value;

    filtered = allData.filter(po => {
        if (!po.tanggal_jatuh_tempo) return true;   // PO cash tidak punya JT
        if (dari   && po.tanggal_jatuh_tempo < dari)   return false;
        if (sampai && po.tanggal_jatuh_tempo > sampai) return false;
        return true;
    });

    updateStat(filtered);
    renderTable(filtered);
}

// ─────────────────────────────────────────────────────────────────────────────
// STAT CARDS
// ─────────────────────────────────────────────────────────────────────────────
function updateStat(data) {
    const totalHutang    = data.reduce((s, d) => s + parseFloat(d.sisa_hutang || 0), 0);
    const segera         = data.filter(d =>
        d.hari_tersisa !== null && d.hari_tersisa !== undefined && d.hari_tersisa >= 0 && d.hari_tersisa < 3
    ).length;
    const suppliers      = new Set(data.map(d => d.supplier?.id).filter(Boolean)).size;

    document.getElementById('stat-total').textContent    = fmt(totalHutang);
    document.getElementById('stat-segera').textContent   = segera + ' PO';
    document.getElementById('stat-supplier').textContent = suppliers + ' supplier';
}

// ─────────────────────────────────────────────────────────────────────────────
// RENDER TABEL
// ─────────────────────────────────────────────────────────────────────────────
function renderTable(data) {
    const tbody = document.getElementById('tbody-data');

    if (!data.length) {
        showEmpty();
        return;
    }

    document.getElementById('tbl-empty').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');
    const pgEl = document.getElementById('pagination');
    pgEl.classList.remove('hidden');
    pgEl.classList.add('flex');

    tbody.innerHTML = data.map(po => buildRow(po)).join('');

    // Info pagination sederhana (data sudah satu halaman besar dari server)
    document.getElementById('pagination-info').textContent =
        `Menampilkan ${data.length} dari ${filtered.length} entri`;
    document.getElementById('pagination-btns').innerHTML = '';
}

function buildRow(po) {
    // ── Status badge ─────────────────────────────────────────────────────────
    const STATUS_MAP = {
        lunas:       { label: 'Lunas',        cls: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' },
        sebagian:    { label: 'Sebagian',      cls: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' },
        belum_lunas: { label: 'Belum Lunas',   cls: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' },
    };
    const st = STATUS_MAP[po.status_pembayaran] ?? { label: po.status_pembayaran, cls: 'bg-gray-100 text-gray-600' };

    // ── Jatuh tempo + badge hari_tersisa ─────────────────────────────────────
    let jtHtml = '<span class="text-gray-400 dark:text-gray-500 text-xs">—</span>';
    if (po.tanggal_jatuh_tempo) {
        const tglFmt = new Date(po.tanggal_jatuh_tempo + 'T00:00:00')
            .toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        const h = po.hari_tersisa;

        let badge = '';
        if (h === null || h === undefined) {
            badge = '';
        } else if (h <= 0) {
            // Sudah jatuh tempo atau hari ini
            badge = `<span class="ml-1 inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 uppercase tracking-wide">
                        JATUH TEMPO
                     </span>`;
        } else if (h <= 3) {
            badge = `<span class="ml-1 inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400">
                        H-${h}
                     </span>`;
        } else if (h <= 7) {
            badge = `<span class="ml-1 inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400">
                        H-${h}
                     </span>`;
        }

        jtHtml = `<div class="flex items-center flex-wrap gap-0.5">
                    <span class="text-sm text-gray-700 dark:text-gray-300">${tglFmt}</span>
                    ${badge}
                  </div>`;
    }

    // ── Sudah dibayar = total - sisa ─────────────────────────────────────────
    const sudahDibayar = parseFloat(po.total || 0) - parseFloat(po.sisa_hutang || 0);

    const canPay = po.status_pembayaran !== 'lunas';

    return `
    <tr id="row-${po.id}" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
        <td class="px-4 py-3 whitespace-nowrap">
            <a href="/gudang/pembelian/${po.id}"
                class="text-sm font-mono font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                ${esc(po.no_pembelian ?? '—')}
            </a>
            <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                ${po.tanggal_pembelian
                    ? new Date(po.tanggal_pembelian + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                    : '—'}
            </div>
        </td>
        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
            ${esc(po.supplier?.nama_supplier ?? '—')}
        </td>
        <td class="px-4 py-3 text-sm text-right font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">
            ${fmt(po.total)}
        </td>
        <td class="px-4 py-3 text-right whitespace-nowrap">
            <span class="text-sm font-mono text-green-600 dark:text-green-400">${fmt(sudahDibayar)}</span>
        </td>
        <td id="sisa-${po.id}" class="px-4 py-3 text-right whitespace-nowrap">
            <span class="text-sm font-mono font-semibold ${parseFloat(po.sisa_hutang) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400 dark:text-gray-500'}">
                ${fmt(po.sisa_hutang)}
            </span>
        </td>
        <td class="px-4 py-3">${jtHtml}</td>
        <td class="px-4 py-3 text-center">
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${st.cls}">
                ${st.label}
            </span>
        </td>
        <td class="px-4 py-3 text-right whitespace-nowrap">
            ${canPay
                ? `<button onclick="bukaBayar(${po.id})"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500">
                       <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 6v1m0 4v1m-9-5a9 9 0 1118 0 9 9 0 01-18 0z"/>
                       </svg>
                       Bayar
                   </button>`
                : `<span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Lunas</span>`
            }
        </td>
    </tr>`;
}

// ─────────────────────────────────────────────────────────────────────────────
// MODAL — BUKA
// ─────────────────────────────────────────────────────────────────────────────
async function bukaBayar(poId) {
    selectedPO = allData.find(d => d.id === poId);
    if (!selectedPO) return;

    resetModal();

    // Isi header info
    document.getElementById('modal-header-info').textContent =
        `${selectedPO.no_pembelian ?? '—'} · ${selectedPO.supplier?.nama_supplier ?? '—'}`;

    const sudahDibayar = parseFloat(selectedPO.total || 0) - parseFloat(selectedPO.sisa_hutang || 0);
    document.getElementById('info-total').textContent = fmt(selectedPO.total);
    document.getElementById('info-bayar').textContent = fmt(sudahDibayar);
    document.getElementById('info-sisa').textContent  = fmt(selectedPO.sisa_hutang);
    document.getElementById('info-jt').textContent    = selectedPO.tanggal_jatuh_tempo
        ? new Date(selectedPO.tanggal_jatuh_tempo + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
        : '— (Cash)';

    document.getElementById('bayar-po-id').value = poId;
    document.getElementById('bayar-sisa').value  = selectedPO.sisa_hutang;

    // Set jumlah default = lunasi penuh
    document.getElementById('input-jumlah').value = parseFloat(selectedPO.sisa_hutang).toFixed(0);
    updateSisaPreview();
    document.getElementById('input-tgl-bayar').value = new Date().toLocaleDateString('sv');

    // Load riwayat pembayaran dari endpoint detail PO
    await loadRiwayat(poId);

    tampilModal();
    setTimeout(() => document.getElementById('input-jumlah').focus(), 120);
}

async function loadRiwayat(poId) {
    try {
        const { data } = await axios.get(`/api/gudang/pembelian/${poId}`);
        const riwayat = data.pembayaran ?? [];

        if (!riwayat.length) return;

        const tbody = document.getElementById('riwayat-tbody');
        tbody.innerHTML = riwayat.map(p => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20">
                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400">${p.tanggal_bayar ?? '—'}</td>
                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400 capitalize">${esc(p.metode ?? '—')}</td>
                <td class="px-3 py-1.5 text-right font-mono font-medium text-green-600 dark:text-green-400">${fmt(p.jumlah)}</td>
                <td class="px-3 py-1.5 text-gray-400 dark:text-gray-500 max-w-[100px] truncate" title="${esc(p.catatan ?? '')}">${esc(p.catatan ?? '—')}</td>
            </tr>`).join('');

        document.getElementById('riwayat-section').classList.remove('hidden');
    } catch (_) {}
}

function tampilModal() {
    const m = document.getElementById('modal-bayar');
    m.classList.remove('hidden');
    m.classList.add('flex');
}

function tutupModal() {
    const m = document.getElementById('modal-bayar');
    m.classList.add('hidden');
    m.classList.remove('flex');
    selectedPO = null;
}

function resetModal() {
    document.getElementById('input-jumlah').value  = '';
    document.getElementById('input-metode').value  = 'cash';
    document.getElementById('input-tgl-bayar').value = '';
    document.getElementById('input-catatan').value = '';
    document.getElementById('preview-sisa').textContent = '';
    document.getElementById('alert-error').classList.add('hidden');
    document.getElementById('riwayat-section').classList.add('hidden');
    document.getElementById('riwayat-tbody').innerHTML = '';
}

// ─────────────────────────────────────────────────────────────────────────────
// PREVIEW SISA
// ─────────────────────────────────────────────────────────────────────────────
function lunasiPenuh() {
    if (!selectedPO) return;
    document.getElementById('input-jumlah').value = parseFloat(selectedPO.sisa_hutang).toFixed(0);
    updateSisaPreview();
    document.getElementById('input-jumlah').focus();
}

function updateSisaPreview() {
    if (!selectedPO) return;
    const jumlah = parseFloat(document.getElementById('input-jumlah').value) || 0;
    const sisa   = parseFloat(selectedPO.sisa_hutang);
    const sisaBaru = sisa - jumlah;
    const preview  = document.getElementById('preview-sisa');

    if (jumlah <= 0) {
        preview.textContent = '';
        preview.className   = 'text-xs text-gray-500 dark:text-gray-400';
        return;
    }
    if (jumlah > sisa) {
        preview.textContent = '⚠ Jumlah melebihi sisa hutang';
        preview.className   = 'text-xs font-medium text-red-600 dark:text-red-400';
    } else if (sisaBaru === 0) {
        preview.textContent = '✓ Hutang akan LUNAS';
        preview.className   = 'text-xs font-medium text-green-600 dark:text-green-400';
    } else {
        preview.textContent = `Sisa setelah bayar: ${fmt(sisaBaru)}`;
        preview.className   = 'text-xs text-gray-500 dark:text-gray-400';
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// SUBMIT BAYAR
// ─────────────────────────────────────────────────────────────────────────────
async function submitBayar() {
    const poId     = parseInt(document.getElementById('bayar-po-id').value);
    const jumlah   = parseFloat(document.getElementById('input-jumlah').value);
    const metode   = document.getElementById('input-metode').value;
    const tglBayar = document.getElementById('input-tgl-bayar').value;
    const catatan  = document.getElementById('input-catatan').value.trim();

    document.getElementById('alert-error').classList.add('hidden');

    // Validasi client-side sebelum kirim ke server
    if (!jumlah || jumlah <= 0) return showError('Jumlah bayar harus lebih dari 0.');
    if (!metode)                return showError('Pilih metode pembayaran.');
    if (!tglBayar)              return showError('Tanggal bayar wajib diisi.');
    if (selectedPO && jumlah > parseFloat(selectedPO.sisa_hutang)) {
        return showError(`Jumlah (${fmt(jumlah)}) melebihi sisa hutang (${fmt(selectedPO.sisa_hutang)}).`);
    }

    setBtnLoading(true);

    try {
        const res = await axios.post(`/api/gudang/pembelian/${poId}/bayar`, {
            jumlah,
            metode,
            tanggal_bayar: tglBayar,
            catatan:       catatan || null,
        });

        // HTTP 200 (sinkron) — bukan 202
        const sisaBaru           = res.data.sisa_hutang_baru ?? 0;
        const statusBaru         = res.data.status_pembayaran_baru ?? '';
        const isLunas            = sisaBaru <= 0 || statusBaru === 'lunas';

        tutupModal();

        if (isLunas) {
            // Hapus baris dari tabel — hutang sudah selesai
            const row = document.getElementById(`row-${poId}`);
            if (row) {
                row.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                setTimeout(() => {
                    row.remove();
                    // Hapus juga dari allData dan filtered agar stat terupdate
                    allData  = allData.filter(d => d.id !== poId);
                    filtered = filtered.filter(d => d.id !== poId);
                    updateStat(filtered);
                    // Tampilkan empty state jika tabel jadi kosong
                    if (!document.getElementById('tbody-data').children.length) showEmpty();
                }, 320);
            }
            showToast('success', 'Hutang LUNAS', `PO ${selectedPO?.no_pembelian ?? poId} telah lunas!`);
        } else {
            // Update sisa hutang inline — baris tetap di tabel dengan nilai baru
            const sisaCell = document.getElementById(`sisa-${poId}`);
            if (sisaCell) {
                sisaCell.innerHTML =
                    `<span class="text-sm font-mono font-semibold text-red-600 dark:text-red-400">${fmt(sisaBaru)}</span>`;
            }
            // Update allData & filtered agar modal berikutnya punya data segar
            const idx = allData.findIndex(d => d.id === poId);
            if (idx >= 0) allData[idx].sisa_hutang = sisaBaru;
            updateStat(filtered);
            showToast('success', 'Pembayaran Dicatat', res.data.message ?? 'Cicilan berhasil dicatat.');
        }

    } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal mencatat pembayaran.';
        showError(msg);
    } finally {
        setBtnLoading(false);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// UI HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function showSkeleton(show) {
    document.getElementById('tbl-skeleton').classList.toggle('hidden', !show);
    if (show) {
        document.getElementById('tbl-wrapper').classList.add('hidden');
        hidePagination();
    }
}

function showEmpty() {
    document.getElementById('tbl-wrapper').classList.remove('hidden');
    document.getElementById('tbody-data').innerHTML = '';
    document.getElementById('tbl-empty').classList.remove('hidden');
    hidePagination();
}

function hidePagination() {
    const el = document.getElementById('pagination');
    el.classList.add('hidden');
    el.classList.remove('flex');
}

function showError(msg) {
    const el = document.getElementById('alert-error');
    document.getElementById('alert-error-msg').textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function setBtnLoading(on) {
    document.getElementById('btn-bayar').disabled = on;
    document.getElementById('btn-spinner').classList.toggle('hidden', !on);
}

// showToast tersedia global dari layouts/app.blade.php — tidak perlu didefinisikan ulang

// ─────────────────────────────────────────────────────────────────────────────
// UTILS
// ─────────────────────────────────────────────────────────────────────────────
function fmt(n) {
    return 'Rp ' + parseFloat(n || 0).toLocaleString('id-ID', { minimumFractionDigits: 0 });
}

function esc(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// Klik backdrop tutup modal
document.getElementById('modal-bayar')?.addEventListener('click', function (e) {
    if (e.target === this) tutupModal();
});
</script>
@endpush
