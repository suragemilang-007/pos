@extends('layouts.app')

@section('title', 'Riwayat Belanja')
@section('page-title', 'Riwayat Belanja Customer')

@section('content')
<div class="space-y-5">

    {{-- ── Breadcrumb ──────────────────────────────────────────────────────── --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="/customer" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Data Customer</a>
        <span>/</span>
        <span id="bc-nama" class="text-gray-900 dark:text-gray-100 font-medium">Riwayat Belanja</span>
    </nav>

    {{-- ── Header Customer ─────────────────────────────────────────────────── --}}
    <div id="header-skeleton" class="animate-pulse bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full shrink-0"></div>
            <div class="flex-1 space-y-2">
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
            </div>
        </div>
    </div>

    <div id="header-customer" class="hidden bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            {{-- Info Customer --}}
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/40 rounded-full flex items-center justify-center shrink-0">
                    <span id="customer-avatar" class="text-lg font-bold text-indigo-600 dark:text-indigo-400">?</span>
                </div>
                <div>
                    <h2 id="customer-nama" class="text-lg font-bold text-gray-900 dark:text-gray-100">—</h2>
                    <p id="customer-telp" class="text-sm font-mono text-gray-500 dark:text-gray-400 mt-0.5"></p>
                    <div class="flex items-center gap-2 mt-1">
                        <span id="customer-tipe" class="text-xs px-2 py-0.5 rounded font-medium"></span>
                        <span id="customer-status" class="text-xs px-2 py-0.5 rounded-full font-medium"></span>
                    </div>
                </div>
            </div>

            {{-- Summary Stat Cards --}}
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 text-center border border-indigo-100 dark:border-indigo-800">
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">Total Belanja</p>
                    <p id="stat-total" class="mt-1 text-xl font-bold font-mono text-indigo-700 dark:text-indigo-300">—</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 text-center border border-green-100 dark:border-green-800">
                    <p class="text-xs text-green-600 dark:text-green-400 font-medium">Frekuensi</p>
                    <p id="stat-frekuensi" class="mt-1 text-xl font-bold font-mono text-green-700 dark:text-green-300">—</p>
                    <p class="text-xs text-green-500 dark:text-green-500 mt-0.5">transaksi</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabel Riwayat ────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Riwayat Transaksi</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Klik baris untuk melihat detail transaksi</p>
        </div>

        {{-- Skeleton --}}
        <div id="riwayat-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 5; $i++)
            <div class="animate-pulse flex gap-4">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-12"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
            </div>
            @endfor
        </div>

        <div id="riwayat-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No Transaksi</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Items</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Total</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Metode</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="tbody-riwayat" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            <div id="riwayat-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada transaksi.</p>
            </div>
        </div>

        {{-- Pagination --}}
        <div id="riwayat-pagination" class="hidden px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p id="riwayat-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div id="riwayat-btns" class="flex items-center gap-1"></div>
        </div>
    </div>

</div>
@endsection

{{-- ══ MODAL DETAIL TRANSAKSI ══════════════════════════════════════════════ --}}
<div id="modal-detail"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">

        {{-- Header modal --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
            <div>
                <h3 id="modal-no-trx" class="text-base font-semibold text-gray-900 dark:text-gray-100 font-mono">—</h3>
                <p id="modal-tgl-trx" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></p>
            </div>
            <button onclick="tutupModalDetail()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body modal --}}
        <div class="flex-1 overflow-y-auto">

            {{-- Skeleton detail --}}
            <div id="detail-skeleton" class="p-6 space-y-3">
                @for ($i = 0; $i < 3; $i++)
                <div class="animate-pulse flex gap-3">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/5"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20 ml-auto"></div>
                </div>
                @endfor
            </div>

            {{-- Tabel item --}}
            <div id="detail-table" class="hidden overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Satuan</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Qty</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Harga</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-detail" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                </table>
            </div>

            {{-- Footer ringkasan --}}
            <div id="detail-footer" class="hidden px-6 py-4 border-t border-gray-200 dark:border-gray-700 space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                    <span id="detail-subtotal" class="font-mono text-gray-900 dark:text-gray-100"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Diskon</span>
                    <span id="detail-diskon" class="font-mono text-gray-900 dark:text-gray-100"></span>
                </div>
                <div class="flex justify-between text-base font-bold border-t border-gray-200 dark:border-gray-700 pt-2 mt-2">
                    <span class="text-gray-900 dark:text-gray-100">Total</span>
                    <span id="detail-total" class="font-mono text-indigo-600 dark:text-indigo-400"></span>
                </div>
                <div class="flex justify-between text-sm pt-1">
                    <span class="text-gray-500 dark:text-gray-400">Metode Bayar</span>
                    <span id="detail-metode" class="text-gray-900 dark:text-gray-100"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Bayar</span>
                    <span id="detail-bayar" class="font-mono text-gray-900 dark:text-gray-100"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Kembalian</span>
                    <span id="detail-kembalian" class="font-mono text-green-600 dark:text-green-400"></span>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
// ── Konstanta ─────────────────────────────────────────────────────────────────
const CUSTOMER_ID = {{ $customerId }};

// ── State ─────────────────────────────────────────────────────────────────────
let riwayatPage  = 1;
let totalPage    = 1;
let riwayatData  = [];

// ── Load Customer Info & Riwayat ──────────────────────────────────────────────
async function init() {
    await Promise.all([loadCustomerInfo(), loadRiwayat(1)]);
}

async function loadCustomerInfo() {
    try {
        const { data } = await axios.get(`/api/kasir/customer/${CUSTOMER_ID}`);
        const c = data.data;

        document.getElementById('header-skeleton').classList.add('hidden');
        document.getElementById('header-customer').classList.remove('hidden');

        document.getElementById('bc-nama').textContent         = c.nama;
        document.getElementById('customer-avatar').textContent = c.nama.charAt(0).toUpperCase();
        document.getElementById('customer-nama').textContent   = c.nama;
        document.getElementById('customer-telp').textContent   = c.no_telp;

        const tipeBadge = c.tipe_customer === 'grosir'
            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
        const tipeEl = document.getElementById('customer-tipe');
        tipeEl.className = `text-xs px-2 py-0.5 rounded font-medium ${tipeBadge}`;
        tipeEl.textContent = c.tipe_customer === 'grosir' ? 'Grosir' : 'Reguler';

        const statusEl = document.getElementById('customer-status');
        statusEl.className = c.status === 'aktif'
            ? 'text-xs px-2.5 py-0.5 rounded-full font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
            : 'text-xs px-2.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
        statusEl.textContent = c.status === 'aktif' ? 'Aktif' : 'Nonaktif';

    } catch (err) {
        showToast('error', 'Gagal memuat data customer', err.response?.data?.message || '');
    }
}

async function loadRiwayat(page = 1) {
    riwayatPage = page;

    document.getElementById('riwayat-skeleton').classList.remove('hidden');
    document.getElementById('riwayat-wrapper').classList.add('hidden');
    document.getElementById('riwayat-pagination').classList.add('hidden');

    try {
        const { data } = await axios.get(`/api/kasir/customer/${CUSTOMER_ID}/riwayat?page=${page}&limit=20`);
        const d = data.data;

        // Update summary stats
        document.getElementById('stat-total').textContent     = 'Rp ' + fmt(d.total_belanja || 0);
        document.getElementById('stat-frekuensi').textContent = d.frekuensi || 0;

        totalPage   = d.total_page || 1;
        riwayatData = d.riwayat || [];

        renderRiwayat(riwayatData, d);

    } catch (err) {
        showToast('error', 'Gagal memuat riwayat', err.response?.data?.message || '');
        document.getElementById('riwayat-skeleton').classList.add('hidden');
        document.getElementById('riwayat-wrapper').classList.remove('hidden');
    }
}

function renderRiwayat(rows, summary) {
    document.getElementById('riwayat-skeleton').classList.add('hidden');
    document.getElementById('riwayat-wrapper').classList.remove('hidden');

    const tbody  = document.getElementById('tbody-riwayat');
    const empty  = document.getElementById('riwayat-empty');

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');

    const BADGE = {
        COMPLETED: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        PENDING:   'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        FAILED:    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        CANCELLED: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    };
    const LABEL = { COMPLETED: 'Selesai', PENDING: 'Proses', FAILED: 'Gagal', CANCELLED: 'Batal' };
    const METODE = { tunai: 'Tunai', transfer: 'Transfer', qris: 'QRIS' };

    tbody.innerHTML = rows.map(t => `
        <tr class="hover:bg-indigo-50 dark:hover:bg-indigo-900/10 cursor-pointer transition-colors"
            onclick="lihatDetailTransaksi(${t.id})">
            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">${esc(t.no_transaksi)}</td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">${formatTgl(t.tanggal)}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300">
                    ${t.jumlah_item ?? '—'}
                </span>
            </td>
            <td class="px-4 py-3 text-right font-mono font-semibold text-gray-900 dark:text-gray-100">
                Rp ${fmt(t.total)}
            </td>
            <td class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400">
                ${METODE[t.metode_bayar] ?? t.metode_bayar}
            </td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${BADGE[t.status] ?? ''}">
                    ${LABEL[t.status] ?? t.status}
                </span>
            </td>
        </tr>`).join('');

    renderPaginasiRiwayat(summary);
}

function renderPaginasiRiwayat(summary) {
    if (!summary || totalPage <= 1) {
        document.getElementById('riwayat-pagination').classList.add('hidden');
        return;
    }

    document.getElementById('riwayat-pagination').classList.remove('hidden');
    document.getElementById('riwayat-info').textContent =
        `Halaman ${riwayatPage} dari ${totalPage}`;

    const btns = document.getElementById('riwayat-btns');
    const pages = [];
    for (let i = 1; i <= totalPage; i++) {
        if (i === 1 || i === totalPage || Math.abs(i - riwayatPage) <= 1) pages.push(i);
        else if (pages[pages.length - 1] !== '...') pages.push('...');
    }

    btns.innerHTML = pages.map(p =>
        p === '...'
            ? `<span class="px-2 py-1 text-xs text-gray-400">…</span>`
            : `<button onclick="loadRiwayat(${p})"
                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${p === riwayatPage
                    ? 'bg-indigo-600 text-white'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'}">${p}</button>`
    ).join('');
}

// ── Modal Detail Transaksi ────────────────────────────────────────────────────
async function lihatDetailTransaksi(trxId) {
    tampilkanModal('modal-detail');
    document.getElementById('detail-skeleton').classList.remove('hidden');
    document.getElementById('detail-table').classList.add('hidden');
    document.getElementById('detail-footer').classList.add('hidden');
    document.getElementById('modal-no-trx').textContent = 'Memuat...';

    try {
        const { data } = await axios.get(`/api/kasir/transaksi/${trxId}`);
        const t = data.data;

        document.getElementById('modal-no-trx').textContent  = t.no_transaksi;
        document.getElementById('modal-tgl-trx').textContent = formatTgl(t.tanggal_transaksi);

        document.getElementById('detail-skeleton').classList.add('hidden');
        document.getElementById('detail-table').classList.remove('hidden');
        document.getElementById('detail-footer').classList.remove('hidden');

        // Render item
        const tbody = document.getElementById('tbody-detail');
        tbody.innerHTML = (t.items || []).map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">${esc(item.nama_produk)}</td>
                <td class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400">${esc(item.nama_satuan)}</td>
                <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100 font-semibold">${item.qty}</td>
                <td class="px-4 py-3 text-right font-mono text-sm text-gray-600 dark:text-gray-400">
                    Rp ${fmt(item.harga_satuan)}
                </td>
                <td class="px-4 py-3 text-right font-mono text-sm font-semibold text-gray-900 dark:text-gray-100">
                    Rp ${fmt(item.subtotal)}
                </td>
            </tr>`).join('');

        // Footer
        const METODE = { tunai: 'Tunai', transfer: 'Transfer', qris: 'QRIS' };
        document.getElementById('detail-subtotal').textContent   = 'Rp ' + fmt(t.subtotal);
        document.getElementById('detail-diskon').textContent     = 'Rp ' + fmt(t.diskon ?? 0);
        document.getElementById('detail-total').textContent      = 'Rp ' + fmt(t.total);
        document.getElementById('detail-metode').textContent     = METODE[t.metode_bayar] ?? t.metode_bayar;
        document.getElementById('detail-bayar').textContent      = 'Rp ' + fmt(t.bayar);
        document.getElementById('detail-kembalian').textContent  = 'Rp ' + fmt(t.kembalian ?? 0);

    } catch (err) {
        sembunyikanModal('modal-detail');
        showToast('error', 'Gagal memuat detail', err.response?.data?.message || '');
    }
}

function tutupModalDetail() { sembunyikanModal('modal-detail'); }

// ── Utilitas ──────────────────────────────────────────────────────────────────
function tampilkanModal(id) { const el = document.getElementById(id); el.classList.remove('hidden'); el.classList.add('flex'); }
function sembunyikanModal(id) { const el = document.getElementById(id); el.classList.add('hidden'); el.classList.remove('flex'); }

function fmt(n) { return new Intl.NumberFormat('id-ID').format(n ?? 0); }
function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function formatTgl(str) {
    if (!str) return '—';
    try {
        return new Date(str).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch (_) { return str; }
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', init);
</script>
@endpush
