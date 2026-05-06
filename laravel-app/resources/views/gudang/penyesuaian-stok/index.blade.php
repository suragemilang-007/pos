@extends('layouts.app')

@section('title', 'Penyesuaian Stok')
@section('page-title', 'Penyesuaian Stok')

@section('content')
<div class="space-y-5">

    {{-- ── Statistik Ringkas ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Hari Ini</p>
            <p id="stat-total" class="text-2xl font-bold text-gray-900 dark:text-gray-100">—</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Pengurangan Stok</p>
            <p id="stat-kurang" class="text-2xl font-bold text-red-600 dark:text-red-400">—</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-1">Penambahan Stok</p>
            <p id="stat-tambah" class="text-2xl font-bold text-green-600 dark:text-green-400">—</p>
        </div>
    </div>

    {{-- ── Filter & Aksi ────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">

            {{-- Cari produk --}}
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input id="filter-produk" type="text" placeholder="Cari nama produk..."
                    class="block w-full pl-9 pr-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    oninput="filterDebounce()">
            </div>

            {{-- Filter tipe --}}
            <select id="filter-tipe"
                class="sm:w-52 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadData(1)">
                <option value="">Semua Tipe</option>
                <option value="barang_rusak">Barang Rusak</option>
                <option value="barang_hilang">Barang Hilang</option>
                <option value="barang_expired">Barang Expired</option>
                <option value="koreksi_plus">Koreksi Tambah</option>
                <option value="koreksi_minus">Koreksi Kurang</option>
            </select>

            {{-- Filter tanggal --}}
            <input id="filter-dari" type="date"
                class="sm:w-40 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadData(1)">

            <input id="filter-sampai" type="date"
                class="sm:w-40 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadData(1)">

            {{-- Tombol Buat Penyesuaian --}}
            <button onclick="bukaModal()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 shrink-0 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Penyesuaian
            </button>
        </div>
    </div>

    {{-- ── Tabel Riwayat ────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Skeleton --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 7; $i++)
            <div class="animate-pulse flex gap-4">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-24"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/8"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-20 ml-auto"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipe</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Qty</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keterangan</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Oleh</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="tbody-data" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            {{-- Empty --}}
            <div id="tbl-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada riwayat penyesuaian stok</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Buat penyesuaian pertama dengan tombol di atas</p>
            </div>
        </div>

        {{-- Pagination --}}
        <div id="pagination" class="hidden px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p id="pagination-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div id="pagination-btns" class="flex gap-1"></div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     MODAL — Buat Penyesuaian Stok
════════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-penyesuaian" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="tutupModal()"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg">

            {{-- Header --}}
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Buat Penyesuaian Stok</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Catat perubahan stok karena kerusakan, kehilangan, expired, atau koreksi opname</p>
                </div>
                <button onclick="tutupModal()" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <form id="form-penyesuaian" onsubmit="submitPenyesuaian(event)" class="p-5 space-y-4">

                {{-- Alert error --}}
                <div id="alert-error" class="hidden p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p id="alert-error-msg" class="text-sm text-red-700 dark:text-red-400"></p>
                </div>

                {{-- Panel preview dampak --}}
                <div id="preview-panel" class="hidden p-3 rounded-lg border">
                    <div class="flex items-start gap-3">
                        <div id="preview-icon" class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"></div>
                        <div>
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-0.5">Dampak Penyesuaian</p>
                            <p id="preview-text" class="text-sm font-medium text-gray-900 dark:text-gray-100"></p>
                            <p id="preview-stok" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></p>
                        </div>
                    </div>
                </div>

                {{-- Pilih Produk --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Produk <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input id="input-produk-search" type="text" placeholder="Ketik nama atau kode produk..."
                            autocomplete="off"
                            class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                            oninput="cariProduk(this.value)">
                        <div id="dropdown-produk"
                            class="hidden absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                        </div>
                    </div>
                    <input id="input-produk-id" type="hidden" name="produk_id">
                    <p id="info-stok-sekarang" class="text-xs text-gray-500 dark:text-gray-400 mt-1 hidden"></p>
                </div>

                {{-- Tipe Penyesuaian --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Tipe Penyesuaian <span class="text-red-500">*</span>
                    </label>
                    <select id="input-tipe" name="tipe"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                        onchange="updatePreview()">
                        <option value="">Pilih tipe...</option>
                        <optgroup label="Pengurangan Stok">
                            <option value="barang_rusak">Barang Rusak</option>
                            <option value="barang_hilang">Barang Hilang</option>
                            <option value="barang_expired">Barang Expired / Kadaluarsa</option>
                            <option value="koreksi_minus">Koreksi Kurang (Opname)</option>
                        </optgroup>
                        <optgroup label="Penambahan Stok">
                            <option value="koreksi_plus">Koreksi Tambah (Opname)</option>
                        </optgroup>
                    </select>
                    <p id="tipe-hint" class="text-xs text-gray-400 dark:text-gray-500 mt-1"></p>
                </div>

                {{-- Qty --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Jumlah (satuan dasar) <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2 items-center">
                        <input id="input-qty" type="number" name="qty_satuan_dasar" min="1" placeholder="0"
                            class="w-36 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                            oninput="updatePreview()">
                        <span id="label-satuan-dasar" class="text-sm text-gray-500 dark:text-gray-400">—</span>
                    </div>
                    <p id="qty-hint" class="text-xs text-gray-400 dark:text-gray-500 mt-1">Isi dengan angka positif. Arah stok ditentukan oleh tipe.</p>
                </div>

                {{-- Keterangan --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Keterangan <span class="text-red-500">*</span>
                        <span class="text-xs font-normal text-gray-400">(min. 5 karakter)</span>
                    </label>
                    <textarea id="input-keterangan" name="keterangan" rows="3" placeholder="Jelaskan alasan penyesuaian stok secara singkat..."
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors resize-none"></textarea>
                    <p id="char-count" class="text-xs text-gray-400 dark:text-gray-500 mt-1">0 karakter</p>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                        Batal
                    </button>
                    <button type="submit" id="btn-submit"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg id="btn-spinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Simpan Penyesuaian
                    </button>
                </div>
            </form>
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
let selectedProduk = null; // { id, nama_produk, satuan_dasar, qty_stok }
let produkCache = [];

const TIPE_LABEL = {
    barang_rusak:   'Barang Rusak',
    barang_hilang:  'Barang Hilang',
    barang_expired: 'Barang Expired',
    koreksi_plus:   'Koreksi Tambah',
    koreksi_minus:  'Koreksi Kurang',
};

const TIPE_HINT = {
    barang_rusak:   'Stok akan dikurangi. Gunakan untuk barang rusak di gudang (bukan dari supplier — gunakan Retur Pembelian).',
    barang_hilang:  'Stok akan dikurangi. Gunakan ketika barang tidak ditemukan saat opname.',
    barang_expired: 'Stok akan dikurangi. Gunakan untuk barang yang sudah melewati tanggal kadaluarsa.',
    koreksi_minus:  'Stok akan dikurangi. Gunakan untuk mengoreksi selisih saat stok sistem > stok fisik.',
    koreksi_plus:   'Stok akan ditambah. Gunakan untuk mengoreksi selisih saat stok sistem < stok fisik.',
};

const TIPE_KURANG = new Set(['barang_rusak', 'barang_hilang', 'barang_expired', 'koreksi_minus']);

// ─────────────────────────────────────────────────────────────────────────────
// LOAD DATA
// ─────────────────────────────────────────────────────────────────────────────
async function loadData(page = 1) {
    currentPage = page;
    showSkeleton(true);

    const token = localStorage.getItem('auth_token');
    const params = new URLSearchParams({
        page,
        limit: 20,
    });

    const tipe  = document.getElementById('filter-tipe').value;
    const dari  = document.getElementById('filter-dari').value;
    const sampai = document.getElementById('filter-sampai').value;
    if (tipe)   params.set('tipe', tipe);
    if (dari)   params.set('dari', dari);
    if (sampai) params.set('sampai', sampai);

    try {
        const res = await axios.get(`/api/gudang/penyesuaian-stok?${params}`, {
            headers: { Authorization: `Bearer ${token}` },
        });

        const { data, pagination } = res.data;
        renderTable(data);
        renderPagination(pagination);
        updateStat(data);
    } catch (err) {
        console.error('Gagal memuat data:', err);
        showEmpty();
    } finally {
        showSkeleton(false);
    }
}

function filterDebounce() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => loadData(1), 400);
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

    tbody.innerHTML = data.map(item => {
        const isKurang = TIPE_KURANG.has(item.tipe);
        const tipeCls  = isKurang
            ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
            : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400';
        const arahIcon = isKurang
            ? `<svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>`
            : `<svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>`;

        const statusCls = item.status === 'COMPLETED'
            ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
            : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400';

        const tanggal = item.created_at
            ? new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
            : '—';

        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">${tanggal}</td>
            <td class="px-4 py-3">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${item.produk?.nama_produk ?? '—'}</div>
                <div class="text-xs text-gray-400 dark:text-gray-500">${item.produk?.kode_produk ?? ''}</div>
            </td>
            <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ${tipeCls}">
                    ${arahIcon} ${TIPE_LABEL[item.tipe] ?? item.tipe}
                </span>
            </td>
            <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100 text-right whitespace-nowrap">
                ${isKurang ? '<span class="text-red-600">-</span>' : '<span class="text-green-600">+</span>'}${item.qty_satuan_dasar} <span class="text-xs font-normal text-gray-400">${item.produk?.satuan_dasar ?? ''}</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 max-w-xs">
                <span class="line-clamp-2">${escapeHtml(item.keterangan ?? '')}</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">${escapeHtml(item.nama_user ?? '—')}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium ${statusCls}">
                    ${item.status === 'COMPLETED' ? 'Selesai' : 'Pending'}
                </span>
            </td>
        </tr>`;
    }).join('');
}

function updateStat(data) {
    const total = data.length;
    const kurang = data.filter(d => TIPE_KURANG.has(d.tipe)).length;
    const tambah = total - kurang;
    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-kurang').textContent = kurang;
    document.getElementById('stat-tambah').textContent = tambah;
}

function renderPagination(pg) {
    const info = document.getElementById('pagination-info');
    const btns = document.getElementById('pagination-btns');

    info.textContent = `Halaman ${pg.current_page} dari ${pg.last_page} (${pg.total} data)`;

    let html = '';
    if (pg.current_page > 1) {
        html += `<button onclick="loadData(${pg.current_page - 1})"
            class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            ← Prev
        </button>`;
    }
    if (pg.current_page < pg.last_page) {
        html += `<button onclick="loadData(${pg.current_page + 1})"
            class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            Next →
        </button>`;
    }
    btns.innerHTML = html;
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
// MODAL
// ─────────────────────────────────────────────────────────────────────────────
function bukaModal() {
    resetModal();
    document.getElementById('modal-penyesuaian').classList.remove('hidden');
    document.getElementById('input-produk-search').focus();
}

function tutupModal() {
    document.getElementById('modal-penyesuaian').classList.add('hidden');
}

function resetModal() {
    selectedProduk = null;
    document.getElementById('input-produk-search').value = '';
    document.getElementById('input-produk-id').value = '';
    document.getElementById('input-tipe').value = '';
    document.getElementById('input-qty').value = '';
    document.getElementById('input-keterangan').value = '';
    document.getElementById('info-stok-sekarang').classList.add('hidden');
    document.getElementById('preview-panel').classList.add('hidden');
    document.getElementById('alert-error').classList.add('hidden');
    document.getElementById('dropdown-produk').classList.add('hidden');
    document.getElementById('label-satuan-dasar').textContent = '—';
    document.getElementById('tipe-hint').textContent = '';
    document.getElementById('char-count').textContent = '0 karakter';
}

// ─────────────────────────────────────────────────────────────────────────────
// CARI PRODUK (autocomplete)
// ─────────────────────────────────────────────────────────────────────────────
let produkTimer = null;
async function cariProduk(query) {
    clearTimeout(produkTimer);
    const dropdown = document.getElementById('dropdown-produk');

    if (query.length < 2) {
        dropdown.classList.add('hidden');
        return;
    }

    produkTimer = setTimeout(async () => {
        try {
            const token = localStorage.getItem('auth_token');
            const res = await axios.get(`/api/gudang/produk?nama=${encodeURIComponent(query)}&status=aktif&limit=20`, {
                headers: { Authorization: `Bearer ${token}` },
            });

            const items = res.data.data ?? [];
            if (!items.length) {
                dropdown.innerHTML = `<div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">Produk tidak ditemukan</div>`;
            } else {
                dropdown.innerHTML = items.map(p => `
                    <button type="button"
                        class="w-full text-left px-4 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        onclick="pilihProduk(${p.id}, '${escapeHtml(p.nama_produk)}', '${escapeHtml(p.satuan_dasar)}', ${p.qty_stok})">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${escapeHtml(p.nama_produk)}</div>
                        <div class="text-xs text-gray-400">${escapeHtml(p.kode_produk)} · Stok: ${p.qty_stok} ${escapeHtml(p.satuan_dasar)}</div>
                    </button>
                `).join('');
            }
            dropdown.classList.remove('hidden');
        } catch (err) {
            console.error('Gagal cari produk:', err);
        }
    }, 300);
}

function pilihProduk(id, nama, satuanDasar, qtyStok) {
    selectedProduk = { id, nama_produk: nama, satuan_dasar: satuanDasar, qty_stok: qtyStok };
    document.getElementById('input-produk-search').value = nama;
    document.getElementById('input-produk-id').value = id;
    document.getElementById('dropdown-produk').classList.add('hidden');
    document.getElementById('label-satuan-dasar').textContent = satuanDasar;
    document.getElementById('info-stok-sekarang').textContent = `Stok saat ini: ${qtyStok} ${satuanDasar}`;
    document.getElementById('info-stok-sekarang').classList.remove('hidden');
    updatePreview();
}

// ─────────────────────────────────────────────────────────────────────────────
// PREVIEW PANEL
// ─────────────────────────────────────────────────────────────────────────────
function updatePreview() {
    const tipe = document.getElementById('input-tipe').value;
    const qty  = parseInt(document.getElementById('input-qty').value) || 0;
    const panel = document.getElementById('preview-panel');
    const hint  = document.getElementById('tipe-hint');

    hint.textContent = tipe ? (TIPE_HINT[tipe] ?? '') : '';

    // Update char count keterangan
    const keterangan = document.getElementById('input-keterangan').value;
    document.getElementById('char-count').textContent = `${keterangan.length} karakter`;

    if (!tipe || qty <= 0 || !selectedProduk) {
        panel.classList.add('hidden');
        return;
    }

    const isKurang = TIPE_KURANG.has(tipe);
    const stokBaru = isKurang
        ? selectedProduk.qty_stok - qty
        : selectedProduk.qty_stok + qty;

    const iconBg = isKurang ? 'bg-red-100 dark:bg-red-900/30' : 'bg-green-100 dark:bg-green-900/30';
    const iconColor = isKurang ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
    const borderColor = isKurang ? 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/10'
                                 : 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/10';
    const icon = isKurang
        ? `<svg class="w-4 h-4 ${iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>`
        : `<svg class="w-4 h-4 ${iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>`;

    panel.className = `p-3 rounded-lg border ${borderColor}`;
    document.getElementById('preview-icon').className = `w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${iconBg}`;
    document.getElementById('preview-icon').innerHTML = icon;
    document.getElementById('preview-text').textContent =
        `Stok ${selectedProduk.nama_produk} akan ${isKurang ? 'berkurang' : 'bertambah'} ${qty} ${selectedProduk.satuan_dasar}`;

    const stokWarning = stokBaru < 0
        ? ' ⚠ Stok akan menjadi negatif — tidak diperbolehkan!'
        : '';
    document.getElementById('preview-stok').textContent =
        `${selectedProduk.qty_stok} → ${stokBaru} ${selectedProduk.satuan_dasar}${stokWarning}`;
    document.getElementById('preview-stok').className =
        `text-xs mt-0.5 ${stokBaru < 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500 dark:text-gray-400'}`;

    panel.classList.remove('hidden');
}

// ─────────────────────────────────────────────────────────────────────────────
// SUBMIT
// ─────────────────────────────────────────────────────────────────────────────
async function submitPenyesuaian(e) {
    e.preventDefault();

    const produkId    = document.getElementById('input-produk-id').value;
    const tipe        = document.getElementById('input-tipe').value;
    const qty         = document.getElementById('input-qty').value;
    const keterangan  = document.getElementById('input-keterangan').value;

    document.getElementById('alert-error').classList.add('hidden');

    // Client-side validasi dasar
    if (!produkId) return showError('Pilih produk terlebih dahulu.');
    if (!tipe)     return showError('Pilih tipe penyesuaian.');
    if (!qty || parseInt(qty) < 1) return showError('Qty harus minimal 1.');
    if (!keterangan || keterangan.length < 5) return showError('Keterangan minimal 5 karakter.');

    // Cegah stok negatif di sisi klien (server juga validasi)
    if (selectedProduk && TIPE_KURANG.has(tipe) && parseInt(qty) > selectedProduk.qty_stok) {
        return showError(`Qty melebihi stok tersedia (${selectedProduk.qty_stok} ${selectedProduk.satuan_dasar}).`);
    }

    setBtnLoading(true);

    try {
        const token = localStorage.getItem('auth_token');
        await axios.post('/api/gudang/penyesuaian-stok', {
            produk_id:        parseInt(produkId),
            tipe,
            qty_satuan_dasar: parseInt(qty),
            keterangan,
        }, {
            headers: { Authorization: `Bearer ${token}` },
        });

        tutupModal();
        showToast('Penyesuaian stok sedang diproses...', 'info');
        loadData(currentPage);

    } catch (err) {
        const msg = err.response?.data?.message
            ?? err.response?.data?.errors
            ?? 'Gagal menyimpan penyesuaian stok.';
        showError(typeof msg === 'object' ? Object.values(msg).flat().join(' ') : msg);
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
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function setBtnLoading(loading) {
    const btn = document.getElementById('btn-submit');
    const spinner = document.getElementById('btn-spinner');
    btn.disabled = loading;
    spinner.classList.toggle('hidden', !loading);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function showToast(msg, type = 'success') {
    if (typeof window.showToast === 'function') {
        window.showToast(msg, type);
    }
}

// Tutup dropdown produk ketika klik di luar
document.addEventListener('click', e => {
    const dropdown = document.getElementById('dropdown-produk');
    if (!e.target.closest('#input-produk-search') && !e.target.closest('#dropdown-produk')) {
        dropdown?.classList.add('hidden');
    }
});

// Update char count keterangan
document.getElementById('input-keterangan')?.addEventListener('input', function () {
    document.getElementById('char-count').textContent = `${this.value.length} karakter`;
    updatePreview();
});

// ─────────────────────────────────────────────────────────────────────────────
// WEBSOCKET — real-time update setelah adjustment.completed
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('ws-event', e => {
    const { event: wsEvent, message } = e.detail ?? {};

    if (wsEvent === 'adjustment.completed') {
        showToast(message ?? 'Penyesuaian stok berhasil diproses!', 'success');
        loadData(currentPage); // refresh tabel untuk update status COMPLETED
    }

    if (wsEvent === 'adjustment.failed') {
        showToast(e.detail.message ?? 'Penyesuaian stok gagal diproses.', 'error');
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Set default filter tanggal hari ini
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('filter-dari').value = today;
    document.getElementById('filter-sampai').value = today;

    loadData(1);
});
</script>
@endpush
