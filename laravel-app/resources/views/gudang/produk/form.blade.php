@extends('layouts.app')

@section('title', isset($produkId) ? 'Edit Produk' : 'Tambah Produk')
@section('page-title', isset($produkId) ? 'Edit Produk' : 'Tambah Produk')

@section('content')
<div class="space-y-6 max-w-4xl">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="/gudang/produk" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Data Produk</a>
        <span>/</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">
            {{ isset($produkId) ? 'Edit Produk' : 'Tambah Produk Baru' }}
        </span>
    </nav>

    {{-- ── BAGIAN 1: Data Utama Produk ────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Data Utama Produk</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Informasi dasar identitas produk</p>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">

            {{-- Kode Produk --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Kode Produk <span class="text-red-500">*</span>
                </label>
                <input id="input-kode" type="text" maxlength="20" autocomplete="off"
                    placeholder="Contoh: KPI-165G"
                    class="input-field font-mono"
                    onblur="validasiKode()"
                    oninput="clearErr('err-kode')">
                <p id="err-kode" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Maks 20 karakter, harus unik</p>
            </div>

            {{-- Nama Produk --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nama Produk <span class="text-red-500">*</span>
                </label>
                <input id="input-nama" type="text" maxlength="200"
                    placeholder="Contoh: Kopi Kapal Api 165g"
                    class="input-field"
                    oninput="clearErr('err-nama')">
                <p id="err-nama" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Kategori --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Kategori <span class="text-red-500">*</span>
                </label>
                <select id="input-kategori" class="input-field" onchange="clearErr('err-kategori')">
                    <option value="">-- Pilih Kategori --</option>
                </select>
                <p id="err-kategori" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                <p id="hint-kategori-kosong" class="hidden text-xs text-amber-600 dark:text-amber-400">
                    Belum ada kategori.
                    <a href="/gudang/kategori" target="_blank" class="underline font-medium">Tambah kategori dulu</a>
                </p>
            </div>

            {{-- Satuan Dasar --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Satuan Dasar <span class="text-red-500">*</span>
                </label>
                <input id="input-satuan-dasar" type="text" maxlength="30"
                    placeholder="Contoh: pcs, gram, ml, lembar"
                    class="input-field"
                    oninput="onSatuanDasarChange(); clearErr('err-satuan-dasar')">
                <p id="err-satuan-dasar" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Satuan terkecil. Stok selalu disimpan dalam satuan ini.</p>
            </div>

            {{-- Harga Beli Dasar --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Harga Beli Dasar <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400">Rp</span>
                    <input id="input-harga-beli" type="number" min="0" step="0.01"
                        placeholder="0"
                        class="input-field pl-10 font-mono"
                        oninput="clearErr('err-harga-beli')">
                </div>
                <p id="err-harga-beli" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Per 1 satuan_dasar</p>
            </div>

            {{-- Stok Minimum --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Stok Minimum <span class="text-red-500">*</span>
                </label>
                <input id="input-stok-min" type="number" min="0" value="0"
                    placeholder="0"
                    class="input-field font-mono"
                    oninput="clearErr('err-stok-min')">
                <p id="err-stok-min" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Peringatan stok rendah di bawah angka ini</p>
            </div>

            {{-- Stok Awal (hanya tambah baru) --}}
            <div id="wrapper-stok-awal" class="space-y-1 {{ isset($produkId) ? 'hidden' : '' }}">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Stok Awal
                </label>
                <input id="input-stok-awal" type="number" min="0" value="0"
                    placeholder="0"
                    class="input-field font-mono">
                <p class="text-xs text-gray-400 dark:text-gray-500">Dalam satuan dasar. Tidak bisa diubah setelah produk dibuat.</p>
            </div>

            {{-- Deskripsi (full width) --}}
            <div class="sm:col-span-2 space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Deskripsi
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">(opsional)</span>
                </label>
                <textarea id="input-deskripsi" rows="3"
                    placeholder="Keterangan tambahan tentang produk ini..."
                    class="input-field resize-none"></textarea>
            </div>

        </div>
    </div>

    {{-- ── BAGIAN 2: Kelola Satuan ──────────────────────────────────────────── --}}
    <div id="satuan-section" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Kelola Satuan</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Tepat 1 satuan harus ditandai sebagai default · Satuan = satuan_dasar harus isi = 1
                </p>
            </div>
            <button onclick="tambahBarisSatuan()"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Satuan
            </button>
        </div>

        {{-- Error validasi satuan --}}
        <div id="err-satuan-global" class="hidden mx-6 mt-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p id="err-satuan-global-msg" class="text-sm text-red-700 dark:text-red-400"></p>
        </div>

        {{-- Tabel satuan --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left min-w-[700px]">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-28">Nama Satuan</th>
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24 text-center">
                            Isi <span class="text-gray-300 dark:text-gray-600 font-normal">(× dasar)</span>
                        </th>
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Harga Beli</th>
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Harga Jual</th>
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20 text-center">Default</th>
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-28">Barcode</th>
                        <th class="px-3 py-3 w-8"></th>
                    </tr>
                </thead>
                <tbody id="tbody-satuan" class="divide-y divide-gray-200 dark:divide-gray-700">
                    {{-- Diisi JS --}}
                </tbody>
            </table>

            {{-- Empty state satuan --}}
            <div id="satuan-empty" class="hidden px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                Belum ada satuan. Klik "+ Tambah Satuan" untuk menambahkan.
            </div>
        </div>

        {{-- Skeleton loading satuan (edit mode) --}}
        <div id="satuan-skeleton" class="hidden p-6 space-y-2">
            @for($i = 0; $i < 3; $i++)
            <div class="animate-pulse flex gap-3">
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-28"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-28"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-12"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
            </div>
            @endfor
        </div>
    </div>

    {{-- ── Tombol Aksi ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between pt-2 pb-6">
        <a href="/gudang/produk"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            ← Kembali
        </a>
        <button id="btn-simpan" onclick="simpanProduk()"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed">
            <svg id="spinner-simpan" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span id="lbl-simpan">{{ isset($produkId) ? 'Simpan Perubahan' : 'Buat Produk' }}</span>
        </button>
    </div>

</div>

{{-- Modal konfirmasi hapus satuan yang sudah ada --}}
<div id="modal-hapus-satuan"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Hapus Satuan</h3>
        </div>
        <div class="px-6 py-5">
            <p id="hapus-satuan-pesan" class="text-sm text-gray-600 dark:text-gray-400"></p>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="sembunyikanModal('modal-hapus-satuan')"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-hapus-satuan-ok"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
                <svg id="spinner-hapus-satuan" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-hapus-satuan">Ya, Hapus</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
/* Input field standar — dipakai berulang di halaman ini */
.input-field {
    @apply block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-400 dark:border-gray-500 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors;
}
.input-field.error {
    @apply border-red-500 dark:border-red-400;
}
.input-sm {
    @apply block w-full px-2 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-400 dark:border-gray-500 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-colors;
}
</style>
@endpush

@push('scripts')
<script>
// ── Konstanta Mode ────────────────────────────────────────────────────────────
const PRODUK_ID = {{ isset($produkId) ? $produkId : 'null' }};
const IS_EDIT   = PRODUK_ID !== null;

// ── State ─────────────────────────────────────────────────────────────────────
let satuanRows  = [];  // {rowId, id, nama_satuan, isi, harga_beli, harga_jual, is_default, barcode, isNew, toDelete}
let rowCounter  = 0;
let hapusSatuanTarget = null; // {rowId, dbId} untuk konfirmasi hapus satuan

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async function () {
    await loadKategoriDropdown();

    if (IS_EDIT) {
        await loadProdukData();
        await loadSatuanData();
        // Scroll ke satuan section jika ada hash
        if (window.location.hash === '#satuan-section') {
            document.getElementById('satuan-section')?.scrollIntoView({ behavior: 'smooth' });
        }
    } else {
        // Tambah mode: mulai dengan 1 baris satuan default
        tambahBarisSatuan(true);
    }
});

// ── Load Dropdown Kategori ────────────────────────────────────────────────────
async function loadKategoriDropdown() {
    try {
        const { data } = await axios.get('/api/kategori');
        const sel = document.getElementById('input-kategori');
        const list = data.data || [];

        if (!list.length) {
            document.getElementById('hint-kategori-kosong').classList.remove('hidden');
            return;
        }

        list.forEach(k => {
            const opt = document.createElement('option');
            opt.value = k.id;
            opt.textContent = k.nama_kategori;
            sel.appendChild(opt);
        });
    } catch (_) {}
}

// ── Load Data Produk (edit mode) ──────────────────────────────────────────────
async function loadProdukData() {
    try {
        const { data } = await axios.get(`/api/gudang/produk/${PRODUK_ID}`);
        const p = data.data;

        document.getElementById('input-kode').value         = p.kode_produk;
        document.getElementById('input-nama').value         = p.nama_produk;
        document.getElementById('input-kategori').value     = p.kategori?.id ?? '';
        document.getElementById('input-satuan-dasar').value = p.satuan_dasar;
        document.getElementById('input-harga-beli').value   = p.harga_beli_dasar;
        document.getElementById('input-stok-min').value     = p.stok_minimum;
        document.getElementById('input-deskripsi').value    = p.deskripsi ?? '';
    } catch (err) {
        showToast('error', 'Gagal memuat data produk', err.response?.data?.message || 'Periksa koneksi');
    }
}

// ── Load Satuan Existing (edit mode) ─────────────────────────────────────────
async function loadSatuanData() {
    document.getElementById('satuan-skeleton').classList.remove('hidden');

    try {
        const { data } = await axios.get(`/api/gudang/produk/${PRODUK_ID}/satuan`);
        satuanRows = (data.data || []).map(s => ({
            rowId:       ++rowCounter,
            id:          s.id,           // DB id
            nama_satuan: s.nama_satuan,
            isi:         s.isi,
            harga_beli:  s.harga_beli,
            harga_jual:  s.harga_jual,
            is_default:  s.is_default,
            barcode:     s.barcode ?? '',
            isNew:       false,
            toDelete:    false,
        }));

        document.getElementById('satuan-skeleton').classList.add('hidden');
        renderTabelSatuan();
    } catch (err) {
        document.getElementById('satuan-skeleton').classList.add('hidden');
        showToast('error', 'Gagal memuat satuan', err.response?.data?.message || 'Periksa koneksi');
    }
}

// ── Render Tabel Satuan ───────────────────────────────────────────────────────
function renderTabelSatuan() {
    const tbody = document.getElementById('tbody-satuan');
    const empty = document.getElementById('satuan-empty');
    const visibleRows = satuanRows.filter(r => !r.toDelete);

    if (!visibleRows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');
    tbody.innerHTML = visibleRows.map(r => buildBarisSatuan(r)).join('');

    // Pasang event listener untuk setiap input di baris
    visibleRows.forEach(r => {
        const prefix = `[data-rowid="${r.rowId}"]`;
        const namaEl   = document.querySelector(`${prefix} .s-nama`);
        const isiEl    = document.querySelector(`${prefix} .s-isi`);
        const beliEl   = document.querySelector(`${prefix} .s-harga-beli`);
        const jualEl   = document.querySelector(`${prefix} .s-harga-jual`);
        const barcodeEl= document.querySelector(`${prefix} .s-barcode`);

        if (namaEl)    namaEl.addEventListener('input',   () => { r.nama_satuan = namaEl.value;           markModified(r); updateInfo(r.rowId); });
        if (isiEl)     isiEl.addEventListener('input',    () => { r.isi = parseInt(isiEl.value)||1;        markModified(r); updateInfo(r.rowId); });
        if (beliEl)    beliEl.addEventListener('input',   () => { r.harga_beli = parseFloat(beliEl.value)||0; markModified(r); });
        if (jualEl)    jualEl.addEventListener('input',   () => { r.harga_jual = parseFloat(jualEl.value)||0; markModified(r); updateInfo(r.rowId); updateAllInfo(); });
        if (barcodeEl) barcodeEl.addEventListener('input',() => { r.barcode = barcodeEl.value;             markModified(r); });
    });

    // Update semua info kalkulasi
    updateAllInfo();
}

function buildBarisSatuan(r) {
    const badgeNew = r.isNew ? `<span class="ml-1 text-xs text-indigo-500 dark:text-indigo-400 font-medium">baru</span>` : '';
    return `
    <tr data-rowid="${r.rowId}" class="group">
        <td class="px-3 py-2 min-w-[110px]">
            <input type="text" value="${esc(r.nama_satuan)}" maxlength="30" placeholder="pcs"
                class="input-sm s-nama w-full">${badgeNew}
        </td>
        <td class="px-3 py-2">
            <input type="number" value="${r.isi}" min="1" placeholder="1"
                class="input-sm s-isi w-full text-center">
        </td>
        <td class="px-3 py-2">
            <input type="number" value="${r.harga_beli}" min="0" step="0.01" placeholder="0"
                class="input-sm s-harga-beli w-full text-right font-mono">
        </td>
        <td class="px-3 py-2">
            <input type="number" value="${r.harga_jual}" min="0" step="0.01" placeholder="0"
                class="input-sm s-harga-jual w-full text-right font-mono">
        </td>
        <td class="px-3 py-2 text-center">
            <input type="radio" name="satuan_default" value="${r.rowId}"
                ${r.is_default ? 'checked' : ''}
                class="text-indigo-600 focus:ring-indigo-500"
                onchange="setDefault(${r.rowId})">
        </td>
        <td class="px-3 py-2">
            <input type="text" value="${esc(r.barcode || '')}" maxlength="50" placeholder="opsional"
                class="input-sm s-barcode w-full font-mono">
        </td>
        <td class="px-3 py-2 text-center">
            <button onclick="hapusBarisSatuan(${r.rowId})"
                class="p-1 text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 transition-colors rounded"
                title="Hapus baris">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </td>
    </tr>
    <tr data-rowid="${r.rowId}" data-info-row class="border-0">
        <td colspan="7" class="px-3 pb-2 pt-0">
            <p class="satuan-info text-xs text-gray-400 dark:text-gray-500 font-mono"></p>
        </td>
    </tr>`;
}

function markModified(row) {
    if (!row.isNew) row.isModified = true;
}

// ── Tambah Baris Baru ─────────────────────────────────────────────────────────
function tambahBarisSatuan(isFirst = false) {
    const newRow = {
        rowId:       ++rowCounter,
        id:          null,
        nama_satuan: isFirst ? (document.getElementById('input-satuan-dasar').value || '') : '',
        isi:         1,
        harga_beli:  0,
        harga_jual:  0,
        is_default:  isFirst && satuanRows.length === 0,
        barcode:     '',
        isNew:       true,
        toDelete:    false,
        isModified:  false,
    };
    satuanRows.push(newRow);
    renderTabelSatuan();
}

// ── Set Default ───────────────────────────────────────────────────────────────
function setDefault(rowId) {
    satuanRows.forEach(r => {
        r.is_default = (r.rowId === rowId);
        if (!r.isNew) r.isModified = true;
    });
    updateAllInfo();
}

// ── Hapus Baris ───────────────────────────────────────────────────────────────
function hapusBarisSatuan(rowId) {
    const row = satuanRows.find(r => r.rowId === rowId);
    if (!row) return;

    if (row.isNew || !row.id) {
        // Baris baru, hapus langsung dari array
        satuanRows = satuanRows.filter(r => r.rowId !== rowId);
        renderTabelSatuan();
        return;
    }

    // Baris existing — perlu konfirmasi, lalu DELETE ke API
    hapusSatuanTarget = { rowId, dbId: row.id, nama: row.nama_satuan };
    document.getElementById('hapus-satuan-pesan').innerHTML =
        `Satuan <strong>"${esc(row.nama_satuan)}"</strong> akan dihapus permanen. ` +
        `Jika satuan ini pernah dipakai di transaksi, penghapusan akan ditolak.`;

    const btn = document.getElementById('btn-hapus-satuan-ok');
    btn.onclick = eksekusiHapusSatuan;
    tampilkanModal('modal-hapus-satuan');
}

async function eksekusiHapusSatuan() {
    if (!hapusSatuanTarget) return;

    const btn = document.getElementById('btn-hapus-satuan-ok');
    btn.disabled = true;
    document.getElementById('spinner-hapus-satuan').classList.remove('hidden');

    try {
        await axios.delete(`/api/gudang/produk/${PRODUK_ID}/satuan/${hapusSatuanTarget.dbId}`);
        satuanRows = satuanRows.filter(r => r.rowId !== hapusSatuanTarget.rowId);
        sembunyikanModal('modal-hapus-satuan');
        renderTabelSatuan();
        showToast('success', 'Satuan dihapus', `Satuan "${hapusSatuanTarget.nama}" berhasil dihapus.`);
    } catch (err) {
        showToast('error', 'Gagal hapus satuan', err.response?.data?.message || 'Satuan mungkin sudah dipakai di transaksi');
        sembunyikanModal('modal-hapus-satuan');
    } finally {
        btn.disabled = false;
        document.getElementById('spinner-hapus-satuan').classList.add('hidden');
        hapusSatuanTarget = null;
    }
}

// ── Info Kalkulasi Satuan ─────────────────────────────────────────────────────
function updateInfo(rowId) {
    const row = satuanRows.find(r => r.rowId === rowId && !r.toDelete);
    if (!row) return;

    const infoEl = document.querySelector(`[data-rowid="${rowId}"][data-info-row] .satuan-info`);
    if (!infoEl) return;

    const hargaJual = parseFloat(row.harga_jual) || 0;
    const isi       = parseInt(row.isi) || 1;
    const satuanDasar = document.getElementById('input-satuan-dasar').value || 'dasar';

    if (hargaJual <= 0) { infoEl.textContent = ''; return; }

    const efektif = hargaJual / isi;

    // Cari satuan default sebagai baseline perbandingan hemat
    const defaultRow = satuanRows.find(r => r.is_default && !r.toDelete);
    const efektifDefault = defaultRow && parseInt(defaultRow.isi) > 0
        ? (parseFloat(defaultRow.harga_jual) / parseInt(defaultRow.isi))
        : 0;

    const fmtRp = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));

    if (row.is_default || efektifDefault <= 0 || efektif >= efektifDefault) {
        infoEl.textContent = `${fmtRp(hargaJual)} / ${isi} = ${fmtRp(efektif)}/${satuanDasar}`;
    } else {
        const hemat = ((efektifDefault - efektif) / efektifDefault * 100).toFixed(1);
        infoEl.textContent = `${fmtRp(hargaJual)} / ${isi} = ${fmtRp(efektif)}/${satuanDasar} · hemat ${hemat}% vs ${document.querySelector('input[name=satuan_default]:checked') ? satuanRows.find(r=>r.is_default)?.nama_satuan||'default' : 'default'}`;
    }
}

function updateAllInfo() {
    satuanRows.filter(r => !r.toDelete).forEach(r => updateInfo(r.rowId));
}

function onSatuanDasarChange() {
    updateAllInfo();
}

// ── Validasi Kode Real-Time saat Blur ────────────────────────────────────────
async function validasiKode() {
    const kode = document.getElementById('input-kode').value.trim();
    if (!kode) return;

    // Jika edit mode dan kode tidak berubah, skip
    if (IS_EDIT) return;

    try {
        const { data } = await axios.get('/api/gudang/produk?nama=' + encodeURIComponent(kode) + '&status=semua&limit=1');
        // Cek apakah ada produk dengan kode persis sama
        const exists = (data.data || []).some(p => p.kode_produk === kode);
        if (exists) {
            tampilkanErr('err-kode', `Kode "${kode}" sudah digunakan oleh produk lain`);
        }
    } catch (_) {}
}

// ── Validasi Form ────────────────────────────────────────────────────────────
function validasiForm() {
    let ok = true;
    clearAllErr();

    const kode    = document.getElementById('input-kode').value.trim();
    const nama    = document.getElementById('input-nama').value.trim();
    const kategori= document.getElementById('input-kategori').value;
    const satDasar= document.getElementById('input-satuan-dasar').value.trim();
    const hargaBeli=parseFloat(document.getElementById('input-harga-beli').value) || 0;
    const stokMin = parseInt(document.getElementById('input-stok-min').value) ?? 0;

    if (!kode)     { tampilkanErr('err-kode', 'Kode produk wajib diisi'); ok = false; }
    if (!nama)     { tampilkanErr('err-nama', 'Nama produk wajib diisi'); ok = false; }
    if (!kategori) { tampilkanErr('err-kategori', 'Pilih kategori terlebih dahulu'); ok = false; }
    if (!satDasar) { tampilkanErr('err-satuan-dasar', 'Satuan dasar wajib diisi'); ok = false; }
    if (hargaBeli <= 0) { tampilkanErr('err-harga-beli', 'Harga beli dasar harus lebih dari 0'); ok = false; }

    // Validasi satuan
    const visibleRows = satuanRows.filter(r => !r.toDelete);
    if (!visibleRows.length) {
        tampilkanErrSatuan('Minimal 1 satuan harus ditambahkan');
        ok = false;
    } else {
        const defaults = visibleRows.filter(r => r.is_default);
        if (defaults.length !== 1) {
            tampilkanErrSatuan('Tepat 1 satuan harus ditandai sebagai default');
            ok = false;
        }

        const invalid = visibleRows.filter(r => (parseInt(r.isi) || 0) < 1);
        if (invalid.length) {
            tampilkanErrSatuan('Semua nilai isi satuan harus ≥ 1');
            ok = false;
        }

        // Satuan yang sama dengan satuan_dasar harus isi = 1
        const samaDasar = visibleRows.find(r =>
            r.nama_satuan.toLowerCase() === satDasar.toLowerCase() && parseInt(r.isi) !== 1
        );
        if (samaDasar) {
            tampilkanErrSatuan(`Satuan "${satDasar}" (sama dengan satuan dasar) harus memiliki isi = 1`);
            ok = false;
        }

        const noNama = visibleRows.filter(r => !r.nama_satuan.trim());
        if (noNama.length) {
            tampilkanErrSatuan('Semua satuan harus memiliki nama');
            ok = false;
        }
    }

    return ok;
}

// ── Simpan Produk ────────────────────────────────────────────────────────────
async function simpanProduk() {
    if (!validasiForm()) {
        // Scroll ke error pertama
        document.querySelector('[id^="err-"]:not(.hidden), #err-satuan-global:not(.hidden)')
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    setSimpanLoading(true);

    try {
        if (IS_EDIT) {
            await simpanEdit();
        } else {
            await simpanBaru();
        }
    } finally {
        setSimpanLoading(false);
    }
}

async function simpanBaru() {
    const payload = {
        kode_produk:      document.getElementById('input-kode').value.trim(),
        nama_produk:      document.getElementById('input-nama').value.trim(),
        kategori_id:      parseInt(document.getElementById('input-kategori').value),
        satuan_dasar:     document.getElementById('input-satuan-dasar').value.trim(),
        harga_beli_dasar: parseFloat(document.getElementById('input-harga-beli').value) || 0,
        qty_stok:         parseInt(document.getElementById('input-stok-awal').value) || 0,
        stok_minimum:     parseInt(document.getElementById('input-stok-min').value) || 0,
        deskripsi:        document.getElementById('input-deskripsi').value.trim() || null,
        satuan_list:      satuanRows.filter(r => !r.toDelete).map(r => ({
            nama_satuan: r.nama_satuan.trim(),
            isi:         parseInt(r.isi) || 1,
            harga_beli:  parseFloat(r.harga_beli) || 0,
            harga_jual:  parseFloat(r.harga_jual) || 0,
            is_default:  r.is_default,
            barcode:     r.barcode?.trim() || null,
        })),
    };

    try {
        const { data } = await axios.post('/api/gudang/produk', payload);
        showToast('success', 'Produk Ditambahkan', `"${payload.nama_produk}" berhasil dibuat.`);
        setTimeout(() => { window.location.href = '/gudang/produk'; }, 1000);
    } catch (err) {
        tampilkanApiErrors(err);
    }
}

async function simpanEdit() {
    const payload = {
        kode_produk:      document.getElementById('input-kode').value.trim(),
        nama_produk:      document.getElementById('input-nama').value.trim(),
        kategori_id:      parseInt(document.getElementById('input-kategori').value),
        satuan_dasar:     document.getElementById('input-satuan-dasar').value.trim(),
        harga_beli_dasar: parseFloat(document.getElementById('input-harga-beli').value) || 0,
        stok_minimum:     parseInt(document.getElementById('input-stok-min').value) || 0,
        deskripsi:        document.getElementById('input-deskripsi').value.trim() || null,
    };

    try {
        // 1. Update header produk
        await axios.put(`/api/gudang/produk/${PRODUK_ID}`, payload);

        // 2. Simpan satuan baru (isNew)
        const newRows = satuanRows.filter(r => r.isNew && !r.toDelete);
        for (const r of newRows) {
            await axios.post(`/api/gudang/produk/${PRODUK_ID}/satuan`, {
                nama_satuan: r.nama_satuan.trim(),
                isi:         parseInt(r.isi) || 1,
                harga_beli:  parseFloat(r.harga_beli) || 0,
                harga_jual:  parseFloat(r.harga_jual) || 0,
                is_default:  r.is_default,
                barcode:     r.barcode?.trim() || null,
            });
        }

        // 3. Update satuan yang dimodifikasi
        const modRows = satuanRows.filter(r => !r.isNew && r.isModified && !r.toDelete && r.id);
        for (const r of modRows) {
            await axios.put(`/api/gudang/produk/${PRODUK_ID}/satuan/${r.id}`, {
                nama_satuan: r.nama_satuan.trim(),
                isi:         parseInt(r.isi) || 1,
                harga_beli:  parseFloat(r.harga_beli) || 0,
                harga_jual:  parseFloat(r.harga_jual) || 0,
                is_default:  r.is_default,
                barcode:     r.barcode?.trim() || null,
            });
        }

        showToast('success', 'Produk Diperbarui', `"${payload.nama_produk}" berhasil diperbarui.`);
        setTimeout(() => { window.location.href = '/gudang/produk'; }, 1000);

    } catch (err) {
        tampilkanApiErrors(err);
    }
}

// ── Helper Error ──────────────────────────────────────────────────────────────
function tampilkanErr(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('hidden');
    const input = el.previousElementSibling;
    if (input && input.tagName !== 'P') input.classList.add('error');
}

function clearErr(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('hidden');
    const input = el.previousElementSibling;
    if (input) input.classList.remove('error');
}

function clearAllErr() {
    document.querySelectorAll('[id^="err-"]').forEach(el => el.classList.add('hidden'));
    document.getElementById('err-satuan-global')?.classList.add('hidden');
}

function tampilkanErrSatuan(msg) {
    const el = document.getElementById('err-satuan-global');
    document.getElementById('err-satuan-global-msg').textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function tampilkanApiErrors(err) {
    const errors = err.response?.data?.errors;
    const msg    = err.response?.data?.message || 'Terjadi kesalahan';

    if (errors?.kode_produk)      tampilkanErr('err-kode', errors.kode_produk[0]);
    if (errors?.nama_produk)      tampilkanErr('err-nama', errors.nama_produk[0]);
    if (errors?.kategori_id)      tampilkanErr('err-kategori', errors.kategori_id[0]);
    if (errors?.satuan_dasar)     tampilkanErr('err-satuan-dasar', errors.satuan_dasar[0]);
    if (errors?.harga_beli_dasar) tampilkanErr('err-harga-beli', errors.harga_beli_dasar[0]);

    const satuanErr = Object.entries(errors || {}).find(([k]) => k.startsWith('satuan_list'));
    if (satuanErr) tampilkanErrSatuan(satuanErr[1][0]);

    if (!Object.keys(errors || {}).length) {
        showToast('error', 'Gagal menyimpan', msg);
    }
}

// ── Utilitas ──────────────────────────────────────────────────────────────────
function setSimpanLoading(on) {
    document.getElementById('btn-simpan').disabled = on;
    document.getElementById('spinner-simpan').classList.toggle('hidden', !on);
    document.getElementById('lbl-simpan').textContent = on
        ? 'Menyimpan...'
        : (IS_EDIT ? 'Simpan Perubahan' : 'Buat Produk');
}

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

function esc(str) {
    if (str == null) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
@endpush
