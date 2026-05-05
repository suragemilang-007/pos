@extends('layouts.app')

@section('title', 'Manajemen Promo Produk')
@section('page-title', 'Manajemen Promo Produk')

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
                <input id="filter-nama" type="text" placeholder="Cari nama promo..."
                    class="block w-full pl-9 pr-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    oninput="renderTabel()">
            </div>

            {{-- Filter status --}}
            <select id="filter-status"
                class="sm:w-44 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="renderTabel()">
                <option value="">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
                <option value="belum_mulai">Belum Mulai</option>
                <option value="expired">Expired</option>
            </select>

            {{-- Tombol Tambah --}}
            <button onclick="bukaModalTambah()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 shrink-0 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Promo
            </button>
        </div>
    </div>

    {{-- ── Tabel Promo ──────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Skeleton --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 5; $i++)
            <div class="animate-pulse flex gap-4 items-center">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-24"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-28"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-16"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-lg w-32 ml-auto"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama Promo</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Tipe</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Periode</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-promo" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            <div id="tbl-empty" class="hidden px-6 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada promo ditemukan.</p>
            </div>
        </div>
    </div>

</div>
@endsection

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL TAMBAH / EDIT PROMO
     Layout dua kolom: kiri = form, kanan = preview real-time
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-form"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-3xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
            <h3 id="modal-form-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">Tambah Promo</h3>
            <button onclick="tutupModalForm()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body: form + preview --}}
        <div class="flex-1 overflow-y-auto">
            <div class="flex flex-col lg:flex-row">

                {{-- ── Kolom kiri: Form ──────────────────────────────────────── --}}
                <div class="flex-1 px-6 py-5 space-y-4 lg:border-r border-gray-200 dark:border-gray-700">

                    {{-- Produk --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Produk <span class="text-red-500">*</span>
                        </label>
                        <select id="f-produk-id"
                            class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                            onchange="onProdukChange(); updatePreview()">
                            <option value="">-- Pilih Produk --</option>
                        </select>
                        <p id="err-produk-id" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                    </div>

                    {{-- Satuan syarat beli --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Satuan Syarat Beli <span class="text-red-500">*</span>
                        </label>
                        <select id="f-satuan-id"
                            class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors disabled:opacity-50"
                            onchange="updatePreview()" disabled>
                            <option value="">-- Pilih Satuan --</option>
                        </select>
                        <p id="err-satuan-id" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                    </div>

                    {{-- Nama Promo --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nama Promo <span class="text-red-500">*</span>
                        </label>
                        <input id="f-nama" type="text" maxlength="200" autocomplete="off"
                            placeholder="Contoh: Beli 5 Gratis 1"
                            class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                            oninput="clearErr('err-nama','f-nama'); updatePreview()">
                        <p id="err-nama" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                    </div>

                    {{-- Tipe Promo + Min Qty (2 kolom) --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tipe Promo <span class="text-red-500">*</span>
                            </label>
                            <select id="f-tipe"
                                class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                                onchange="onTipeChange(); updatePreview()">
                                <option value="">-- Pilih Tipe --</option>
                                <option value="bonus_produk_sama">Bonus Produk Sama</option>
                                <option value="bonus_produk_lain">Bonus Produk Lain</option>
                                <option value="diskon_persen">Diskon Persen (%)</option>
                                <option value="diskon_nominal">Diskon Nominal (Rp)</option>
                            </select>
                            <p id="err-tipe" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Min. Qty Beli <span class="text-red-500">*</span>
                            </label>
                            <input id="f-min-qty" type="number" min="1" value="1"
                                class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                                oninput="clearErr('err-min-qty','f-min-qty'); updatePreview()">
                            <p id="err-min-qty" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                        </div>
                    </div>

                    {{-- === Kondisional: BONUS QTY (bonus_produk_sama & bonus_produk_lain) === --}}
                    <div id="section-bonus-qty" class="hidden space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Qty Bonus Diberikan <span class="text-red-500">*</span>
                        </label>
                        <input id="f-bonus-qty" type="number" min="1" value="1"
                            class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                            oninput="clearErr('err-bonus-qty','f-bonus-qty'); updatePreview()">
                        <p id="err-bonus-qty" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                    </div>

                    {{-- === Kondisional: PRODUK & SATUAN BONUS (bonus_produk_lain saja) === --}}
                    <div id="section-bonus-produk" class="hidden space-y-3">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Produk Bonus <span class="text-red-500">*</span>
                            </label>
                            <select id="f-bonus-produk-id"
                                class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                                onchange="onBonusProdukChange(); updatePreview()">
                                <option value="">-- Pilih Produk Bonus --</option>
                            </select>
                            <p id="err-bonus-produk-id" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Satuan Produk Bonus <span class="text-red-500">*</span>
                            </label>
                            <select id="f-bonus-satuan-id"
                                class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors disabled:opacity-50"
                                onchange="updatePreview()" disabled>
                                <option value="">-- Pilih Satuan Bonus --</option>
                            </select>
                            <p id="err-bonus-satuan-id" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                        </div>
                    </div>

                    {{-- === Kondisional: NILAI DISKON (diskon_persen & diskon_nominal) === --}}
                    <div id="section-diskon" class="hidden space-y-1">
                        <label id="lbl-diskon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nilai Diskon <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="f-diskon-nilai" type="number" min="0.01" step="0.01"
                                class="block w-full px-3 py-2 pr-12 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                                oninput="clearErr('err-diskon-nilai','f-diskon-nilai'); updatePreview()">
                            <span id="lbl-diskon-unit"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400 pointer-events-none">
                                %
                            </span>
                        </div>
                        <p id="err-diskon-nilai" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                    </div>

                    {{-- Periode Berlaku --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Berlaku Dari
                                <span class="text-xs font-normal text-gray-400 ml-1">(opsional)</span>
                            </label>
                            <input id="f-berlaku-dari" type="date"
                                class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                                oninput="clearErr('err-berlaku-dari','f-berlaku-dari'); updatePreview()">
                            <p id="err-berlaku-dari" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Berlaku Sampai
                                <span class="text-xs font-normal text-gray-400 ml-1">(opsional)</span>
                            </label>
                            <input id="f-berlaku-sampai" type="date"
                                class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                                oninput="clearErr('err-berlaku-sampai','f-berlaku-sampai'); updatePreview()">
                            <p id="err-berlaku-sampai" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                        </div>
                    </div>

                    {{-- Keterangan --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Keterangan
                            <span class="text-xs font-normal text-gray-400 ml-1">(tampil di UI kasir)</span>
                        </label>
                        <textarea id="f-keterangan" rows="2" maxlength="500"
                            placeholder="Contoh: Berlaku untuk pembelian tunai"
                            class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors resize-none"
                            oninput="updatePreview()"></textarea>
                    </div>

                    {{-- Status Aktif --}}
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <div class="relative">
                            <input id="f-is-active" type="checkbox" class="sr-only peer" checked onchange="updatePreview()">
                            <div class="w-10 h-5 bg-gray-300 dark:bg-gray-600 rounded-full peer
                                peer-checked:bg-indigo-500 transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow
                                peer-checked:translate-x-5 transition-transform"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Promo Aktif</span>
                    </label>

                </div>

                {{-- ── Kolom kanan: Preview real-time ───────────────────────── --}}
                <div class="lg:w-64 shrink-0 px-5 py-5 bg-gray-50 dark:bg-gray-900/40">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                        Preview Kasir
                    </p>

                    {{-- Card preview --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3 shadow-sm">
                        {{-- Badge tipe --}}
                        <div>
                            <span id="pv-tipe-badge"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                — Pilih tipe —
                            </span>
                        </div>
                        {{-- Nama promo --}}
                        <p id="pv-nama" class="text-sm font-semibold text-gray-900 dark:text-gray-100 leading-snug">
                            —
                        </p>
                        {{-- Deskripsi kondisi --}}
                        <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <p id="pv-kondisi">—</p>
                            <p id="pv-periode" class="text-gray-400 dark:text-gray-500">—</p>
                        </div>
                        {{-- Keterangan --}}
                        <p id="pv-keterangan" class="hidden text-xs text-blue-600 dark:text-blue-400 italic border-t border-gray-100 dark:border-gray-700 pt-2"></p>
                        {{-- Status badge --}}
                        <div>
                            <span id="pv-status"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                Aktif
                            </span>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-3 leading-relaxed">
                        Preview ini menggambarkan tampilan yang akan dilihat kasir saat scan produk.
                    </p>
                </div>

            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 shrink-0">
            <button onclick="tutupModalForm()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-simpan" onclick="simpanPromo()"
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
     MODAL KONFIRMASI HAPUS
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-hapus"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Hapus Promo</h3>
        </div>

        <div class="px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <p id="hapus-pesan" class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed"></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">
                        Jika promo sudah pernah digunakan di transaksi, gunakan <strong>Nonaktifkan</strong> saja.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalHapus()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-hapus-ok" onclick="eksekusiHapus()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
                <svg id="spinner-hapus" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-hapus-ok">Ya, Hapus</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let promoList    = [];   // semua data promo dari API
let produkList   = [];   // semua produk aktif (cache)
let editingId    = null;
let hapusTarget  = null;

// ── Load Data ─────────────────────────────────────────────────────────────────
async function loadPromo() {
    document.getElementById('tbl-skeleton').classList.remove('hidden');
    document.getElementById('tbl-wrapper').classList.add('hidden');

    try {
        const { data } = await axios.get('/api/gudang/promo');
        promoList = data.data || [];
        renderTabel();
    } catch (err) {
        showToast('error', 'Gagal memuat data', err.response?.data?.message || 'Periksa koneksi');
        document.getElementById('tbl-skeleton').classList.add('hidden');
        document.getElementById('tbl-wrapper').classList.remove('hidden');
    }
}

async function loadProdukList() {
    if (produkList.length) return; // sudah di-cache
    try {
        const { data } = await axios.get('/api/gudang/produk', { params: { status: 'aktif', limit: 200 } });
        produkList = data.data || [];
    } catch (_) {
        produkList = [];
    }
}

// ── Render Tabel ──────────────────────────────────────────────────────────────
function renderTabel() {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');

    const nama   = document.getElementById('filter-nama').value.trim().toLowerCase();
    const status = document.getElementById('filter-status').value;

    const filtered = promoList.filter(p => {
        if (nama && !p.nama_promo.toLowerCase().includes(nama)) return false;
        if (status && getStatusPromo(p) !== status) return false;
        return true;
    });

    const tbody = document.getElementById('tbody-promo');
    const empty = document.getElementById('tbl-empty');

    if (!filtered.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }
    empty.classList.add('hidden');

    const tipeCls = {
        bonus_produk_sama: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        bonus_produk_lain: 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
        diskon_persen:     'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        diskon_nominal:    'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    };
    const tipeLabel = {
        bonus_produk_sama: 'Bonus Sama',
        bonus_produk_lain: 'Bonus Lain',
        diskon_persen:     'Diskon %',
        diskon_nominal:    'Diskon Rp',
    };

    tbody.innerHTML = filtered.map(p => {
        const st      = getStatusPromo(p);
        const stBadge = statusBadgeHtml(st);
        const tipeBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${tipeCls[p.tipe_promo] || 'bg-gray-100 text-gray-600'}">${tipeLabel[p.tipe_promo] || p.tipe_promo}</span>`;

        const produkNama = p.produk?.nama_produk || `#${p.produk_id}`;
        const satuanNama = p.satuan_syarat?.nama_satuan || '—';

        const periode = formatPeriode(p.berlaku_dari, p.berlaku_sampai);

        // Toggle button
        const toggleCls = p.is_active
            ? 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100'
            : 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100';
        const toggleLabel = p.is_active ? 'Nonaktifkan' : 'Aktifkan';

        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${esc(p.nama_promo)}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    Min. ${p.min_qty} ${esc(satuanNama)}
                    ${p.keterangan ? `· <span class="italic">${esc(p.keterangan.substring(0,40))}${p.keterangan.length>40?'…':''}</span>` : ''}
                </p>
            </td>
            <td class="px-4 py-3 text-center">${tipeBadge}</td>
            <td class="px-4 py-3">
                <p class="text-sm text-gray-700 dark:text-gray-300">${esc(produkNama)}</p>
            </td>
            <td class="px-4 py-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">${periode}</p>
            </td>
            <td class="px-4 py-3 text-center">${stBadge}</td>
            <td class="px-4 py-3 text-right">
                <div class="inline-flex items-center gap-1 flex-wrap justify-end">
                    <button onclick="bukaModalEdit(${p.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </button>
                    <button onclick="togglePromo(${p.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium ${toggleCls} dark:hover:bg-opacity-30 rounded-lg transition-colors">
                        ${toggleLabel}
                    </button>
                    <button onclick="bukaModalHapus(${p.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── Status Promo (client-side logic) ─────────────────────────────────────────
function getStatusPromo(p) {
    if (!p.is_active) return 'nonaktif';
    const today = new Date().toISOString().split('T')[0];
    if (p.berlaku_dari  && p.berlaku_dari  > today) return 'belum_mulai';
    if (p.berlaku_sampai && p.berlaku_sampai < today) return 'expired';
    return 'aktif';
}

function statusBadgeHtml(st) {
    const map = {
        aktif:       'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        nonaktif:    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        belum_mulai: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        expired:     'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    const lbl = {
        aktif:'Aktif', nonaktif:'Nonaktif', belum_mulai:'Belum Mulai', expired:'Expired'
    };
    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${map[st] || ''}">
        ${lbl[st] || st}
    </span>`;
}

// ── Modal Tambah ──────────────────────────────────────────────────────────────
async function bukaModalTambah() {
    editingId = null;
    document.getElementById('modal-form-title').textContent = 'Tambah Promo';
    document.getElementById('lbl-simpan').textContent       = 'Simpan';

    await loadProdukList();
    populateProdukDropdown('f-produk-id', null);
    populateProdukDropdown('f-bonus-produk-id', null);

    resetForm();
    tampilModal('modal-form');
    setTimeout(() => document.getElementById('f-produk-id').focus(), 100);
}

// ── Modal Edit ────────────────────────────────────────────────────────────────
async function bukaModalEdit(id) {
    const p = promoList.find(x => x.id === id);
    if (!p) return;

    editingId = id;
    document.getElementById('modal-form-title').textContent = 'Edit Promo';
    document.getElementById('lbl-simpan').textContent       = 'Perbarui';

    await loadProdukList();
    populateProdukDropdown('f-produk-id', p.produk_id);
    populateProdukDropdown('f-bonus-produk-id', p.bonus_produk_id);

    resetForm();

    // Isi field
    document.getElementById('f-produk-id').value    = p.produk_id;
    document.getElementById('f-nama').value          = p.nama_promo;
    document.getElementById('f-tipe').value          = p.tipe_promo;
    document.getElementById('f-min-qty').value       = p.min_qty;
    document.getElementById('f-bonus-qty').value     = p.bonus_qty   ?? 1;
    document.getElementById('f-bonus-produk-id').value = p.bonus_produk_id ?? '';
    document.getElementById('f-diskon-nilai').value  = p.diskon_nilai ?? '';
    document.getElementById('f-berlaku-dari').value  = p.berlaku_dari  ?? '';
    document.getElementById('f-berlaku-sampai').value= p.berlaku_sampai ?? '';
    document.getElementById('f-keterangan').value    = p.keterangan ?? '';
    document.getElementById('f-is-active').checked   = !!p.is_active;

    // Load satuan produk utama
    await loadSatuanDropdown('f-satuan-id', p.produk_id, p.produk_satuan_id);

    // Tipe mungkin butuh load satuan bonus
    onTipeChange();
    if (p.tipe_promo === 'bonus_produk_lain' && p.bonus_produk_id) {
        await loadSatuanDropdown('f-bonus-satuan-id', p.bonus_produk_id, p.bonus_satuan_id);
    }

    updatePreview();
    tampilModal('modal-form');
}

function tutupModalForm() {
    sembunyiModal('modal-form');
    editingId = null;
}

function resetForm() {
    [
        'f-nama','f-keterangan','f-berlaku-dari','f-berlaku-sampai','f-diskon-nilai',
    ].forEach(id => { const el=document.getElementById(id); if(el) el.value = ''; });

    document.getElementById('f-tipe').value     = '';
    document.getElementById('f-min-qty').value  = '1';
    document.getElementById('f-bonus-qty').value= '1';
    document.getElementById('f-bonus-produk-id').value = '';
    document.getElementById('f-is-active').checked = true;

    bersihkanSemuaErr();
    onTipeChange();
    updatePreview();
}

// ── Populasi Dropdown Produk ──────────────────────────────────────────────────
function populateProdukDropdown(selectId, selectedId) {
    const sel = document.getElementById(selectId);
    sel.innerHTML = '<option value="">-- Pilih Produk --</option>';

    produkList.forEach(p => {
        const opt = document.createElement('option');
        opt.value       = p.id;
        opt.textContent = `${p.kode_produk} · ${p.nama_produk}`;
        if (selectedId && p.id === selectedId) opt.selected = true;
        sel.appendChild(opt);
    });
}

// ── Load Satuan Dropdown ──────────────────────────────────────────────────────
async function loadSatuanDropdown(selectId, produkId, selectedId = null) {
    const sel = document.getElementById(selectId);
    sel.innerHTML = '<option value="">-- Pilih Satuan --</option>';
    sel.disabled  = true;

    if (!produkId) return;

    try {
        const { data } = await axios.get(`/api/gudang/produk/${produkId}/satuan`);
        const list = data.data || [];

        list.forEach(s => {
            const opt = document.createElement('option');
            opt.value       = s.id;
            opt.textContent = `${s.nama_satuan} (isi ${s.isi})`;
            if (selectedId && s.id === selectedId) opt.selected = true;
            sel.appendChild(opt);
        });

        sel.disabled = list.length === 0;
    } catch (_) {
        sel.disabled = true;
    }
}

async function onProdukChange() {
    const id = document.getElementById('f-produk-id').value;
    clearErr('err-produk-id', 'f-produk-id');
    await loadSatuanDropdown('f-satuan-id', id ? parseInt(id) : null);
}

async function onBonusProdukChange() {
    const id = document.getElementById('f-bonus-produk-id').value;
    clearErr('err-bonus-produk-id', 'f-bonus-produk-id');
    await loadSatuanDropdown('f-bonus-satuan-id', id ? parseInt(id) : null);
}

// ── Validasi Kondisional Berdasarkan Tipe ─────────────────────────────────────
function onTipeChange() {
    const tipe = document.getElementById('f-tipe').value;
    clearErr('err-tipe', 'f-tipe');

    const showEl = (id, show) => {
        document.getElementById(id).classList.toggle('hidden', !show);
    };

    // bonus_qty: muncul untuk tipe bonus
    showEl('section-bonus-qty',    tipe === 'bonus_produk_sama' || tipe === 'bonus_produk_lain');
    // bonus_produk: hanya untuk bonus_produk_lain
    showEl('section-bonus-produk', tipe === 'bonus_produk_lain');
    // diskon: untuk kedua tipe diskon
    showEl('section-diskon',       tipe === 'diskon_persen' || tipe === 'diskon_nominal');

    // Update label dan unit diskon
    if (tipe === 'diskon_persen') {
        document.getElementById('lbl-diskon-unit').textContent = '%';
        document.getElementById('f-diskon-nilai').max          = '100';
        document.getElementById('f-diskon-nilai').placeholder  = '1 – 100';
    } else if (tipe === 'diskon_nominal') {
        document.getElementById('lbl-diskon-unit').textContent = 'Rp';
        document.getElementById('f-diskon-nilai').removeAttribute('max');
        document.getElementById('f-diskon-nilai').placeholder  = 'Contoh: 5000';
    }
}

// ── Preview Real-time ─────────────────────────────────────────────────────────
function updatePreview() {
    const tipe      = document.getElementById('f-tipe').value;
    const minQty    = document.getElementById('f-min-qty').value    || '?';
    const bonusQty  = document.getElementById('f-bonus-qty').value  || '?';
    const diskon    = document.getElementById('f-diskon-nilai').value;
    const nama      = document.getElementById('f-nama').value.trim();
    const dari      = document.getElementById('f-berlaku-dari').value;
    const sampai    = document.getElementById('f-berlaku-sampai').value;
    const ket       = document.getElementById('f-keterangan').value.trim();
    const isActive  = document.getElementById('f-is-active').checked;

    // Ambil teks satuan dari dropdown yang dipilih
    const satuanSel      = document.getElementById('f-satuan-id');
    const satuanTeks     = satuanSel.selectedIndex > 0
        ? satuanSel.options[satuanSel.selectedIndex].text.split(' (')[0]
        : 'satuan';

    const bonusSatuanSel = document.getElementById('f-bonus-satuan-id');
    const bonusSatuanTeks= bonusSatuanSel.selectedIndex > 0
        ? bonusSatuanSel.options[bonusSatuanSel.selectedIndex].text.split(' (')[0]
        : 'satuan';

    const bonusProdukSel = document.getElementById('f-bonus-produk-id');
    const bonusProdukTeks= bonusProdukSel.selectedIndex > 0
        ? bonusProdukSel.options[bonusProdukSel.selectedIndex].text.split(' · ')[1] || '?'
        : '?';

    // Badge tipe
    const tipeCls = {
        bonus_produk_sama: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        bonus_produk_lain: 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
        diskon_persen:     'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        diskon_nominal:    'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    };
    const tipeLabel = {
        bonus_produk_sama: 'Bonus Sama',
        bonus_produk_lain: 'Bonus Lain',
        diskon_persen:     'Diskon %',
        diskon_nominal:    'Diskon Rp',
    };
    const badge = document.getElementById('pv-tipe-badge');
    badge.textContent = tipe ? tipeLabel[tipe] : '— Pilih tipe —';
    badge.className = `inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${tipeCls[tipe] || 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'}`;

    // Nama
    document.getElementById('pv-nama').textContent = nama || '—';

    // Kondisi teks
    let kondisi = '—';
    switch (tipe) {
        case 'bonus_produk_sama':
            kondisi = `Beli min. ${minQty} ${satuanTeks} → gratis ${bonusQty} ${satuanTeks}`;
            break;
        case 'bonus_produk_lain':
            kondisi = `Beli min. ${minQty} ${satuanTeks} → gratis ${bonusQty} ${bonusSatuanTeks} ${bonusProdukTeks}`;
            break;
        case 'diskon_persen':
            kondisi = diskon
                ? `Beli min. ${minQty} ${satuanTeks} → diskon ${diskon}%`
                : `Beli min. ${minQty} ${satuanTeks} → diskon …%`;
            break;
        case 'diskon_nominal':
            kondisi = diskon
                ? `Beli min. ${minQty} ${satuanTeks} → potongan Rp ${fmt(diskon)}`
                : `Beli min. ${minQty} ${satuanTeks} → potongan Rp …`;
            break;
    }
    document.getElementById('pv-kondisi').textContent = kondisi;

    // Periode
    let periodeStr = 'Berlaku: selamanya';
    if (dari && sampai)   periodeStr = `${fmtDate(dari)} – ${fmtDate(sampai)}`;
    else if (dari)        periodeStr = `Mulai ${fmtDate(dari)}`;
    else if (sampai)      periodeStr = `Sampai ${fmtDate(sampai)}`;
    document.getElementById('pv-periode').textContent = periodeStr;

    // Keterangan
    const pvKet = document.getElementById('pv-keterangan');
    if (ket) {
        pvKet.textContent = `"${ket}"`;
        pvKet.classList.remove('hidden');
    } else {
        pvKet.classList.add('hidden');
    }

    // Status badge
    const pvStatus = document.getElementById('pv-status');
    if (!isActive) {
        pvStatus.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
        pvStatus.textContent = 'Nonaktif';
    } else {
        const today = new Date().toISOString().split('T')[0];
        if (dari && dari > today) {
            pvStatus.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
            pvStatus.textContent = 'Belum Mulai';
        } else {
            pvStatus.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
            pvStatus.textContent = 'Aktif';
        }
    }
}

// ── Simpan Promo (Tambah / Edit) ──────────────────────────────────────────────
async function simpanPromo() {
    const produkId    = document.getElementById('f-produk-id').value;
    const satuanId    = document.getElementById('f-satuan-id').value;
    const nama        = document.getElementById('f-nama').value.trim();
    const tipe        = document.getElementById('f-tipe').value;
    const minQty      = document.getElementById('f-min-qty').value;
    const bonusQty    = document.getElementById('f-bonus-qty').value;
    const bonusProduk = document.getElementById('f-bonus-produk-id').value;
    const bonusSatuan = document.getElementById('f-bonus-satuan-id').value;
    const diskon      = document.getElementById('f-diskon-nilai').value;
    const dari        = document.getElementById('f-berlaku-dari').value;
    const sampai      = document.getElementById('f-berlaku-sampai').value;
    const ket         = document.getElementById('f-keterangan').value.trim();
    const isActive    = document.getElementById('f-is-active').checked;

    bersihkanSemuaErr();

    // Validasi frontend sesuai tipe_promo
    let ok = true;
    if (!produkId)  { tampilErrField('err-produk-id','f-produk-id', 'Pilih produk'); ok = false; }
    if (!satuanId)  { tampilErrField('err-satuan-id','f-satuan-id', 'Pilih satuan syarat beli'); ok = false; }
    if (!nama)      { tampilErrField('err-nama','f-nama', 'Nama promo wajib diisi'); ok = false; }
    if (!tipe)      { tampilErrField('err-tipe','f-tipe', 'Pilih tipe promo'); ok = false; }
    if (!minQty || parseInt(minQty) < 1) { tampilErrField('err-min-qty','f-min-qty', 'Min. qty harus >= 1'); ok = false; }

    if (tipe === 'bonus_produk_sama' || tipe === 'bonus_produk_lain') {
        if (!bonusQty || parseInt(bonusQty) < 1) {
            tampilErrField('err-bonus-qty','f-bonus-qty', 'Qty bonus harus >= 1');
            ok = false;
        }
    }
    if (tipe === 'bonus_produk_lain') {
        if (!bonusProduk) { tampilErrField('err-bonus-produk-id','f-bonus-produk-id', 'Pilih produk bonus'); ok = false; }
        if (!bonusSatuan) { tampilErrField('err-bonus-satuan-id','f-bonus-satuan-id', 'Pilih satuan produk bonus'); ok = false; }
    }
    if (tipe === 'diskon_persen') {
        if (!diskon) { tampilErrField('err-diskon-nilai','f-diskon-nilai', 'Nilai diskon wajib diisi'); ok = false; }
        else if (parseFloat(diskon) <= 0 || parseFloat(diskon) > 100) {
            tampilErrField('err-diskon-nilai','f-diskon-nilai', 'Diskon persen harus antara 0.01–100');
            ok = false;
        }
    }
    if (tipe === 'diskon_nominal') {
        if (!diskon) { tampilErrField('err-diskon-nilai','f-diskon-nilai', 'Nilai diskon wajib diisi'); ok = false; }
        else if (parseFloat(diskon) <= 0) {
            tampilErrField('err-diskon-nilai','f-diskon-nilai', 'Nilai diskon harus lebih dari 0');
            ok = false;
        }
    }
    if (dari && sampai && dari > sampai) {
        tampilErrField('err-berlaku-sampai','f-berlaku-sampai', 'Tanggal akhir tidak boleh sebelum tanggal mulai');
        ok = false;
    }
    if (!ok) return;

    setSimpanLoading(true);

    const payload = {
        produk_id:        parseInt(produkId),
        produk_satuan_id: parseInt(satuanId),
        nama_promo:       nama,
        tipe_promo:       tipe,
        min_qty:          parseInt(minQty),
        bonus_qty:        (tipe==='bonus_produk_sama'||tipe==='bonus_produk_lain') ? parseInt(bonusQty) : null,
        bonus_produk_id:  tipe==='bonus_produk_lain' ? parseInt(bonusProduk) : null,
        bonus_satuan_id:  tipe==='bonus_produk_lain' ? parseInt(bonusSatuan) : null,
        diskon_nilai:     (tipe==='diskon_persen'||tipe==='diskon_nominal') ? parseFloat(diskon) : null,
        keterangan:       ket || null,
        is_active:        isActive,
        berlaku_dari:     dari  || null,
        berlaku_sampai:   sampai || null,
    };

    try {
        if (editingId) {
            await axios.put(`/api/gudang/promo/${editingId}`, payload);
            showToast('success', 'Promo Diperbarui', `"${nama}" berhasil diperbarui.`);
        } else {
            await axios.post('/api/gudang/promo', payload);
            showToast('success', 'Promo Ditambahkan', `"${nama}" berhasil ditambahkan.`);
        }
        tutupModalForm();
        await loadPromo();
    } catch (err) {
        const errors = err.response?.data?.errors;
        if (errors) {
            const fieldMap = {
                produk_id:'err-produk-id', produk_satuan_id:'err-satuan-id',
                nama_promo:'err-nama', tipe_promo:'err-tipe', min_qty:'err-min-qty',
                bonus_qty:'err-bonus-qty', bonus_produk_id:'err-bonus-produk-id',
                bonus_satuan_id:'err-bonus-satuan-id', diskon_nilai:'err-diskon-nilai',
                berlaku_dari:'err-berlaku-dari', berlaku_sampai:'err-berlaku-sampai',
            };
            for (const [key, errId] of Object.entries(fieldMap)) {
                if (errors[key]?.[0]) {
                    const inputId = errId.replace('err-', 'f-');
                    tampilErrField(errId, inputId, errors[key][0]);
                }
            }
        } else {
            showToast('error', 'Gagal menyimpan', err.response?.data?.message || 'Terjadi kesalahan');
        }
    } finally {
        setSimpanLoading(false);
    }
}

function setSimpanLoading(on) {
    document.getElementById('btn-simpan').disabled = on;
    document.getElementById('spinner-simpan').classList.toggle('hidden', !on);
    document.getElementById('lbl-simpan').textContent = on ? 'Menyimpan...' : (editingId ? 'Perbarui' : 'Simpan');
}

// ── Toggle Aktif/Nonaktif ─────────────────────────────────────────────────────
async function togglePromo(id) {
    const p = promoList.find(x => x.id === id);
    if (!p) return;

    try {
        const { data } = await axios.patch(`/api/gudang/promo/${id}/toggle`);
        const isNowActive = data.data?.is_active;
        showToast('success', isNowActive ? 'Promo Diaktifkan' : 'Promo Dinonaktifkan',
            `"${p.nama_promo}" berhasil ${isNowActive ? 'diaktifkan' : 'dinonaktifkan'}.`);

        // Update lokal
        const idx = promoList.findIndex(x => x.id === id);
        if (idx !== -1) promoList[idx].is_active = isNowActive;
        renderTabel();
    } catch (err) {
        showToast('error', 'Gagal', err.response?.data?.message || 'Terjadi kesalahan');
    }
}

// ── Modal Hapus ───────────────────────────────────────────────────────────────
function bukaModalHapus(id) {
    const p = promoList.find(x => x.id === id);
    if (!p) return;

    hapusTarget = p;
    document.getElementById('hapus-pesan').innerHTML =
        `Promo <strong class="text-gray-900 dark:text-gray-100">"${esc(p.nama_promo)}"</strong> akan dihapus permanen.`;
    tampilModal('modal-hapus');
}

function tutupModalHapus() {
    sembunyiModal('modal-hapus');
    hapusTarget = null;
}

async function eksekusiHapus() {
    if (!hapusTarget) return;

    const btn = document.getElementById('btn-hapus-ok');
    btn.disabled = true;
    document.getElementById('spinner-hapus').classList.remove('hidden');
    document.getElementById('lbl-hapus-ok').textContent = 'Menghapus...';

    try {
        await axios.delete(`/api/gudang/promo/${hapusTarget.id}`);
        showToast('success', 'Promo Dihapus', `"${hapusTarget.nama_promo}" berhasil dihapus.`);
        tutupModalHapus();
        await loadPromo();
    } catch (err) {
        const msg = err.response?.data?.message || 'Terjadi kesalahan';
        if (err.response?.status === 422) {
            showToast('warning', 'Tidak Bisa Dihapus', msg);
        } else {
            showToast('error', 'Gagal', msg);
        }
        tutupModalHapus();
    } finally {
        btn.disabled = false;
        document.getElementById('spinner-hapus').classList.add('hidden');
        document.getElementById('lbl-hapus-ok').textContent = 'Ya, Hapus';
    }
}

// ── Utilitas Modal ────────────────────────────────────────────────────────────
function tampilModal(id)  { const el=document.getElementById(id); el.classList.remove('hidden'); el.classList.add('flex'); }
function sembunyiModal(id){ const el=document.getElementById(id); el.classList.add('hidden'); el.classList.remove('flex'); }

// Klik backdrop + Escape
['modal-form','modal-hapus'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', e => { if(e.target===e.currentTarget) sembunyiModal(id); });
});
document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    ['modal-hapus','modal-form'].forEach(id => {
        const el = document.getElementById(id);
        if (el && !el.classList.contains('hidden')) sembunyiModal(id);
    });
});

// ── Utilitas Error ────────────────────────────────────────────────────────────
function tampilErrField(errId, inputId, msg) {
    const e = document.getElementById(errId);
    if (!e) return;
    e.textContent = msg;
    e.classList.remove('hidden');
    document.getElementById(inputId)?.classList.add('border-red-500','dark:border-red-400');
}
function clearErr(errId, inputId) {
    const e = document.getElementById(errId);
    e?.classList.add('hidden');
    document.getElementById(inputId)?.classList.remove('border-red-500','dark:border-red-400');
}
function bersihkanSemuaErr() {
    document.querySelectorAll('[id^="err-"]').forEach(e => e.classList.add('hidden'));
    document.querySelectorAll('[id^="f-"]').forEach(el => el.classList.remove('border-red-500','dark:border-red-400'));
}

// ── Utilitas Format ───────────────────────────────────────────────────────────
function fmtDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('id-ID', {
        day:'2-digit', month:'short', year:'numeric'
    });
}
function formatPeriode(dari, sampai) {
    if (!dari && !sampai) return '<span class="text-gray-400 dark:text-gray-500 italic">Selamanya</span>';
    if (dari && sampai)   return `${fmtDate(dari)} – ${fmtDate(sampai)}`;
    if (dari)             return `Mulai ${fmtDate(dari)}`;
    return `Sampai ${fmtDate(sampai)}`;
}
function fmt(n) { return new Intl.NumberFormat('id-ID').format(n ?? 0); }
function esc(s) {
    if (s==null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => { loadPromo(); });
</script>
@endpush
