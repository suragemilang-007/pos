@extends('layouts.app')

@section('title', 'Hutang Dagang')
@section('page-title', 'Hutang Dagang')

@section('content')
<div class="space-y-5">

    {{-- ── Ringkasan Hutang ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Hutang</p>
            <p id="stat-total" class="text-2xl font-bold text-gray-900 dark:text-gray-100">—</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-800 shadow-sm p-4">
            <p class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Jatuh Tempo / Terlewat</p>
            <p id="stat-jatuh-tempo" class="text-2xl font-bold text-red-600 dark:text-red-400">—</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-yellow-200 dark:border-yellow-800 shadow-sm p-4">
            <p class="text-xs font-semibold text-yellow-600 uppercase tracking-wider mb-1">Jatuh Tempo ≤ 3 Hari</p>
            <p id="stat-segera" class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">—</p>
        </div>
    </div>

    {{-- ── Filter ───────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">

            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input id="filter-search" type="text" placeholder="Cari no. PO atau nama supplier..."
                    class="block w-full pl-9 pr-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    oninput="filterDebounce()">
            </div>

            <select id="filter-status"
                class="sm:w-44 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadData(1)">
                <option value="belum_lunas,sebagian">Belum Lunas</option>
                <option value="lunas">Sudah Lunas</option>
                <option value="">Semua</option>
            </select>
        </div>
    </div>

    {{-- ── Tabel Hutang ─────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 6; $i++)
            <div class="animate-pulse flex gap-4">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/8"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-20 ml-auto"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No. PO</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Total PO</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Sisa Hutang</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-data" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            <div id="tbl-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada hutang dagang</p>
            </div>
        </div>

        <div id="pagination" class="hidden px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p id="pagination-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div id="pagination-btns" class="flex gap-1"></div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     MODAL — Bayar Hutang
════════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-bayar" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="tutupModal()"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">

            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Catat Pembayaran Hutang</h3>
                    <p id="modal-no-po" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-mono">—</p>
                </div>
                <button onclick="tutupModal()" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-4">

                {{-- Info hutang --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Total PO</p>
                        <p id="info-total-po" class="text-sm font-semibold text-gray-900 dark:text-gray-100">—</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                        <p class="text-xs text-red-600 dark:text-red-400 mb-0.5">Sisa Hutang</p>
                        <p id="info-sisa-hutang" class="text-sm font-semibold text-red-700 dark:text-red-300">—</p>
                    </div>
                </div>

                {{-- Alert error --}}
                <div id="alert-error" class="hidden p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p id="alert-error-msg" class="text-sm text-red-700 dark:text-red-400"></p>
                </div>

                <input type="hidden" id="bayar-pembelian-id">

                {{-- Jumlah --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Jumlah Bayar (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input id="input-jumlah" type="number" min="1" placeholder="0"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                        oninput="updateSisaPreview()">
                    <p id="preview-sisa" class="text-xs text-gray-500 dark:text-gray-400 mt-1"></p>
                </div>

                {{-- Metode --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
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

                {{-- Tanggal bayar --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Tanggal Bayar <span class="text-red-500">*</span>
                    </label>
                    <input id="input-tanggal-bayar" type="date"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Catatan <span class="text-xs font-normal text-gray-400">(opsional)</span>
                    </label>
                    <input id="input-catatan" type="text" placeholder="No. referensi, dll."
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>

                {{-- Riwayat Pembayaran --}}
                <div id="riwayat-section" class="hidden">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Riwayat Pembayaran</p>
                    <div id="riwayat-list" class="space-y-1.5 max-h-32 overflow-y-auto"></div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Batal
                    </button>
                    <button onclick="submitBayar()" id="btn-bayar"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg id="btn-spinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Catat Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ─────────────────────────────────────────────────────────────────────────────
// STATE
// ─────────────────────────────────────────────────────────────────────────────
let currentPage = 1;
let filterTimer = null;
let selectedPO = null; // { id, no_pembelian, total, sisa_hutang, ... }
let allData = [];

const fmt = val => 'Rp ' + parseFloat(val || 0).toLocaleString('id-ID', { minimumFractionDigits: 0 });

// ─────────────────────────────────────────────────────────────────────────────
// LOAD DATA
// ─────────────────────────────────────────────────────────────────────────────
async function loadData(page = 1) {
    currentPage = page;
    showSkeleton(true);

    const token  = localStorage.getItem('auth_token');
    const status = document.getElementById('filter-status').value;
    const params = new URLSearchParams({ page, limit: 20 });
    if (status) params.set('status_pembayaran', status);

    try {
        const res = await axios.get(`/api/gudang/hutang?${params}`, {
            headers: { Authorization: `Bearer ${token}` },
        });

        allData = res.data.data ?? [];
        updateStat(allData);
        renderTable(allData);
        renderPagination(res.data.pagination ?? {});
    } catch (err) {
        console.error('Gagal memuat hutang:', err);
        showEmpty();
    } finally {
        showSkeleton(false);
    }
}

function filterDebounce() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => {
        const q = document.getElementById('filter-search').value.toLowerCase();
        const filtered = allData.filter(d =>
            (d.no_pembelian ?? '').toLowerCase().includes(q) ||
            (d.supplier?.nama_supplier ?? '').toLowerCase().includes(q)
        );
        renderTable(filtered);
    }, 300);
}

// ─────────────────────────────────────────────────────────────────────────────
// STAT
// ─────────────────────────────────────────────────────────────────────────────
function updateStat(data) {
    const totalHutang = data.reduce((s, d) => s + parseFloat(d.sisa_hutang || 0), 0);
    const jatuhTempo  = data.filter(d => parseFloat(d.sisa_hutang) > 0 && d.hari_tersisa !== null && d.hari_tersisa <= 0).length;
    const segera      = data.filter(d => parseFloat(d.sisa_hutang) > 0 && d.hari_tersisa !== null && d.hari_tersisa > 0 && d.hari_tersisa <= 3).length;

    document.getElementById('stat-total').textContent = fmt(totalHutang);
    document.getElementById('stat-jatuh-tempo').textContent = jatuhTempo + ' PO';
    document.getElementById('stat-segera').textContent = segera + ' PO';
}

// ─────────────────────────────────────────────────────────────────────────────
// RENDER
// ─────────────────────────────────────────────────────────────────────────────
function renderTable(data) {
    const tbody = document.getElementById('tbody-data');

    if (!data.length) {
        showEmpty();
        return;
    }

    document.getElementById('tbl-empty').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');
    document.getElementById('pagination').classList.remove('hidden');

    tbody.innerHTML = data.map(po => {
        const statusMap = {
            lunas:        { label: 'Lunas',        cls: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' },
            sebagian:     { label: 'Sebagian',      cls: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' },
            belum_lunas:  { label: 'Belum Lunas',   cls: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' },
        };
        const { label: statusLabel, cls: statusCls } = statusMap[po.status_pembayaran] ?? { label: po.status_pembayaran, cls: 'bg-gray-100 text-gray-700' };

        let jatuhTempoHtml = '—';
        if (po.tanggal_jatuh_tempo) {
            const tgl = new Date(po.tanggal_jatuh_tempo).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const hari = po.hari_tersisa;
            let urgencyCls = 'text-gray-500 dark:text-gray-400';
            let urgencyLabel = '';
            if (hari !== null && hari !== undefined) {
                if (hari < 0) {
                    urgencyCls = 'text-red-600 dark:text-red-400 font-semibold';
                    urgencyLabel = `<span class="text-xs">(${Math.abs(hari)} hari terlewat)</span>`;
                } else if (hari <= 3) {
                    urgencyCls = 'text-yellow-600 dark:text-yellow-400 font-medium';
                    urgencyLabel = `<span class="text-xs">(${hari} hari lagi)</span>`;
                }
            }
            jatuhTempoHtml = `<div class="${urgencyCls}">${tgl} ${urgencyLabel}</div>`;
        }

        const canPay = po.status_pembayaran !== 'lunas';

        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3">
                <a href="/gudang/pembelian/${po.id}" class="text-sm font-mono font-medium text-indigo-600 dark:text-indigo-400 hover:underline">${po.no_pembelian ?? '—'}</a>
                <div class="text-xs text-gray-400">${new Date(po.tanggal_pembelian ?? '').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">${escapeHtml(po.supplier?.nama_supplier ?? '—')}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 text-right whitespace-nowrap">${fmt(po.total)}</td>
            <td class="px-4 py-3 text-sm font-semibold text-red-600 dark:text-red-400 text-right whitespace-nowrap">${fmt(po.sisa_hutang)}</td>
            <td class="px-4 py-3">${jatuhTempoHtml}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium ${statusCls}">${statusLabel}</span>
            </td>
            <td class="px-4 py-3 text-right">
                ${canPay ? `<button onclick="bukaBayar(${po.id})"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 6v1m0 4v1m-9-5a9 9 0 1118 0 9 9 0 01-18 0z"/>
                    </svg>
                    Bayar
                </button>` : `<span class="text-xs text-gray-400">Lunas</span>`}
            </td>
        </tr>`;
    }).join('');
}

function renderPagination(pg) {
    if (!pg.last_page) return;
    document.getElementById('pagination-info').textContent =
        `Halaman ${pg.current_page} dari ${pg.last_page} (${pg.total} data)`;

    let html = '';
    if (pg.current_page > 1) {
        html += `<button onclick="loadData(${pg.current_page - 1})"
            class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">← Prev</button>`;
    }
    if (pg.current_page < pg.last_page) {
        html += `<button onclick="loadData(${pg.current_page + 1})"
            class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Next →</button>`;
    }
    document.getElementById('pagination-btns').innerHTML = html;
}

function showSkeleton(show) {
    document.getElementById('tbl-skeleton').classList.toggle('hidden', !show);
    if (show) {
        document.getElementById('tbl-wrapper').classList.add('hidden');
        document.getElementById('pagination').classList.add('hidden');
    }
}

function showEmpty() {
    document.getElementById('tbl-wrapper').classList.remove('hidden');
    document.getElementById('tbody-data').innerHTML = '';
    document.getElementById('tbl-empty').classList.remove('hidden');
    document.getElementById('pagination').classList.add('hidden');
}

// ─────────────────────────────────────────────────────────────────────────────
// MODAL BAYAR
// ─────────────────────────────────────────────────────────────────────────────
async function bukaBayar(pembelianId) {
    selectedPO = allData.find(d => d.id === pembelianId);
    if (!selectedPO) return;

    resetModal();
    document.getElementById('bayar-pembelian-id').value = pembelianId;
    document.getElementById('modal-no-po').textContent = selectedPO.no_pembelian ?? '—';
    document.getElementById('info-total-po').textContent = fmt(selectedPO.total);
    document.getElementById('info-sisa-hutang').textContent = fmt(selectedPO.sisa_hutang);

    // Set default jumlah = sisa hutang (lunasi penuh)
    document.getElementById('input-jumlah').value = parseFloat(selectedPO.sisa_hutang).toFixed(0);
    updateSisaPreview();

    // Set tanggal bayar hari ini
    document.getElementById('input-tanggal-bayar').value = new Date().toISOString().split('T')[0];

    // Load riwayat pembayaran
    await loadRiwayat(pembelianId);

    document.getElementById('modal-bayar').classList.remove('hidden');
    document.getElementById('input-jumlah').focus();
}

function tutupModal() {
    document.getElementById('modal-bayar').classList.add('hidden');
    selectedPO = null;
}

function resetModal() {
    document.getElementById('input-jumlah').value = '';
    document.getElementById('input-metode').value = 'cash';
    document.getElementById('input-catatan').value = '';
    document.getElementById('alert-error').classList.add('hidden');
    document.getElementById('preview-sisa').textContent = '';
    document.getElementById('riwayat-section').classList.add('hidden');
}

function updateSisaPreview() {
    if (!selectedPO) return;
    const jumlah = parseFloat(document.getElementById('input-jumlah').value) || 0;
    const sisa   = parseFloat(selectedPO.sisa_hutang) - jumlah;
    const preview = document.getElementById('preview-sisa');

    if (jumlah <= 0) {
        preview.textContent = '';
        return;
    }

    if (jumlah > parseFloat(selectedPO.sisa_hutang)) {
        preview.textContent = '⚠ Jumlah melebihi sisa hutang';
        preview.className = 'text-xs text-red-600 dark:text-red-400 mt-1';
    } else {
        preview.textContent = `Sisa setelah bayar: ${fmt(Math.max(0, sisa))}${sisa === 0 ? ' (Lunas!)' : ''}`;
        preview.className = `text-xs mt-1 ${sisa === 0 ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-500 dark:text-gray-400'}`;
    }
}

async function loadRiwayat(pembelianId) {
    try {
        const token = localStorage.getItem('auth_token');
        const res = await axios.get(`/api/gudang/pembelian/${pembelianId}`, {
            headers: { Authorization: `Bearer ${token}` },
        });

        const riwayat = res.data.pembayaran ?? [];
        if (!riwayat.length) return;

        const list = document.getElementById('riwayat-list');
        list.innerHTML = riwayat.map(p => `
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
                <div>
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">${fmt(p.jumlah)}</p>
                    <p class="text-xs text-gray-400">${p.metode} · ${p.tanggal_bayar ?? '—'}</p>
                </div>
                <span class="text-xs text-green-600 dark:text-green-400">✓</span>
            </div>
        `).join('');

        document.getElementById('riwayat-section').classList.remove('hidden');
    } catch (err) {
        console.error('Gagal load riwayat:', err);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// SUBMIT BAYAR
// ─────────────────────────────────────────────────────────────────────────────
async function submitBayar() {
    const pembelianId  = document.getElementById('bayar-pembelian-id').value;
    const jumlah       = parseFloat(document.getElementById('input-jumlah').value);
    const metode       = document.getElementById('input-metode').value;
    const tanggalBayar = document.getElementById('input-tanggal-bayar').value;
    const catatan      = document.getElementById('input-catatan').value;

    document.getElementById('alert-error').classList.add('hidden');

    if (!jumlah || jumlah <= 0) return showError('Jumlah bayar harus lebih dari 0.');
    if (!metode)               return showError('Pilih metode pembayaran.');
    if (!tanggalBayar)         return showError('Isi tanggal bayar.');

    if (selectedPO && jumlah > parseFloat(selectedPO.sisa_hutang)) {
        return showError(`Jumlah (${fmt(jumlah)}) melebihi sisa hutang (${fmt(selectedPO.sisa_hutang)}).`);
    }

    setBtnLoading(true);

    try {
        const token = localStorage.getItem('auth_token');
        const res = await axios.post(`/api/gudang/pembelian/${pembelianId}/bayar`, {
            jumlah,
            metode,
            tanggal_bayar: tanggalBayar,
            catatan: catatan || null,
        }, {
            headers: { Authorization: `Bearer ${token}` },
        });

        tutupModal();
        showToast(res.data.message ?? 'Pembayaran berhasil dicatat!', 'success');
        loadData(currentPage);

    } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal mencatat pembayaran.';
        showError(msg);
    } finally {
        setBtnLoading(false);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function showError(msg) {
    const el = document.getElementById('alert-error');
    document.getElementById('alert-error-msg').textContent = msg;
    el.classList.remove('hidden');
}

function setBtnLoading(loading) {
    const btn = document.getElementById('btn-bayar');
    const spinner = document.getElementById('btn-spinner');
    btn.disabled = loading;
    spinner.classList.toggle('hidden', !loading);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function showToast(msg, type = 'success') {
    if (typeof window.showToast === 'function') window.showToast(msg, type);
}

// ─────────────────────────────────────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => loadData(1));
</script>
@endpush
