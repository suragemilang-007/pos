@extends('layouts.app')

@section('title', 'Data Supplier')
@section('page-title', 'Data Supplier')

@section('content')
<div class="space-y-5">

    {{-- ── Filter & Aksi ────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">

            {{-- Search nama --}}
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input id="filter-search" type="text" placeholder="Cari nama supplier..."
                    class="block w-full pl-9 pr-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    oninput="filterDebounce()">
            </div>

            {{-- Filter status --}}
            <select id="filter-status"
                class="sm:w-40 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadSupplier(1)">
                <option value="">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>

            {{-- Tombol Tambah --}}
            <button onclick="bukaModalTambah()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 shrink-0 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Supplier
            </button>
        </div>
    </div>

    {{-- ── Tabel Supplier ───────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Skeleton loading --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 6; $i++)
            <div class="animate-pulse flex gap-4">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/8"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-16"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-lg w-32 ml-auto"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama Supplier</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kontak Person</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No. Telp</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alamat</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-supplier" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            {{-- Empty state --}}
            <div id="tbl-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada supplier ditemukan.</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Coba ubah filter atau tambah supplier baru.</p>
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
     MODAL TAMBAH / EDIT SUPPLIER
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-form"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 id="modal-form-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">Tambah Supplier</h3>
            <button onclick="tutupModalForm()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            {{-- Nama Supplier --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nama Supplier <span class="text-red-500">*</span>
                </label>
                <input id="input-nama" type="text" maxlength="100" autocomplete="off"
                    placeholder="Contoh: PT Sinar Makmur"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    oninput="clearErr('err-nama','input-nama')">
                <p id="err-nama" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Kontak Person (opsional) --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Kontak Person
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">(opsional)</span>
                </label>
                <input id="input-kontak" type="text" maxlength="100" autocomplete="off"
                    placeholder="Nama PIC yang bisa dihubungi"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
            </div>

            {{-- No. Telp --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    No. Telp <span class="text-red-500">*</span>
                </label>
                <input id="input-telp" type="text" maxlength="20" autocomplete="off"
                    placeholder="Contoh: 0812-3456-7890"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    oninput="clearErr('err-telp','input-telp')">
                <p id="err-telp" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Alamat (opsional) --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Alamat
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">(opsional)</span>
                </label>
                <textarea id="input-alamat" rows="3" maxlength="500"
                    placeholder="Alamat lengkap supplier..."
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors resize-none"></textarea>
            </div>

        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalForm()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-simpan" onclick="simpanSupplier()"
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

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL KONFIRMASI HAPUS / NONAKTIFKAN
     Pesan berbeda berdasarkan has_pembelian yang dikembalikan API:
     - has_pembelian = true  → soft delete (nonaktifkan)
     - has_pembelian = false → hard delete (hapus permanen)
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-hapus"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 id="hapus-judul" class="text-base font-semibold text-gray-900 dark:text-gray-100">Hapus Supplier</h3>
        </div>

        <div class="px-6 py-5">
            <div class="flex items-start gap-3">
                {{-- Ikon berubah tergantung tipe aksi --}}
                <div id="hapus-icon-wrap" class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
                    <svg id="hapus-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p id="hapus-pesan" class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed"></p>
                    <p id="hapus-sub" class="text-xs text-gray-400 dark:text-gray-500 mt-2 leading-relaxed"></p>
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
                <span id="lbl-hapus-ok">Ya, Lanjutkan</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let supplierList  = [];
let currentPage   = 1;
let editingId     = null;
let hapusTarget   = null;   // { id, nama_supplier, has_pembelian }
let filterTimer   = null;

// ── Load Supplier ─────────────────────────────────────────────────────────────
async function loadSupplier(page = 1) {
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
        const { data } = await axios.get('/api/gudang/supplier?' + params.toString());
        supplierList = data.data || [];
        renderTabel(supplierList, data.pagination);
    } catch (err) {
        showToast('error', 'Gagal memuat data', err.response?.data?.message || 'Periksa koneksi');
        document.getElementById('tbl-skeleton').classList.add('hidden');
        document.getElementById('tbl-wrapper').classList.remove('hidden');
    }
}

function renderTabel(rows, pagination) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');

    const tbody = document.getElementById('tbody-supplier');
    const empty = document.getElementById('tbl-empty');

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        document.getElementById('pagination-wrapper').classList.add('hidden');
        return;
    }

    empty.classList.add('hidden');

    tbody.innerHTML = rows.map(s => {
        const statusBadge = s.status === 'aktif'
            ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Aktif</span>`
            : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Nonaktif</span>`;

        const alamatTampil = s.alamat
            ? `<span class="text-sm text-gray-600 dark:text-gray-400 line-clamp-1 max-w-xs" title="${esc(s.alamat)}">${esc(s.alamat)}</span>`
            : `<span class="text-xs text-gray-400 dark:text-gray-500">—</span>`;

        const kontakTampil = s.kontak_person
            ? `<span class="text-sm text-gray-700 dark:text-gray-300">${esc(s.kontak_person)}</span>`
            : `<span class="text-xs text-gray-400 dark:text-gray-500">—</span>`;

        // Label tombol hapus/nonaktif berdasarkan riwayat pembelian
        // has_pembelian disertakan di response API agar view bisa tampilkan pesan yang tepat
        const labelAksiHapus = s.has_pembelian
            ? `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
               </svg> Nonaktifkan`
            : `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
               </svg> Hapus`;

        const warnaBtnHapus = s.has_pembelian
            ? 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30'
            : 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30';

        // Tombol aksi hapus/nonaktif hanya tampil jika supplier masih aktif
        const btnHapus = s.status === 'aktif'
            ? `<button onclick="bukaModalHapus(${s.id})"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium ${warnaBtnHapus} rounded-lg transition-colors">
                ${labelAksiHapus}
               </button>`
            : '';

        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">${esc(s.nama_supplier)}</p>
                        ${s.has_pembelian
                            ? `<p class="text-xs text-indigo-500 dark:text-indigo-400">Punya riwayat pembelian</p>`
                            : `<p class="text-xs text-gray-400 dark:text-gray-500">Belum ada pembelian</p>`}
                    </div>
                </div>
            </td>
            <td class="px-4 py-3">${kontakTampil}</td>
            <td class="px-4 py-3">
                <span class="text-sm font-mono text-gray-700 dark:text-gray-300">${esc(s.no_telp)}</span>
            </td>
            <td class="px-4 py-3">${alamatTampil}</td>
            <td class="px-4 py-3 text-center">${statusBadge}</td>
            <td class="px-4 py-3 text-right">
                <div class="inline-flex items-center gap-1">
                    <button onclick="bukaModalEdit(${s.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </button>
                    ${btnHapus}
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
        `Menampilkan ${Math.min((pg.current_page - 1) * pg.per_page + 1, pg.total)}–${Math.min(pg.current_page * pg.per_page, pg.total)} dari ${pg.total} supplier`;

    const container = document.getElementById('pagination-btns');
    const pages = [];
    for (let i = 1; i <= pg.last_page; i++) {
        if (i === 1 || i === pg.last_page || Math.abs(i - pg.current_page) <= 1) pages.push(i);
        else if (pages[pages.length - 1] !== '...') pages.push('...');
    }

    container.innerHTML = pages.map(p =>
        p === '...'
            ? `<span class="px-2 py-1 text-xs text-gray-400">…</span>`
            : `<button onclick="loadSupplier(${p})"
                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${p === pg.current_page
                    ? 'bg-indigo-600 text-white'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'}">${p}</button>`
    ).join('');
}

// ── Modal Tambah ──────────────────────────────────────────────────────────────
function bukaModalTambah() {
    editingId = null;
    document.getElementById('modal-form-title').textContent = 'Tambah Supplier';
    document.getElementById('lbl-simpan').textContent       = 'Simpan';

    document.getElementById('input-nama').value   = '';
    document.getElementById('input-kontak').value = '';
    document.getElementById('input-telp').value   = '';
    document.getElementById('input-alamat').value = '';

    bersihkanSemuaErr();
    tampilkanModal('modal-form');
    setTimeout(() => document.getElementById('input-nama').focus(), 100);
}

// ── Modal Edit ────────────────────────────────────────────────────────────────
function bukaModalEdit(id) {
    const s = supplierList.find(s => s.id === id);
    if (!s) return;

    editingId = id;
    document.getElementById('modal-form-title').textContent = 'Edit Supplier';
    document.getElementById('lbl-simpan').textContent       = 'Perbarui';

    document.getElementById('input-nama').value   = s.nama_supplier;
    document.getElementById('input-kontak').value = s.kontak_person || '';
    document.getElementById('input-telp').value   = s.no_telp;
    document.getElementById('input-alamat').value = s.alamat || '';

    bersihkanSemuaErr();
    tampilkanModal('modal-form');
    setTimeout(() => document.getElementById('input-nama').focus(), 100);
}

function tutupModalForm() {
    sembunyikanModal('modal-form');
    editingId = null;
}

async function simpanSupplier() {
    const nama    = document.getElementById('input-nama').value.trim();
    const kontak  = document.getElementById('input-kontak').value.trim();
    const telp    = document.getElementById('input-telp').value.trim();
    const alamat  = document.getElementById('input-alamat').value.trim();

    bersihkanSemuaErr();

    let ok = true;
    if (!nama) { tampilkanErr('err-nama', 'input-nama', 'Nama supplier wajib diisi'); ok = false; }
    if (!telp) { tampilkanErr('err-telp', 'input-telp', 'No. Telp wajib diisi'); ok = false; }
    if (!ok) return;

    setSimpanLoading(true);

    const payload = {
        nama_supplier:  nama,
        kontak_person:  kontak  || null,
        no_telp:        telp,
        alamat:         alamat  || null,
    };

    try {
        if (editingId) {
            await axios.put(`/api/gudang/supplier/${editingId}`, payload);
            showToast('success', 'Supplier Diperbarui', `"${nama}" berhasil diperbarui.`);
        } else {
            await axios.post('/api/gudang/supplier', payload);
            showToast('success', 'Supplier Ditambahkan', `"${nama}" berhasil ditambahkan.`);
        }
        tutupModalForm();
        await loadSupplier(editingId ? currentPage : 1);
    } catch (err) {
        const errors = err.response?.data?.errors;
        if (errors?.no_telp?.[0])        tampilkanErr('err-telp',  'input-telp',  errors.no_telp[0]);
        else if (errors?.nama_supplier?.[0]) tampilkanErr('err-nama', 'input-nama', errors.nama_supplier[0]);
        else showToast('error', 'Gagal menyimpan', err.response?.data?.message || 'Terjadi kesalahan');
    } finally {
        setSimpanLoading(false);
    }
}

function setSimpanLoading(on) {
    document.getElementById('btn-simpan').disabled = on;
    document.getElementById('spinner-simpan').classList.toggle('hidden', !on);
    document.getElementById('lbl-simpan').textContent = on
        ? 'Menyimpan...'
        : (editingId ? 'Perbarui' : 'Simpan');
}

// ── Modal Hapus / Nonaktifkan ─────────────────────────────────────────────────
function bukaModalHapus(id) {
    const s = supplierList.find(s => s.id === id);
    if (!s) return;

    hapusTarget = s;

    const btnOk        = document.getElementById('btn-hapus-ok');
    const iconWrap     = document.getElementById('hapus-icon-wrap');
    const hapusJudul   = document.getElementById('hapus-judul');
    const hapusPesan   = document.getElementById('hapus-pesan');
    const hapusSub     = document.getElementById('hapus-sub');
    const lblOk        = document.getElementById('lbl-hapus-ok');

    if (s.has_pembelian) {
        // Supplier pernah dipakai di pembelian → soft delete (nonaktifkan)
        hapusJudul.textContent = 'Nonaktifkan Supplier';
        iconWrap.className = 'shrink-0 w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center';
        document.getElementById('hapus-icon').className = 'w-5 h-5 text-amber-600 dark:text-amber-400';
        hapusPesan.innerHTML =
            `Supplier <strong class="text-gray-900 dark:text-gray-100">"${esc(s.nama_supplier)}"</strong> ` +
            `akan <strong class="text-amber-700 dark:text-amber-400">dinonaktifkan</strong> karena memiliki riwayat pembelian.`;
        hapusSub.textContent = 'Data riwayat pembelian tetap tersimpan dan bisa dilihat kapan saja. Supplier dapat diaktifkan kembali.';
        btnOk.className = 'inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed';
        lblOk.textContent = 'Ya, Nonaktifkan';
    } else {
        // Supplier belum pernah dipakai → hard delete
        hapusJudul.textContent = 'Hapus Supplier';
        iconWrap.className = 'shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center';
        document.getElementById('hapus-icon').className = 'w-5 h-5 text-red-600 dark:text-red-400';
        hapusPesan.innerHTML =
            `Supplier <strong class="text-gray-900 dark:text-gray-100">"${esc(s.nama_supplier)}"</strong> ` +
            `akan <strong class="text-red-700 dark:text-red-400">dihapus permanen</strong> dari sistem.`;
        hapusSub.textContent = 'Tindakan ini tidak dapat dibatalkan. Data supplier akan hilang sepenuhnya.';
        btnOk.className = 'inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed';
        lblOk.textContent = 'Ya, Hapus Permanen';
    }

    tampilkanModal('modal-hapus');
}

function tutupModalHapus() {
    sembunyikanModal('modal-hapus');
    hapusTarget = null;
}

async function eksekusiHapus() {
    if (!hapusTarget) return;

    const btn = document.getElementById('btn-hapus-ok');
    btn.disabled = true;
    document.getElementById('spinner-hapus').classList.remove('hidden');
    document.getElementById('lbl-hapus-ok').textContent = 'Memproses...';

    try {
        await axios.delete(`/api/gudang/supplier/${hapusTarget.id}`);

        if (hapusTarget.has_pembelian) {
            showToast('warning', 'Supplier Dinonaktifkan',
                `"${hapusTarget.nama_supplier}" berhasil dinonaktifkan.`);
        } else {
            showToast('success', 'Supplier Dihapus',
                `"${hapusTarget.nama_supplier}" berhasil dihapus dari sistem.`);
        }

        tutupModalHapus();
        await loadSupplier(currentPage);
    } catch (err) {
        showToast('error', 'Gagal', err.response?.data?.message || 'Terjadi kesalahan');
        tutupModalHapus();
    } finally {
        btn.disabled = false;
        document.getElementById('spinner-hapus').classList.add('hidden');
    }
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

// ── Utilitas Error Form ───────────────────────────────────────────────────────
function tampilkanErr(errId, inputId, msg) {
    const e = document.getElementById(errId);
    if (!e) return;
    e.textContent = msg;
    e.classList.remove('hidden');
    document.getElementById(inputId)?.classList.add('border-red-500', 'dark:border-red-400');
}

function clearErr(errId, inputId) {
    const e = document.getElementById(errId);
    e?.classList.add('hidden');
    document.getElementById(inputId)?.classList.remove('border-red-500', 'dark:border-red-400');
}

function bersihkanSemuaErr() {
    ['err-nama', 'err-telp'].forEach(id => {
        const e = document.getElementById(id);
        e?.classList.add('hidden');
        e && (e.textContent = '');
    });
    ['input-nama', 'input-telp'].forEach(id => {
        document.getElementById(id)?.classList.remove('border-red-500', 'dark:border-red-400');
    });
}

// ── Filter Debounce ───────────────────────────────────────────────────────────
function filterDebounce() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => loadSupplier(1), 400);
}

// ── Klik backdrop tutup modal ─────────────────────────────────────────────────
['modal-form', 'modal-hapus'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function (e) {
        if (e.target === this) sembunyikanModal(id);
    });
});

// ── Escape tutup modal aktif ──────────────────────────────────────────────────
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    ['modal-hapus', 'modal-form'].forEach(id => {
        const el = document.getElementById(id);
        if (el && !el.classList.contains('hidden')) sembunyikanModal(id);
    });
});

// ── Escape di form → submit dengan Enter (kecuali textarea) ──────────────────
document.addEventListener('DOMContentLoaded', function () {
    ['input-nama', 'input-kontak', 'input-telp'].forEach(id => {
        document.getElementById(id)?.addEventListener('keydown', e => {
            if (e.key === 'Enter') simpanSupplier();
        });
    });

    loadSupplier(1);
});

// ── Utilitas XSS-safe escaping ────────────────────────────────────────────────
function esc(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
</script>
@endpush
