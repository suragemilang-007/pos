@extends('layouts.app')

@section('title', 'Data Customer')
@section('page-title', 'Data Customer')

@section('content')
<div class="space-y-5">

    {{-- ── Filter & Aksi ────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">

            {{-- Search --}}
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input id="filter-search" type="text" placeholder="Cari nama atau no. HP..."
                    class="block w-full pl-9 pr-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    oninput="filterDebounce()">
            </div>

            {{-- Filter status --}}
            <select id="filter-status"
                class="sm:w-36 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadCustomer(1)">
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
                <option value="semua">Semua</option>
            </select>

            {{-- Tombol Tambah --}}
            <button onclick="bukaModalTambah()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 shrink-0 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Customer
            </button>
        </div>
    </div>

    {{-- ── Tabel Customer ───────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Skeleton --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 5; $i++)
            <div class="animate-pulse flex gap-4">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-28 ml-auto"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No. HP</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Tipe</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Total Belanja</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Frekuensi</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-customer" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            <div id="tbl-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada customer ditemukan.</p>
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

{{-- ══ MODAL TAMBAH / EDIT ══════════════════════════════════════════════════ --}}
<div id="modal-form"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 id="modal-form-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">Tambah Customer</h3>
            <button onclick="tutupModalForm()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">

            {{-- Nama --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nama <span class="text-red-500">*</span>
                </label>
                <input id="input-nama" type="text" maxlength="100" autocomplete="off"
                    placeholder="Nama lengkap customer"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    oninput="clearErrField('err-nama', 'input-nama')">
                <p id="err-nama" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- No Telp --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    No. HP <span class="text-red-500">*</span>
                </label>
                <input id="input-telp" type="text" maxlength="20" autocomplete="off"
                    placeholder="Contoh: 081234567890"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    oninput="clearErrField('err-telp', 'input-telp')">
                <p id="err-telp" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Tipe Customer --}}
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tipe Customer <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" id="tipe-reguler" name="tipe_customer" value="reguler" checked
                            class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Reguler</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" id="tipe-grosir" name="tipe_customer" value="grosir"
                            class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Grosir</span>
                    </label>
                </div>
            </div>

            {{-- Alamat --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Alamat
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">(opsional)</span>
                </label>
                <textarea id="input-alamat" rows="2"
                    placeholder="Alamat lengkap..."
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors resize-none"></textarea>
            </div>

        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalForm()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-simpan" onclick="simpanCustomer()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-simpan" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-simpan">Simpan</span>
            </button>
        </div>
    </div>
</div>

{{-- ══ MODAL KONFIRMASI NONAKTIFKAN ════════════════════════════════════════ --}}
<div id="modal-nonaktif"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Nonaktifkan Customer</h3>
        </div>

        <div class="px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="shrink-0 w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p id="nonaktif-pesan" class="text-sm text-gray-600 dark:text-gray-400"></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                        Riwayat belanjanya tetap tersimpan dan dapat dilihat kapan saja.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalNonaktif()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-nonaktif-ok" onclick="eksekusiNonaktif()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50">
                <svg id="spinner-nonaktif" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-nonaktif">Ya, Nonaktifkan</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let customerList    = [];
let editingId       = null;
let nonaktifTarget  = null;
let currentPage     = 1;
let filterTimer     = null;

// ── Load Customer ─────────────────────────────────────────────────────────────
async function loadCustomer(page = 1) {
    currentPage = page;

    const search = document.getElementById('filter-search').value.trim();
    const status = document.getElementById('filter-status').value;

    const params = new URLSearchParams({ page, limit: 20 });
    if (search) params.set('search', search);
    if (status) params.set('status', status);

    document.getElementById('tbl-skeleton').classList.remove('hidden');
    document.getElementById('tbl-wrapper').classList.add('hidden');
    document.getElementById('pagination-wrapper').classList.add('hidden');

    try {
        const { data } = await axios.get('/api/kasir/customer?' + params.toString());
        customerList = data.data || [];
        renderTabel(customerList, data.pagination);
    } catch (err) {
        showToast('error', 'Gagal memuat data', err.response?.data?.message || 'Periksa koneksi');
        document.getElementById('tbl-skeleton').classList.add('hidden');
        document.getElementById('tbl-wrapper').classList.remove('hidden');
    }
}

function renderTabel(rows, pagination) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');

    const tbody = document.getElementById('tbody-customer');
    const empty = document.getElementById('tbl-empty');

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');
    tbody.innerHTML = rows.map(c => {
        const tipeBadge = c.tipe_customer === 'grosir'
            ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Grosir</span>`
            : `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Reguler</span>`;

        const statusBadge = c.status === 'aktif'
            ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Aktif</span>`
            : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Nonaktif</span>`;

        const totalBelanja = c.total_belanja ? 'Rp ' + fmt(c.total_belanja) : '—';
        const frekuensi    = c.frekuensi ? c.frekuensi + 'x' : '—';

        const btnNonaktif = c.status === 'aktif'
            ? `<button onclick="bukaModalNonaktif(${c.id})"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                Nonaktif
            </button>`
            : '';

        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${esc(c.nama)}</p>
                ${c.alamat ? `<p class="text-xs text-gray-400 dark:text-gray-500 truncate max-w-xs">${esc(c.alamat)}</p>` : ''}
            </td>
            <td class="px-4 py-3 font-mono text-sm text-gray-600 dark:text-gray-400">${esc(c.no_telp)}</td>
            <td class="px-4 py-3 text-center">${tipeBadge}</td>
            <td class="px-4 py-3 text-right font-mono text-sm text-gray-900 dark:text-gray-100">${totalBelanja}</td>
            <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">${frekuensi}</td>
            <td class="px-4 py-3 text-center">${statusBadge}</td>
            <td class="px-4 py-3 text-right">
                <div class="inline-flex items-center gap-1">
                    <button onclick="bukaModalEdit(${c.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </button>
                    <a href="/customer/${c.id}/riwayat"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Riwayat
                    </a>
                    ${btnNonaktif}
                </div>
            </td>
        </tr>`;
    }).join('');

    renderPagination(pagination);
}

function renderPagination(pg) {
    if (!pg || pg.total <= pg.per_page) {
        document.getElementById('pagination-wrapper').classList.add('hidden');
        return;
    }

    document.getElementById('pagination-wrapper').classList.remove('hidden');
    document.getElementById('pagination-info').textContent =
        `Menampilkan ${Math.min((pg.current_page - 1) * pg.per_page + 1, pg.total)}–${Math.min(pg.current_page * pg.per_page, pg.total)} dari ${pg.total}`;

    const container = document.getElementById('pagination-btns');
    const pages = [];
    for (let i = 1; i <= pg.last_page; i++) {
        if (i === 1 || i === pg.last_page || Math.abs(i - pg.current_page) <= 1) pages.push(i);
        else if (pages[pages.length - 1] !== '...') pages.push('...');
    }

    container.innerHTML = pages.map(p =>
        p === '...'
            ? `<span class="px-2 py-1 text-xs text-gray-400">…</span>`
            : `<button onclick="loadCustomer(${p})"
                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${p === pg.current_page
                    ? 'bg-indigo-600 text-white'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'}">${p}</button>`
    ).join('');
}

// ── Modal Tambah / Edit ───────────────────────────────────────────────────────
function bukaModalTambah() {
    editingId = null;
    document.getElementById('modal-form-title').textContent = 'Tambah Customer';
    document.getElementById('lbl-simpan').textContent       = 'Simpan';
    document.getElementById('input-nama').value    = '';
    document.getElementById('input-telp').value    = '';
    document.getElementById('input-alamat').value  = '';
    document.getElementById('tipe-reguler').checked = true;
    clearAllFormErr();
    tampilkanModal('modal-form');
    setTimeout(() => document.getElementById('input-nama').focus(), 100);
}

function bukaModalEdit(id) {
    const c = customerList.find(c => c.id === id);
    if (!c) return;

    editingId = id;
    document.getElementById('modal-form-title').textContent = 'Edit Customer';
    document.getElementById('lbl-simpan').textContent       = 'Perbarui';
    document.getElementById('input-nama').value   = c.nama;
    document.getElementById('input-telp').value   = c.no_telp;
    document.getElementById('input-alamat').value = c.alamat || '';
    document.querySelector(`input[name="tipe_customer"][value="${c.tipe_customer}"]`).checked = true;
    clearAllFormErr();
    tampilkanModal('modal-form');
    setTimeout(() => document.getElementById('input-nama').focus(), 100);
}

async function simpanCustomer() {
    const nama  = document.getElementById('input-nama').value.trim();
    const telp  = document.getElementById('input-telp').value.trim();
    const tipe  = document.querySelector('input[name="tipe_customer"]:checked')?.value || 'reguler';
    const alamat= document.getElementById('input-alamat').value.trim();

    clearAllFormErr();
    let ok = true;
    if (!nama) { tampilkanErrField('err-nama', 'input-nama', 'Nama wajib diisi'); ok = false; }
    if (!telp) { tampilkanErrField('err-telp', 'input-telp', 'No. HP wajib diisi'); ok = false; }
    if (!ok) return;

    setSimpanLoading(true);

    const payload = { nama, no_telp: telp, tipe_customer: tipe, alamat: alamat || null };

    try {
        if (editingId) {
            await axios.put(`/api/kasir/customer/${editingId}`, payload);
            showToast('success', 'Customer diperbarui', `"${nama}" berhasil diperbarui.`);
        } else {
            await axios.post('/api/kasir/customer', payload);
            showToast('success', 'Customer ditambahkan', `"${nama}" berhasil ditambahkan.`);
        }
        tutupModalForm();
        await loadCustomer(currentPage);
    } catch (err) {
        const errors = err.response?.data?.errors;
        if (errors?.no_telp?.[0]) {
            tampilkanErrField('err-telp', 'input-telp', errors.no_telp[0]);
        } else if (errors?.nama?.[0]) {
            tampilkanErrField('err-nama', 'input-nama', errors.nama[0]);
        } else {
            showToast('error', 'Gagal menyimpan', err.response?.data?.message || 'Terjadi kesalahan');
        }
    } finally {
        setSimpanLoading(false);
    }
}

function tutupModalForm() {
    sembunyikanModal('modal-form');
    editingId = null;
}

// ── Modal Nonaktifkan ─────────────────────────────────────────────────────────
function bukaModalNonaktif(id) {
    const c = customerList.find(c => c.id === id);
    if (!c) return;

    nonaktifTarget = c;
    document.getElementById('nonaktif-pesan').innerHTML =
        `Customer <strong class="text-gray-900 dark:text-gray-100">"${esc(c.nama)}"</strong> akan dinonaktifkan.`;
    tampilkanModal('modal-nonaktif');
}

async function eksekusiNonaktif() {
    if (!nonaktifTarget) return;

    const btn = document.getElementById('btn-nonaktif-ok');
    btn.disabled = true;
    document.getElementById('spinner-nonaktif').classList.remove('hidden');
    document.getElementById('lbl-nonaktif').textContent = 'Memproses...';

    try {
        await axios.delete(`/api/kasir/customer/${nonaktifTarget.id}`);
        showToast('warning', 'Customer Dinonaktifkan', `"${nonaktifTarget.nama}" telah dinonaktifkan.`);
        tutupModalNonaktif();
        await loadCustomer(currentPage);
    } catch (err) {
        showToast('error', 'Gagal', err.response?.data?.message || 'Terjadi kesalahan');
        tutupModalNonaktif();
    } finally {
        btn.disabled = false;
        document.getElementById('spinner-nonaktif').classList.add('hidden');
        document.getElementById('lbl-nonaktif').textContent = 'Ya, Nonaktifkan';
    }
}

function tutupModalNonaktif() {
    sembunyikanModal('modal-nonaktif');
    nonaktifTarget = null;
}

// ── Utilitas ──────────────────────────────────────────────────────────────────
function tampilkanModal(id) { const el = document.getElementById(id); el.classList.remove('hidden'); el.classList.add('flex'); }
function sembunyikanModal(id) { const el = document.getElementById(id); el.classList.add('hidden'); el.classList.remove('flex'); }

function tampilkanErrField(errId, inputId, msg) {
    const e = document.getElementById(errId);
    e.textContent = msg; e.classList.remove('hidden');
    document.getElementById(inputId)?.classList.add('border-red-500', 'dark:border-red-400');
}
function clearErrField(errId, inputId) {
    const e = document.getElementById(errId);
    e?.classList.add('hidden');
    document.getElementById(inputId)?.classList.remove('border-red-500', 'dark:border-red-400');
}
function clearAllFormErr() {
    ['err-nama','err-telp'].forEach(id => { const e = document.getElementById(id); e?.classList.add('hidden'); });
    ['input-nama','input-telp'].forEach(id => document.getElementById(id)?.classList.remove('border-red-500','dark:border-red-400'));
}

function setSimpanLoading(on) {
    document.getElementById('btn-simpan').disabled = on;
    document.getElementById('spinner-simpan').classList.toggle('hidden', !on);
    document.getElementById('lbl-simpan').textContent = on ? 'Menyimpan...' : (editingId ? 'Perbarui' : 'Simpan');
}

function filterDebounce() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => loadCustomer(1), 400);
}

function fmt(n) { return new Intl.NumberFormat('id-ID').format(n ?? 0); }
function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    loadCustomer(1);

    // Enter di field form → submit
    ['input-nama','input-telp','input-alamat'].forEach(id => {
        document.getElementById(id)?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && id !== 'input-alamat') simpanCustomer();
        });
    });
});
</script>
@endpush
