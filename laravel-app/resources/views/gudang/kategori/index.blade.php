@extends('layouts.app')

@section('title', 'Manajemen Kategori')
@section('page-title', 'Manajemen Kategori')

@section('content')
<div class="space-y-6">

    {{-- ── Action Bar ───────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Kategori digunakan untuk mengelompokkan produk di inventori.
        </p>
        <button onclick="bukaModalTambah()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Kategori
        </button>
    </div>

    {{-- ── Tabel Kategori ───────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Header card --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Daftar Kategori Produk</h3>
            <span id="badge-total"
                class="hidden text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
            </span>
        </div>

        {{-- Skeleton loading saat data belum tersedia --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 5; $i++)
            <div class="animate-pulse flex gap-4">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-8"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20 ml-auto"></div>
            </div>
            @endfor
        </div>

        {{-- Tabel data --}}
        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">No</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama Kategori</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deskripsi</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Jumlah Produk</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-kategori" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            {{-- Empty state --}}
            <div id="tbl-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada kategori.</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    <button onclick="bukaModalTambah()"
                        class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                        Tambahkan kategori pertama
                    </button>
                    untuk mulai mengelola produk.
                </p>
            </div>
        </div>

    </div>

</div>
@endsection

{{-- ══ MODAL TAMBAH / EDIT ════════════════════════════════════════════════════ --}}
{{-- hidden + flex dipisah: flex ditambah JS saat modal dibuka (hindari cssConflict) --}}
<div id="modal-form"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 id="modal-form-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">
                Tambah Kategori
            </h3>
            <button onclick="tutupModalForm()"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            {{-- Nama Kategori --}}
            <div class="space-y-1">
                <label for="input-nama" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nama Kategori <span class="text-red-500">*</span>
                </label>
                <input id="input-nama" type="text" maxlength="100" autocomplete="off"
                    placeholder="Contoh: Minuman, Sembako, Snack..."
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    oninput="clearErrNama()">
                <p id="err-nama" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Deskripsi --}}
            <div class="space-y-1">
                <label for="input-deskripsi" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Deskripsi
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">(opsional)</span>
                </label>
                <textarea id="input-deskripsi" rows="3"
                    placeholder="Keterangan singkat tentang kategori ini..."
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors resize-none"></textarea>
            </div>

        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalForm()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-simpan" onclick="simpanKategori()"
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

{{-- ══ MODAL KONFIRMASI HAPUS ══════════════════════════════════════════════════ --}}
<div id="modal-hapus"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Hapus Kategori</h3>
        </div>

        {{-- Panel: kategori masih dipakai produk (jumlah_produk > 0) --}}
        <div id="hapus-blok" class="hidden px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="shrink-0 w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Tidak dapat dihapus</p>
                    <p id="hapus-blok-pesan" class="text-sm text-gray-600 dark:text-gray-400 mt-1"></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                        Nonaktifkan atau pindahkan produk terkait terlebih dahulu.
                    </p>
                </div>
            </div>
        </div>

        {{-- Panel: kategori kosong, aman untuk dihapus --}}
        <div id="hapus-konfirm" class="hidden px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Konfirmasi Hapus</p>
                    <p id="hapus-konfirm-pesan" class="text-sm text-gray-600 dark:text-gray-400 mt-1"></p>
                    <p class="text-xs text-red-500 dark:text-red-400 mt-2">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalHapus()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Tutup
            </button>
            {{-- Hanya muncul jika jumlah_produk = 0 --}}
            <button id="btn-hapus-konfirm" onclick="hapusKategori()"
                class="hidden inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-hapus" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-hapus">Ya, Hapus</span>
            </button>
        </div>

    </div>
</div>

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
// authToken, axios config, showToast → sudah tersedia dari layouts/app.blade.php
let editingId    = null;  // null = mode tambah, int = mode edit
let deleteTarget = null;  // {id, nama_kategori, jumlah_produk}
let kategoriList = [];    // cache lokal hasil GET /api/gudang/kategori

// ── Load & Render Tabel ───────────────────────────────────────────────────────
async function loadKategori() {
    try {
        const { data } = await axios.get('/api/gudang/kategori');
        kategoriList = data.data || [];
        renderTabel(kategoriList);
    } catch (err) {
        showToast('error', 'Gagal memuat data', err.response?.data?.message || 'Periksa koneksi');
    }
}

function renderTabel(rows) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');

    const badge = document.getElementById('badge-total');
    badge.textContent = rows.length + ' kategori';
    badge.classList.remove('hidden');

    const tbody = document.getElementById('tbody-kategori');
    const empty = document.getElementById('tbl-empty');

    if (!rows.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');

    tbody.innerHTML = rows.map((k, i) => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500 font-mono">${i + 1}</td>

            <td class="px-4 py-3">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${esc(k.nama_kategori)}</p>
            </td>

            <td class="px-4 py-3">
                <p class="text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate" title="${esc(k.deskripsi || '')}">
                    ${k.deskripsi
                        ? esc(k.deskripsi)
                        : '<span class="text-gray-300 dark:text-gray-600 italic text-xs">Tidak ada deskripsi</span>'}
                </p>
            </td>

            <td class="px-4 py-3 text-center">
                ${k.jumlah_produk > 0
                    ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                           ${k.jumlah_produk} produk
                       </span>`
                    : `<span class="text-xs text-gray-300 dark:text-gray-600">—</span>`}
            </td>

            <td class="px-4 py-3 text-right">
                <div class="inline-flex items-center gap-1.5">
                    <button onclick="bukaModalEdit(${k.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </button>
                    <button onclick="bukaModalHapus(${k.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus
                    </button>
                </div>
            </td>
        </tr>`).join('');
}

// ── Modal Tambah / Edit ───────────────────────────────────────────────────────
function bukaModalTambah() {
    editingId = null;
    document.getElementById('modal-form-title').textContent = 'Tambah Kategori';
    document.getElementById('lbl-simpan').textContent       = 'Simpan';
    document.getElementById('input-nama').value             = '';
    document.getElementById('input-deskripsi').value        = '';
    clearErrNama();
    tampilkanModal('modal-form');
    setTimeout(() => document.getElementById('input-nama').focus(), 100);
}

function bukaModalEdit(id) {
    // Ambil data dari cache lokal — tidak perlu request tambahan
    const k = kategoriList.find(k => k.id === id);
    if (!k) return;

    editingId = id;
    document.getElementById('modal-form-title').textContent = 'Edit Kategori';
    document.getElementById('lbl-simpan').textContent       = 'Perbarui';
    document.getElementById('input-nama').value             = k.nama_kategori;
    document.getElementById('input-deskripsi').value        = k.deskripsi || '';
    clearErrNama();
    tampilkanModal('modal-form');
    setTimeout(() => document.getElementById('input-nama').focus(), 100);
}

async function simpanKategori() {
    const nama      = document.getElementById('input-nama').value.trim();
    const deskripsi = document.getElementById('input-deskripsi').value.trim();

    if (!nama) {
        tampilkanErrNama('Nama kategori wajib diisi');
        document.getElementById('input-nama').focus();
        return;
    }

    setSimpanLoading(true);
    clearErrNama();

    const payload = {
        nama_kategori: nama,
        deskripsi:     deskripsi || null,
    };

    try {
        if (editingId) {
            await axios.put(`/api/gudang/kategori/${editingId}`, payload);
            showToast('success', 'Kategori diperbarui', `"${nama}" berhasil diperbarui.`);
        } else {
            await axios.post('/api/gudang/kategori', payload);
            showToast('success', 'Kategori ditambahkan', `"${nama}" berhasil ditambahkan.`);
        }

        tutupModalForm();
        await loadKategori();

    } catch (err) {
        const errors = err.response?.data?.errors;
        if (errors?.nama_kategori?.[0]) {
            // 422 duplikat nama — tampilkan error inline di bawah field
            tampilkanErrNama(errors.nama_kategori[0]);
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

// ── Modal Hapus ───────────────────────────────────────────────────────────────
function bukaModalHapus(id) {
    const k = kategoriList.find(k => k.id === id);
    if (!k) return;

    deleteTarget = k;

    const elBlok    = document.getElementById('hapus-blok');
    const elKonfirm = document.getElementById('hapus-konfirm');
    const btnHapus  = document.getElementById('btn-hapus-konfirm');

    if (k.jumlah_produk > 0) {
        // Kategori masih dipakai — hanya info, tidak ada tombol hapus
        document.getElementById('hapus-blok-pesan').innerHTML =
            `Kategori <strong class="text-gray-800 dark:text-gray-100">"${esc(k.nama_kategori)}"</strong> ` +
            `masih digunakan oleh ` +
            `<strong class="text-amber-700 dark:text-amber-400">${k.jumlah_produk} produk</strong> ` +
            `dan tidak dapat dihapus.`;

        elBlok.classList.remove('hidden');
        elKonfirm.classList.add('hidden');
        btnHapus.classList.add('hidden');
    } else {
        // Kategori kosong — tampilkan konfirmasi hapus
        document.getElementById('hapus-konfirm-pesan').innerHTML =
            `Kategori <strong class="text-gray-800 dark:text-gray-100">"${esc(k.nama_kategori)}"</strong> ` +
            `akan dihapus permanen. Lanjutkan?`;

        elBlok.classList.add('hidden');
        elKonfirm.classList.remove('hidden');
        btnHapus.classList.remove('hidden');
    }

    tampilkanModal('modal-hapus');
}

async function hapusKategori() {
    if (!deleteTarget) return;

    setHapusLoading(true);

    try {
        await axios.delete(`/api/gudang/kategori/${deleteTarget.id}`);
        showToast('success', 'Kategori dihapus', `"${deleteTarget.nama_kategori}" berhasil dihapus.`);
        tutupModalHapus();
        await loadKategori();

    } catch (err) {
        // 422 bisa terjadi jika ada produk baru ditambahkan ke kategori ini
        // setelah modal konfirmasi sudah terbuka
        showToast('error', 'Gagal menghapus', err.response?.data?.message || 'Terjadi kesalahan');
        tutupModalHapus();
        await loadKategori(); // Refresh tabel agar jumlah_produk terbaru terlihat
    } finally {
        setHapusLoading(false);
    }
}

function tutupModalHapus() {
    sembunyikanModal('modal-hapus');
    deleteTarget = null;
    document.getElementById('lbl-hapus').textContent = 'Ya, Hapus';
}

// ── Utilitas Modal ────────────────────────────────────────────────────────────
// Pisah hidden dan flex agar tidak ada cssConflict (§10.6F)
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

// ── Utilitas Form ─────────────────────────────────────────────────────────────
function tampilkanErrNama(msg) {
    const errEl   = document.getElementById('err-nama');
    const inputEl = document.getElementById('input-nama');
    errEl.textContent = msg;
    errEl.classList.remove('hidden');
    inputEl.classList.add('border-red-500', 'dark:border-red-400');
    inputEl.classList.remove('border-gray-300', 'dark:border-gray-600');
}

function clearErrNama() {
    const errEl   = document.getElementById('err-nama');
    const inputEl = document.getElementById('input-nama');
    errEl.classList.add('hidden');
    errEl.textContent = '';
    inputEl.classList.remove('border-red-500', 'dark:border-red-400');
    inputEl.classList.add('border-gray-300', 'dark:border-gray-600');
}

function setSimpanLoading(on) {
    const btn = document.getElementById('btn-simpan');
    btn.disabled = on;
    document.getElementById('spinner-simpan').classList.toggle('hidden', !on);
    document.getElementById('lbl-simpan').textContent = on
        ? 'Menyimpan...'
        : (editingId ? 'Perbarui' : 'Simpan');
}

function setHapusLoading(on) {
    const btn = document.getElementById('btn-hapus-konfirm');
    btn.disabled = on;
    document.getElementById('spinner-hapus').classList.toggle('hidden', !on);
    document.getElementById('lbl-hapus').textContent = on ? 'Menghapus...' : 'Ya, Hapus';
}

// Escape HTML — wajib saat insert data dari server ke innerHTML untuk cegah XSS
function esc(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Tekan Enter di form → submit
document.addEventListener('DOMContentLoaded', function () {
    loadKategori();

    document.getElementById('input-nama').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') simpanKategori();
    });
});
</script>
@endpush
