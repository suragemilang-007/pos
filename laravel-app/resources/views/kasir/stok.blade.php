@extends('layouts.app')

@section('title', 'Pantau Stok')
@section('page-title', 'Pantau Stok Real-Time')

@push('head')
<style>
/* Flash animation saat stok berubah via WebSocket */
@keyframes stok-flash {
    0%   { background-color: transparent; }
    20%  { background-color: rgb(254 240 138 / 0.7); }   /* amber-200 */
    80%  { background-color: rgb(254 240 138 / 0.7); }
    100% { background-color: transparent; }
}
.dark @keyframes stok-flash {
    0%   { background-color: transparent; }
    20%  { background-color: rgb(120 83 15 / 0.4); }     /* amber-900/40 */
    80%  { background-color: rgb(120 83 15 / 0.4); }
    100% { background-color: transparent; }
}
.row-flash { animation: stok-flash 1s ease-in-out; }

/* Sub-row expand transition */
.subrow-enter { animation: subrow-in 0.15s ease-out; }
@keyframes subrow-in {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
@endpush

@section('content')
<div class="space-y-5">

    {{-- ── Filter Bar ───────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">

            {{-- Search nama --}}
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input id="filter-nama" type="text" placeholder="Cari nama produk..."
                    class="block w-full pl-9 pr-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    oninput="filterDebounce()">
            </div>

            {{-- Filter kategori --}}
            <select id="filter-kategori"
                class="sm:w-44 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadStok()">
                <option value="">Semua Kategori</option>
            </select>

            {{-- Filter status stok --}}
            <select id="filter-status-stok"
                class="sm:w-40 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="applyStatusFilter()">
                <option value="semua">Semua Stok</option>
                <option value="rendah">Stok Rendah</option>
                <option value="habis">Stok Habis</option>
            </select>

        </div>
    </div>

    {{-- ── Tabel Stok ───────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Header card --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Inventori Stok</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Update otomatis via WebSocket — tidak perlu refresh halaman
                </p>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span id="badge-habis" class="hidden inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 shrink-0"></span>
                    <span id="cnt-habis">0</span> habis
                </span>
                <span id="badge-rendah" class="hidden inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                    <span id="cnt-rendah">0</span> rendah
                </span>
                <span id="badge-normal" class="hidden inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500 shrink-0"></span>
                    <span id="cnt-normal">0</span> normal
                </span>
            </div>
        </div>

        {{-- Skeleton --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 7; $i++)
            <div class="animate-pulse flex gap-3 items-center">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded flex-1"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-10"></div>
                <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-14"></div>
                <div class="h-7 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
            </div>
            @endfor
        </div>

        {{-- Tabel --}}
        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-28">Kode</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama Produk</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategori</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center w-24">Sat. Dasar</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right w-28">Stok</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right w-20">Min</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center w-24">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center w-20">Detail</th>
                    </tr>
                </thead>
                <tbody id="tbody-stok"></tbody>
            </table>

            <div id="tbl-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada produk ditemukan.</p>
            </div>
        </div>

    </div>

    {{-- ── Legenda ───────────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-6 text-xs text-gray-500 dark:text-gray-400 px-1">
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-sm bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700"></span>
            Stok habis (= 0)
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-sm bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700"></span>
            Stok rendah (≤ minimum)
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"></span>
            Normal
        </span>
    </div>

</div>
@endsection

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
// authToken, axios, wsConn, showToast → sudah tersedia dari layouts/app.blade.php
let stokData     = [];   // cache lokal hasil GET /api/kasir/stok
let expandedRows = new Set();  // id produk yang sub-row-nya sedang terbuka
let filterTimer  = null;

// ── Load Kategori (untuk dropdown) ───────────────────────────────────────────
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

// ── Load Stok ─────────────────────────────────────────────────────────────────
async function loadStok() {
    const nama     = document.getElementById('filter-nama').value.trim();
    const kategori = document.getElementById('filter-kategori').value;

    const params = new URLSearchParams({ limit: 100 }); // Muat semua untuk filter client-side
    if (nama)     params.set('nama', nama);
    if (kategori) params.set('kategori_id', kategori);

    document.getElementById('tbl-skeleton').classList.remove('hidden');
    document.getElementById('tbl-wrapper').classList.add('hidden');
    expandedRows.clear();

    try {
        const { data } = await axios.get('/api/kasir/stok?' + params.toString());
        stokData = (data.data?.stok ?? data.data ?? []);
        renderTabel(stokData);
        applyStatusFilter();
        updateBadgeSummary();
    } catch (err) {
        showToast('error', 'Gagal memuat stok', err.response?.data?.message || 'Periksa koneksi');
        document.getElementById('tbl-skeleton').classList.add('hidden');
        document.getElementById('tbl-wrapper').classList.remove('hidden');
    }
}

// ── Render Tabel Penuh ────────────────────────────────────────────────────────
function renderTabel(rows) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');

    const tbody = document.getElementById('tbody-stok');
    const empty = document.getElementById('tbl-empty');

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');
    tbody.innerHTML = rows.map(p => buildRowHtml(p) + buildSubRowHtml(p)).join('');
}

function buildRowHtml(p) {
    const stok    = p.qty_stok ?? 0;
    const min     = p.stok_minimum ?? 0;
    const rowBg   = rowBgClass(stok, min);
    const badge   = statusBadgeHtml(stok, min);

    return `
    <tr id="row-${p.id}" data-stok="${stok}" data-min="${min}"
        class="border-b border-gray-100 dark:border-gray-700/50 transition-colors ${rowBg}">
        <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">${esc(p.kode_produk)}</td>
        <td class="px-4 py-3">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${esc(p.nama_produk)}</p>
        </td>
        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
            ${p.kategori ? esc(p.kategori) : '<span class="text-gray-300 dark:text-gray-600">—</span>'}
        </td>
        <td class="px-4 py-3 text-center">
            <span class="text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">
                ${esc(p.satuan_dasar)}
            </span>
        </td>
        <td class="px-4 py-3 text-right" id="cell-stok-${p.id}">
            <span class="text-sm font-bold font-mono ${stok === 0 ? 'text-red-700 dark:text-red-400' : stok <= min ? 'text-amber-700 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100'}">
                ${fmt(stok)}
            </span>
        </td>
        <td class="px-4 py-3 text-right text-sm font-mono text-gray-400 dark:text-gray-500">${fmt(min)}</td>
        <td class="px-4 py-3 text-center" id="cell-badge-${p.id}">${badge}</td>
        <td class="px-4 py-3 text-center">
            <button id="btn-detail-${p.id}" onclick="toggleDetail(${p.id})"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                Detail
            </button>
        </td>
    </tr>`;
}

function buildSubRowHtml(p) {
    const satuanHtml = (p.satuan_tersedia || []).map(s => `
        <div class="flex items-center gap-3 text-xs">
            <span class="text-gray-300 dark:text-gray-600 font-mono select-none">└──</span>
            <span class="font-semibold text-gray-700 dark:text-gray-300 w-20">${esc(s.nama_satuan)}</span>
            <span class="text-gray-400 dark:text-gray-500">|</span>
            <span id="sub-stok-${p.id}-${s.id}" class="${s.stok_dalam_satuan === 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-900 dark:text-gray-100'} font-mono w-24">
                ${fmt(s.stok_dalam_satuan)} tersedia
            </span>
            <span class="text-gray-400 dark:text-gray-500">|</span>
            <span class="font-mono text-gray-600 dark:text-gray-400">Rp ${fmt(s.harga_jual)}</span>
            ${s.is_default ? '<span class="text-indigo-500 dark:text-indigo-400 text-xs font-medium">· default</span>' : ''}
        </div>`).join('');

    return `
    <tr id="subrow-${p.id}" class="hidden">
        <td colspan="8" class="px-4 pt-1 pb-3 bg-gray-50 dark:bg-gray-700/20 border-b border-gray-100 dark:border-gray-700/50">
            <div class="ml-8 space-y-1.5 subrow-enter">${satuanHtml || '<p class="text-xs text-gray-400">Belum ada satuan.</p>'}</div>
        </td>
    </tr>`;
}

// ── Toggle Detail Sub-Row ─────────────────────────────────────────────────────
function toggleDetail(produkId) {
    const subrow = document.getElementById('subrow-' + produkId);
    const btn    = document.getElementById('btn-detail-' + produkId);
    if (!subrow) return;

    const isOpen = !subrow.classList.contains('hidden');

    if (isOpen) {
        subrow.classList.add('hidden');
        expandedRows.delete(produkId);
        btn.innerHTML = `
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg> Detail`;
    } else {
        subrow.classList.remove('hidden');
        expandedRows.add(produkId);
        btn.innerHTML = `
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
            </svg> Tutup`;
    }
}

// ── Filter Status Stok (client-side) ─────────────────────────────────────────
function applyStatusFilter() {
    const statusFilter = document.getElementById('filter-status-stok').value;

    stokData.forEach(p => {
        const rowEl    = document.getElementById('row-' + p.id);
        const subrowEl = document.getElementById('subrow-' + p.id);
        if (!rowEl) return;

        const stok = p.qty_stok ?? 0;
        const min  = p.stok_minimum ?? 0;

        let show = true;
        if (statusFilter === 'rendah') show = stok > 0 && stok <= min;
        if (statusFilter === 'habis')  show = stok === 0;

        rowEl.style.display    = show ? '' : 'none';
        if (subrowEl) subrowEl.style.display = (show && expandedRows.has(p.id)) ? '' : 'none';
    });
}

// ── Badge Summary (atas tabel) ────────────────────────────────────────────────
function updateBadgeSummary() {
    let habis = 0, rendah = 0, normal = 0;

    stokData.forEach(p => {
        const stok = p.qty_stok ?? 0;
        const min  = p.stok_minimum ?? 0;
        if (stok === 0)        habis++;
        else if (stok <= min)  rendah++;
        else                   normal++;
    });

    const setB = (id, cnt, cntId) => {
        document.getElementById(id).classList.toggle('hidden', cnt === 0);
        document.getElementById(cntId).textContent = cnt;
    };

    setB('badge-habis',  habis,  'cnt-habis');
    setB('badge-rendah', rendah, 'cnt-rendah');
    setB('badge-normal', normal, 'cnt-normal');
}

// ── WebSocket Real-Time Update ────────────────────────────────────────────────
// Event 'stok-updated' di-dispatch oleh layouts/app.blade.php saat
// menerima {event:'stock.updated', updates:[{produkId, stokBaru}]} dari Consumer.
// TIDAK ada polling — hanya WS event yang mengubah UI.

document.addEventListener('stok-updated', function (e) {
    const updates = e.detail || [];
    updates.forEach(u => updateStokRow(u.produkId, u.stokBaru));
    updateBadgeSummary();
    applyStatusFilter();
});

function updateStokRow(produkId, stokBaru) {
    // 1. Update cache lokal
    const item = stokData.find(p => p.id === produkId);
    if (!item) return;

    const stokLama = item.qty_stok;
    item.qty_stok  = stokBaru;

    // Recalculate stok_dalam_satuan per satuan
    (item.satuan_tersedia || []).forEach(s => {
        s.stok_dalam_satuan = Math.floor(stokBaru / (s.isi || 1));
    });

    // 2. Update main row cell stok
    const stokCell = document.getElementById('cell-stok-' + produkId);
    if (stokCell) {
        const min = item.stok_minimum ?? 0;
        stokCell.innerHTML = `
            <span class="text-sm font-bold font-mono ${stokBaru === 0 ? 'text-red-700 dark:text-red-400' : stokBaru <= min ? 'text-amber-700 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100'}">
                ${fmt(stokBaru)}
            </span>`;
    }

    // 3. Update row background + data attribute
    const rowEl = document.getElementById('row-' + produkId);
    if (rowEl) {
        rowEl.setAttribute('data-stok', stokBaru);
        const min = parseInt(rowEl.getAttribute('data-min')) || 0;

        // Hapus class background lama
        rowEl.classList.remove(
            'bg-red-50','dark:bg-red-900/20',
            'bg-amber-50','dark:bg-amber-900/20'
        );
        // Terapkan class baru
        const newBg = rowBgClass(stokBaru, min);
        if (newBg) {
            newBg.split(' ').forEach(cls => { if (cls) rowEl.classList.add(cls); });
        }

        // 4. Flash animation — highlight baris yang berubah
        if (stokBaru !== stokLama) {
            rowEl.classList.remove('row-flash');
            void rowEl.offsetWidth; // reflow untuk reset animasi
            rowEl.classList.add('row-flash');
            rowEl.addEventListener('animationend', function handler() {
                rowEl.classList.remove('row-flash');
                rowEl.removeEventListener('animationend', handler);
            });
        }
    }

    // 5. Update status badge
    const badgeCell = document.getElementById('cell-badge-' + produkId);
    if (badgeCell) {
        badgeCell.innerHTML = statusBadgeHtml(stokBaru, item.stok_minimum ?? 0);
    }

    // 6. Update sub-row jika terbuka
    if (expandedRows.has(produkId)) {
        (item.satuan_tersedia || []).forEach(s => {
            const subStokEl = document.getElementById(`sub-stok-${produkId}-${s.id}`);
            if (!subStokEl) return;
            subStokEl.textContent = `${fmt(s.stok_dalam_satuan)} tersedia`;
            subStokEl.className = s.stok_dalam_satuan === 0
                ? 'text-red-600 dark:text-red-400 font-semibold font-mono w-24'
                : 'text-gray-900 dark:text-gray-100 font-mono w-24';
        });
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function rowBgClass(stok, min) {
    if (stok === 0)       return 'bg-red-50 dark:bg-red-900/20';
    if (stok <= min)      return 'bg-amber-50 dark:bg-amber-900/20';
    return '';
}

function statusBadgeHtml(stok, min) {
    if (stok === 0) {
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
            Habis
        </span>`;
    }
    if (stok <= min) {
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
            Rendah
        </span>`;
    }
    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
        Normal
    </span>`;
}

function filterDebounce() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(loadStok, 400);
}

function fmt(n) { return new Intl.NumberFormat('id-ID').format(n ?? 0); }
function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    loadKategoriFilter();
    loadStok();
});
</script>
@endpush
