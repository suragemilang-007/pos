@extends('layouts.app')

@section('title', 'Daftar Pembelian')
@section('page-title', 'Pembelian (PO)')

@section('content')
<div class="space-y-5">

    {{-- ── Filter Bar ──────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">

            {{-- Filter supplier --}}
            <select id="filter-supplier"
                class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadPembelian(1)">
                <option value="">Semua Supplier</option>
            </select>

            {{-- Filter status --}}
            <select id="filter-status"
                class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadPembelian(1)">
                <option value="">Semua Status</option>
                <option value="PENDING">Menunggu</option>
                <option value="COMPLETED">Selesai</option>
                <option value="CANCELLED">Dibatalkan</option>
            </select>

            {{-- Filter dari --}}
            <div class="relative">
                <label class="absolute -top-2 left-2 px-1 text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">Dari</label>
                <input id="filter-dari" type="date"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="loadPembelian(1)">
            </div>

            {{-- Filter sampai --}}
            <div class="relative">
                <label class="absolute -top-2 left-2 px-1 text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">Sampai</label>
                <input id="filter-sampai" type="date"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="loadPembelian(1)">
            </div>

            {{-- Tombol buat PO --}}
            <a href="/gudang/pembelian/create"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat PO Baru
            </a>
        </div>
    </div>

    {{-- ── Tabel ─────────────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Skeleton --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 5; $i++)
            <div class="animate-pulse flex gap-3">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16 ml-auto"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No PO</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Total</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-pembelian" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            <div id="tbl-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada PO ditemukan.</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    <a href="/gudang/pembelian/create" class="text-indigo-600 dark:text-indigo-400 hover:underline">Buat PO baru</a> untuk mulai restock.
                </p>
            </div>
        </div>

        {{-- Pagination --}}
        <div id="pagination-wrapper" class="hidden px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p id="pagination-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div id="pagination-btns" class="flex items-center gap-1"></div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const BADGE = {
    PENDING:   'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    COMPLETED: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    CANCELLED: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
};
const LABEL_STATUS = { PENDING: 'Menunggu', COMPLETED: 'Selesai', CANCELLED: 'Dibatalkan' };

// ── Load Supplier Dropdown ───────────────────────────────────────────────────
async function loadSupplierFilter() {
    try {
        const { data } = await axios.get('/api/gudang/supplier?limit=100&status=aktif');
        const sel = document.getElementById('filter-supplier');
        (data.data || []).forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.nama_supplier;
            sel.appendChild(opt);
        });
    } catch (_) {}
}

// ── Load Tabel ───────────────────────────────────────────────────────────────
async function loadPembelian(page = 1) {
    const supplier = document.getElementById('filter-supplier').value;
    const status   = document.getElementById('filter-status').value;
    const dari     = document.getElementById('filter-dari').value;
    const sampai   = document.getElementById('filter-sampai').value;

    const params = new URLSearchParams({ page, limit: 20 });
    if (supplier) params.set('supplier_id', supplier);
    if (status)   params.set('status', status);
    if (dari)     params.set('dari', dari);
    if (sampai)   params.set('sampai', sampai);

    document.getElementById('tbl-skeleton').classList.remove('hidden');
    document.getElementById('tbl-wrapper').classList.add('hidden');
    document.getElementById('pagination-wrapper').classList.add('hidden');

    try {
        const { data } = await axios.get('/api/gudang/pembelian?' + params.toString());
        renderTabel(data.data || [], data.pagination);
    } catch (err) {
        showToast('error', 'Gagal memuat data', err.response?.data?.message || 'Periksa koneksi');
        document.getElementById('tbl-skeleton').classList.add('hidden');
        document.getElementById('tbl-wrapper').classList.remove('hidden');
    }
}

function renderTabel(rows, pagination) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');

    const tbody = document.getElementById('tbody-pembelian');
    const empty = document.getElementById('tbl-empty');

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');
    tbody.innerHTML = rows.map(p => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">${esc(p.no_pembelian)}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                ${esc(p.supplier?.nama_supplier || '—')}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">${fmtTgl(p.tanggal_pembelian)}</td>
            <td class="px-4 py-3 text-sm font-semibold font-mono text-gray-900 dark:text-gray-100 text-right">
                Rp ${fmt(p.total)}
            </td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${BADGE[p.status] || ''}">
                    ${LABEL_STATUS[p.status] || p.status}
                </span>
            </td>
            <td class="px-4 py-3 text-right">
                <a href="/gudang/pembelian/${p.id}"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Detail
                </a>
            </td>
        </tr>`).join('');

    renderPagination(pagination);
}

function renderPagination(pg) {
    if (!pg || pg.total <= pg.per_page) {
        document.getElementById('pagination-wrapper').classList.add('hidden');
        return;
    }
    document.getElementById('pagination-wrapper').classList.remove('hidden');
    document.getElementById('pagination-info').textContent =
        `Menampilkan ${Math.min((pg.current_page-1)*pg.per_page+1,pg.total)}–${Math.min(pg.current_page*pg.per_page,pg.total)} dari ${pg.total} PO`;

    const pages = [];
    for (let i = 1; i <= pg.last_page; i++) {
        if (i===1 || i===pg.last_page || Math.abs(i-pg.current_page)<=1) pages.push(i);
        else if (pages[pages.length-1] !== '...') pages.push('...');
    }
    document.getElementById('pagination-btns').innerHTML = pages.map(p =>
        p === '...'
            ? `<span class="px-2 py-1 text-xs text-gray-400">…</span>`
            : `<button onclick="loadPembelian(${p})" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${p===pg.current_page?'bg-indigo-600 text-white':'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'}">${p}</button>`
    ).join('');
}

// ── Utilitas ──────────────────────────────────────────────────────────────────
function fmt(n)    { return new Intl.NumberFormat('id-ID').format(n ?? 0); }
function fmtTgl(s) { if (!s) return '—'; try { return new Date(s).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}); } catch(_){return s;} }
function esc(str)  { if(str==null)return''; return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

// Refresh tabel saat ada purchase/retur selesai
document.addEventListener('stok-updated', function () { loadPembelian(); });

document.addEventListener('DOMContentLoaded', function () {
    loadSupplierFilter();
    loadPembelian(1);
});
</script>
@endpush
