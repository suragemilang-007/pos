@extends('layouts.app')

@section('title', 'Daftar Transaksi')
@section('page-title', 'Daftar Transaksi')

@section('content')
<div class="space-y-5">

    {{-- ── Filter ─────────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3 flex-wrap">

            {{-- Dari Tanggal --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Dari Tanggal</label>
                <input id="filter-dari" type="date"
                    class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
            </div>

            {{-- Sampai Tanggal --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Sampai Tanggal</label>
                <input id="filter-sampai" type="date"
                    class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
            </div>

            {{-- Filter Status --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Status</label>
                <select id="filter-status"
                    class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                    <option value="">Semua Status</option>
                    <option value="COMPLETED">Selesai</option>
                    <option value="VOIDED">Di-void</option>
                    <option value="PENDING">Pending</option>
                    <option value="FAILED">Gagal</option>
                </select>
            </div>

            {{-- Search --}}
            <div class="flex flex-col gap-1 flex-1 min-w-48">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Cari</label>
                <input id="filter-q" type="text" placeholder="No. transaksi atau nama customer..."
                    class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onkeydown="if(event.key==='Enter') loadTransaksi(1)">
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex items-end gap-2">
                <button onclick="loadTransaksi(1)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Cari
                </button>
                <button onclick="resetFilter()"
                    class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    Reset
                </button>
            </div>
        </div>
    </div>

    {{-- ── Ringkasan Cepat ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Transaksi</p>
            <p id="stat-total" class="mt-1 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Omzet</p>
            <p id="stat-omzet" class="mt-1 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Transaksi Di-void</p>
            <p id="stat-void" class="mt-1 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
        </div>
    </div>

    {{-- ── Tabel Transaksi ─────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Skeleton loading --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 8; $i++)
            <div class="animate-pulse flex gap-4 items-center">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-32 shrink-0"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-28"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 ml-auto"></div>
                <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded-full w-20"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-lg w-16"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">No. Transaksi</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Tanggal</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kasir</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Metode Bayar</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Total</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-transaksi" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            {{-- Empty state --}}
            <div id="tbl-empty" class="hidden px-6 py-14 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada transaksi ditemukan.</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Coba ubah filter tanggal atau hapus kata kunci pencarian.</p>
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

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL DETAIL TRANSAKSI
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-detail"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

        {{-- Header modal --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
            <div class="flex items-center gap-3">
                <h3 id="modal-no-trx" class="text-base font-semibold text-gray-900 dark:text-gray-100 font-mono">—</h3>
                <span id="modal-badge-status"></span>
            </div>
            <button onclick="tutupModal()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body modal --}}
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

            {{-- Skeleton modal --}}
            <div id="modal-skeleton" class="space-y-4">
                <div class="animate-pulse grid grid-cols-2 gap-3">
                    @for ($i = 0; $i < 4; $i++)
                    <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    @endfor
                </div>
                <div class="animate-pulse space-y-2 mt-4">
                    @for ($i = 0; $i < 4; $i++)
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    @endfor
                </div>
            </div>

            {{-- Konten detail (tampil setelah data dimuat) --}}
            <div id="modal-konten" class="hidden space-y-5">

                {{-- Info ringkasan --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal</p>
                        <p id="modal-tanggal" class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Customer</p>
                        <p id="modal-customer" class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Kasir</p>
                        <p id="modal-kasir" class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Metode Bayar</p>
                        <p id="modal-metode" class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5 capitalize">—</p>
                    </div>
                </div>

                {{-- Tabel item --}}
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qty</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Satuan</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Harga</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="modal-tbody" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                    </table>
                </div>

                {{-- Total --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Subtotal</span>
                        <span id="modal-subtotal" class="font-mono">—</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Diskon</span>
                        <span id="modal-diskon" class="font-mono text-red-500 dark:text-red-400">—</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold text-gray-900 dark:text-gray-100 pt-2 border-t border-gray-200 dark:border-gray-600">
                        <span>Total</span>
                        <span id="modal-total" class="font-mono text-indigo-600 dark:text-indigo-400">—</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Bayar</span>
                        <span id="modal-bayar" class="font-mono">—</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Kembalian</span>
                        <span id="modal-kembalian" class="font-mono">—</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer modal --}}
        <div class="shrink-0 flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button id="btn-cetak-struk" onclick="cetakStruk()"
                class="hidden inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak Struk
            </button>
            <button onclick="tutupModal()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let currentPage    = 1;
let detailIdAktif  = null;

// ── Init: set default tanggal hari ini & load data ────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    if (authRole !== 'owner') {
        window.location.replace('/');
        return;
    }

    const today = new Date().toLocaleDateString('sv');
    document.getElementById('filter-dari').value   = today;
    document.getElementById('filter-sampai').value = today;

    loadTransaksi(1);
});

// ── Load Daftar Transaksi ─────────────────────────────────────────────────────
async function loadTransaksi(page = 1) {
    currentPage = page;

    const dari   = document.getElementById('filter-dari').value;
    const sampai = document.getElementById('filter-sampai').value;
    const status = document.getElementById('filter-status').value;
    const q      = document.getElementById('filter-q').value.trim();

    const params = new URLSearchParams({ page, limit: 20 });
    if (dari)   params.set('dari',   dari);
    if (sampai) params.set('sampai', sampai);
    if (status) params.set('status', status);
    if (q)      params.set('q',      q);

    document.getElementById('tbl-skeleton').classList.remove('hidden');
    document.getElementById('tbl-wrapper').classList.add('hidden');
    document.getElementById('pagination-wrapper').classList.add('hidden');

    try {
        const { data } = await axios.get('/api/owner/transaksi?' + params.toString());
        renderTabel(data.data || [], data.pagination, data.ringkasan);
    } catch (err) {
        showToast('error', 'Gagal memuat data', err.response?.data?.message || 'Periksa koneksi');
        document.getElementById('tbl-skeleton').classList.add('hidden');
        document.getElementById('tbl-wrapper').classList.remove('hidden');
        document.getElementById('tbl-empty').classList.remove('hidden');
    }
}

// ── Render Tabel ──────────────────────────────────────────────────────────────
function renderTabel(rows, pagination, ringkasan) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');

    const tbody = document.getElementById('tbody-transaksi');
    const empty = document.getElementById('tbl-empty');

    // Update kartu ringkasan
    if (ringkasan) {
        document.getElementById('stat-total').textContent = ringkasan.total_transaksi ?? '—';
        document.getElementById('stat-omzet').textContent = 'Rp ' + fmt(ringkasan.total_omzet ?? 0);
        document.getElementById('stat-void').textContent  = ringkasan.total_void ?? '—';
    }

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        document.getElementById('pagination-wrapper').classList.add('hidden');
        return;
    }

    empty.classList.add('hidden');

    tbody.innerHTML = rows.map(t => {
        const statusBadge = buatBadgeStatus(t.status);
        const metodeBadge = buatBadgeMetode(t.metode_bayar);

        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3">
                <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                    ${esc(t.no_transaksi)}
                </span>
            </td>
            <td class="px-4 py-3 whitespace-nowrap">
                <p class="text-sm text-gray-800 dark:text-gray-200">${fmtTanggal(t.tanggal_transaksi)}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">${fmtJam(t.tanggal_transaksi)}</p>
            </td>
            <td class="px-4 py-3">
                ${t.customer
                    ? `<span class="text-sm text-gray-800 dark:text-gray-200">${esc(t.customer.nama)}</span>`
                    : `<span class="text-xs text-gray-400 dark:text-gray-500 italic">Umum</span>`}
            </td>
            <td class="px-4 py-3">
                <span class="text-sm text-gray-600 dark:text-gray-400">${esc(t.kasir?.nama ?? '—')}</span>
            </td>
            <td class="px-4 py-3">${metodeBadge}</td>
            <td class="px-4 py-3 text-right">
                <span class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100 whitespace-nowrap">
                    Rp ${fmt(t.total)}
                </span>
            </td>
            <td class="px-4 py-3 text-center">${statusBadge}</td>
            <td class="px-4 py-3 text-center">
                <button onclick="bukaDetail(${t.id})"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Detail
                </button>
            </td>
        </tr>`;
    }).join('');

    renderPaginasi(pagination);
}

// ── Paginasi ──────────────────────────────────────────────────────────────────
function renderPaginasi(pg) {
    if (!pg || pg.total <= pg.per_page) {
        document.getElementById('pagination-wrapper').classList.add('hidden');
        return;
    }

    document.getElementById('pagination-wrapper').classList.remove('hidden');
    document.getElementById('pagination-info').textContent =
        `Menampilkan ${Math.min((pg.current_page - 1) * pg.per_page + 1, pg.total)}–${Math.min(pg.current_page * pg.per_page, pg.total)} dari ${pg.total} transaksi`;

    const container = document.getElementById('pagination-btns');
    const pages = [];
    for (let i = 1; i <= pg.last_page; i++) {
        if (i === 1 || i === pg.last_page || Math.abs(i - pg.current_page) <= 1) pages.push(i);
        else if (pages[pages.length - 1] !== '...') pages.push('...');
    }

    container.innerHTML = pages.map(p =>
        p === '...'
            ? `<span class="px-2 py-1 text-xs text-gray-400">…</span>`
            : `<button onclick="loadTransaksi(${p})"
                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${p === pg.current_page
                    ? 'bg-indigo-600 text-white'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'}">${p}</button>`
    ).join('');
}

// ── Modal Detail ──────────────────────────────────────────────────────────────
async function bukaDetail(id) {
    detailIdAktif = id;

    // Reset konten ke state loading
    document.getElementById('modal-skeleton').classList.remove('hidden');
    document.getElementById('modal-konten').classList.add('hidden');
    document.getElementById('modal-no-trx').textContent = '…';
    document.getElementById('modal-badge-status').innerHTML = '';
    document.getElementById('btn-cetak-struk').classList.add('hidden');

    tampilkanModal('modal-detail');

    try {
        const { data } = await axios.get(`/api/kasir/transaksi/${id}`);
        const t = data.data;

        // Header
        document.getElementById('modal-no-trx').textContent = t.no_transaksi;
        document.getElementById('modal-badge-status').innerHTML = buatBadgeStatus(t.status);

        // Info ringkasan
        document.getElementById('modal-tanggal').textContent   = fmtDatetime(t.tanggal_transaksi);
        document.getElementById('modal-customer').textContent  = t.customer?.nama ?? 'Umum';
        document.getElementById('modal-kasir').textContent     = t.kasir?.nama ?? t.kasir ?? '—';
        document.getElementById('modal-metode').textContent    = t.metode_bayar ?? '—';

        // Items
        const tbody = document.getElementById('modal-tbody');
        tbody.innerHTML = (t.items || []).map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                <td class="px-3 py-2.5">
                    <p class="text-sm text-gray-800 dark:text-gray-200">${esc(item.nama_produk ?? '—')}</p>
                    ${item.is_bonus ? '<span class="text-xs text-green-600 dark:text-green-400 font-medium">Bonus</span>' : ''}
                </td>
                <td class="px-3 py-2.5 text-center text-sm text-gray-700 dark:text-gray-300 font-mono">${item.qty}</td>
                <td class="px-3 py-2.5 text-center text-sm text-gray-600 dark:text-gray-400">${esc(item.nama_satuan)}</td>
                <td class="px-3 py-2.5 text-right text-sm font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">
                    Rp ${fmt(item.harga_satuan)}
                </td>
                <td class="px-3 py-2.5 text-right text-sm font-mono font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                    Rp ${fmt(item.subtotal)}
                </td>
            </tr>`).join('');

        // Total
        const diskon = (t.subtotal ?? 0) - (t.total ?? 0) + (t.diskon ?? 0);
        document.getElementById('modal-subtotal').textContent  = 'Rp ' + fmt(t.subtotal ?? 0);
        document.getElementById('modal-diskon').textContent    = t.diskon > 0 ? '- Rp ' + fmt(t.diskon) : 'Rp 0';
        document.getElementById('modal-total').textContent     = 'Rp ' + fmt(t.total ?? 0);
        document.getElementById('modal-bayar').textContent     = 'Rp ' + fmt(t.bayar ?? 0);
        document.getElementById('modal-kembalian').textContent = 'Rp ' + fmt(t.kembalian ?? 0);

        // Tampilkan tombol cetak struk hanya jika COMPLETED
        if (t.status === 'COMPLETED') {
            document.getElementById('btn-cetak-struk').classList.remove('hidden');
        }

        document.getElementById('modal-skeleton').classList.add('hidden');
        document.getElementById('modal-konten').classList.remove('hidden');

    } catch (err) {
        showToast('error', 'Gagal memuat detail', err.response?.data?.message || 'Terjadi kesalahan');
        tutupModal();
    }
}

async function cetakStruk() {
    if (!detailIdAktif) return;

    try {
        const { data } = await axios.get(`/api/kasir/transaksi/${detailIdAktif}/struk`);
        const popup = window.open('', '_blank', 'width=400,height=600');
        popup.document.write(data.data.html);
        popup.document.close();
        popup.focus();
        popup.print();
    } catch (err) {
        showToast('error', 'Gagal cetak struk', err.response?.data?.message || 'Terjadi kesalahan');
    }
}

function tutupModal() {
    sembunyikanModal('modal-detail');
    detailIdAktif = null;
}

// ── Reset Filter ──────────────────────────────────────────────────────────────
function resetFilter() {
    const today = new Date().toLocaleDateString('sv');
    document.getElementById('filter-dari').value   = today;
    document.getElementById('filter-sampai').value = today;
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-q').value      = '';
    loadTransaksi(1);
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

// Tutup modal klik backdrop
document.getElementById('modal-detail')?.addEventListener('click', function (e) {
    if (e.target === this) tutupModal();
});

// ── Badge Helpers ─────────────────────────────────────────────────────────────
function buatBadgeStatus(status) {
    const map = {
        COMPLETED: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        VOIDED:    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        PENDING:   'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        FAILED:    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        CANCELLED: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    };
    const label = {
        COMPLETED: 'Selesai',
        VOIDED:    'Di-void',
        PENDING:   'Pending',
        FAILED:    'Gagal',
        CANCELLED: 'Dibatalkan',
    };
    const cls = map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${cls}">
                ${label[status] || status}
            </span>`;
}

function buatBadgeMetode(metode) {
    const map = {
        tunai:    'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
        transfer: 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400',
        qris:     'bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-400',
    };
    const label = { tunai: 'Tunai', transfer: 'Transfer', qris: 'QRIS' };
    const cls   = map[metode] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
    return `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${cls}">
                ${label[metode] || esc(metode)}
            </span>`;
}

// ── Format Utilitas ───────────────────────────────────────────────────────────
function fmt(n) { return new Intl.NumberFormat('id-ID').format(n || 0); }

function fmtTanggal(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function fmtJam(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

function fmtDatetime(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
        + ', ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

function esc(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
@endpush
