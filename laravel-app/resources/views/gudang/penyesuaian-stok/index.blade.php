@extends('layouts.app')

@section('title', 'Penyesuaian Stok')
@section('page-title', 'Penyesuaian Stok')

@section('content')
<div class="space-y-5">

    {{-- ── Section Atas: Aksi + Filter ────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">

            {{-- Filter tipe --}}
            <select id="filter-tipe"
                class="sm:w-52 px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                onchange="loadData(1)">
                <option value="">Semua Tipe</option>
                <option value="barang_rusak">Rusak</option>
                <option value="barang_hilang">Hilang</option>
                <option value="barang_expired">Expired</option>
                <option value="koreksi_plus">+ Koreksi</option>
                <option value="koreksi_minus">- Koreksi</option>
            </select>

            {{-- Dari --}}
            <div class="relative sm:w-40">
                <label class="absolute -top-2 left-2.5 text-[10px] font-medium text-gray-400 bg-white dark:bg-gray-800 px-1">Dari</label>
                <input id="filter-dari" type="date"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="loadData(1)">
            </div>

            {{-- Sampai --}}
            <div class="relative sm:w-40">
                <label class="absolute -top-2 left-2.5 text-[10px] font-medium text-gray-400 bg-white dark:bg-gray-800 px-1">Sampai</label>
                <input id="filter-sampai" type="date"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="loadData(1)">
            </div>

            <div class="flex-1"></div>

            {{-- [+ Penyesuaian Baru] — CTA utama --}}
            <button onclick="bukaModal()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Penyesuaian Baru
            </button>
        </div>
    </div>

    {{-- ── Tabel Riwayat ────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Skeleton --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 8; $i++)
            <div class="animate-pulse flex gap-4 items-center">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-28 shrink-0"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded flex-1"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-20 shrink-0"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-12 shrink-0"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20 shrink-0"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded-full w-16 shrink-0"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left min-w-[800px]">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipe</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Qty</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keterangan</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Admin</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="tbody-data" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            <div id="tbl-empty" class="hidden px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Belum ada riwayat penyesuaian</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Klik tombol "+ Penyesuaian Baru" untuk memulai</p>
                <button onclick="bukaModal()"
                    class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Penyesuaian Pertama
                </button>
            </div>
        </div>

        {{-- Pagination --}}
        <div id="pagination" class="hidden px-4 py-3 border-t border-gray-200 dark:border-gray-700 items-center justify-between">
            <p id="pagination-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div id="pagination-btns" class="flex gap-1"></div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     MODAL — Penyesuaian Baru
     Dua langkah: Langkah 1 cari produk → Langkah 2 isi form
════════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-penyesuaian"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Penyesuaian Stok</h3>
                <p id="modal-langkah-label" class="text-xs text-indigo-600 dark:text-indigo-400 font-medium mt-0.5">
                    Langkah 1 dari 2 — Pilih Produk
                </p>
            </div>
            <button onclick="tutupModal()"
                class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Indikator langkah --}}
        <div class="px-6 pt-4 shrink-0">
            <div class="flex items-center gap-2">
                <div id="step-dot-1"
                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold bg-indigo-600 text-white">1</div>
                <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700">
                    <div id="step-line" class="h-full bg-indigo-600 transition-all duration-300 w-0"></div>
                </div>
                <div id="step-dot-2"
                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400">2</div>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-medium">Pilih Produk</span>
                <span id="step2-label-txt" class="text-[10px] text-gray-400 dark:text-gray-500">Isi Penyesuaian</span>
            </div>
        </div>

        {{-- Body (scrollable) --}}
        <div class="overflow-y-auto flex-1 px-6 py-4">

            {{-- ════════════════════
                 LANGKAH 1: Cari Produk
                 ════════════════════ --}}
            <div id="panel-langkah-1" class="space-y-4">

                {{-- Input kode / nama --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Kode Barcode atau Nama Produk
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        {{-- Ikon barcode --}}
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        <input id="input-kode" type="text" placeholder="Scan barcode atau ketik nama produk..."
                            autocomplete="off"
                            class="block w-full pl-10 pr-3 py-2.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                            oninput="onKodeInput(this.value)"
                            onkeydown="onKodeKeydown(event)">
                        {{-- Spinner loading --}}
                        <svg id="kode-spinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2 animate-spin w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        Tekan Enter untuk mencari langsung via kode · atau ketik ≥ 2 karakter untuk autocomplete
                    </p>

                    {{-- Dropdown autocomplete --}}
                    <div id="dropdown-produk"
                        class="hidden mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                    </div>
                </div>

                {{-- Kartu info produk terpilih (muncul setelah produk dipilih) --}}
                <div id="produk-terpilih" class="hidden">
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                    </svg>
                                </div>
                                <div>
                                    <p id="info-nama-produk" class="text-sm font-semibold text-gray-900 dark:text-gray-100">—</p>
                                    <p id="info-kode-produk" class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-0.5">—</p>
                                </div>
                            </div>
                            <button type="button" onclick="resetProduk()" title="Ganti produk"
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline shrink-0">Ganti</button>
                        </div>
                        <div class="mt-3 pt-3 border-t border-indigo-200 dark:border-indigo-700 flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Stok saat ini</span>
                            <div class="flex items-center gap-1.5">
                                <span id="info-stok-nilai" class="text-lg font-bold text-gray-900 dark:text-gray-100">—</span>
                                <span id="info-stok-satuan" class="text-sm text-gray-500 dark:text-gray-400">—</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol lanjut ke langkah 2 --}}
                    <button onclick="lanjutLangkah2()"
                        class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-lg transition-colors">
                        Lanjut Isi Penyesuaian →
                    </button>
                </div>

                {{-- Tidak ditemukan --}}
                <div id="produk-tidak-ada" class="hidden p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-sm text-red-700 dark:text-red-400">Produk tidak ditemukan untuk kode/nama tersebut.</p>
                </div>

            </div>

            {{-- ════════════════════
                 LANGKAH 2: Isi Form
                 ════════════════════ --}}
            <div id="panel-langkah-2" class="hidden space-y-5">

                {{-- Ringkasan produk (mini) --}}
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p id="step2-nama" class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">—</p>
                        <p id="step2-stok" class="text-xs text-gray-500 dark:text-gray-400">Stok: —</p>
                    </div>
                    <button onclick="kembaliLangkah1()" class="text-xs text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline shrink-0">
                        ← Ganti
                    </button>
                </div>

                {{-- Alert error --}}
                <div id="alert-error" class="hidden p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p id="alert-error-msg" class="text-sm text-red-700 dark:text-red-400"></p>
                </div>

                {{-- Tipe Penyesuaian — radio buttons dengan deskripsi --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Tipe Penyesuaian <span class="text-red-500">*</span>
                    </label>

                    <div class="space-y-2">

                        {{-- Pengurangan --}}
                        <p class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-1">Pengurangan Stok</p>

                        @foreach ([
                            ['barang_rusak',   'Rusak',    'Barang rusak fisik di gudang (bukan dari supplier — gunakan Retur Pembelian untuk itu)'],
                            ['barang_hilang',  'Hilang',   'Barang tidak ditemukan saat opname fisik'],
                            ['barang_expired', 'Expired',  'Barang sudah melewati tanggal kadaluarsa'],
                            ['koreksi_minus',  '− Koreksi','Selisih negatif setelah opname (stok sistem > stok fisik)'],
                        ] as [$val, $label, $desc])
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/10 cursor-pointer transition-colors group has-checked:border-indigo-500 has-checked:bg-indigo-50 dark:has-checked:bg-indigo-900/20">
                            <input type="radio" name="tipe-penyesuaian" value="{{ $val }}"
                                class="mt-0.5 w-4 h-4 text-indigo-600 border-gray-300 dark:border-gray-600 focus:ring-indigo-500 shrink-0"
                                onchange="onTipeChange(this.value)">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $label }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">{{ $desc }}</p>
                            </div>
                        </label>
                        @endforeach

                        {{-- Penambahan --}}
                        <p class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-1 pt-1">Penambahan Stok</p>

                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-green-400 dark:hover:border-green-600 hover:bg-green-50 dark:hover:bg-green-900/10 cursor-pointer transition-colors has-checked:border-green-500 has-checked:bg-green-50 dark:has-checked:bg-green-900/20">
                            <input type="radio" name="tipe-penyesuaian" value="koreksi_plus"
                                class="mt-0.5 w-4 h-4 text-green-600 border-gray-300 dark:border-gray-600 focus:ring-green-500 shrink-0"
                                onchange="onTipeChange(this.value)">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">+ Koreksi</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">Selisih positif setelah opname (stok sistem &lt; stok fisik)</p>
                            </div>
                        </label>

                    </div>
                </div>

                {{-- Qty --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Jumlah <span id="lbl-satuan-dasar" class="text-gray-400 font-normal">(satuan dasar)</span>
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input id="input-qty" type="number" min="1" placeholder="0"
                            class="w-32 px-3 py-2 text-sm text-center font-mono bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                            oninput="updatePreview()">
                        <span id="lbl-satuan-unit" class="text-sm font-medium text-gray-500 dark:text-gray-400">—</span>
                    </div>
                    {{-- Warning stok melebihi --}}
                    <p id="qty-warning" class="hidden mt-1.5 text-xs font-semibold text-red-600 dark:text-red-400">
                        ⚠ Melebihi stok! Qty tidak boleh lebih dari stok tersedia.
                    </p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        Selalu positif — arah stok ditentukan oleh tipe di atas.
                    </p>
                </div>

                {{-- Keterangan --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Keterangan <span class="text-red-500">*</span>
                        <span class="text-xs font-normal text-gray-400">(wajib, min. 5 karakter)</span>
                    </label>
                    <textarea id="input-keterangan" rows="3"
                        placeholder="Jelaskan alasan penyesuaian. Contoh: Ditemukan 5 karton rusak akibat kebocoran atap gudang."
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors resize-none"
                        oninput="updateCharCount()"></textarea>
                    <div class="flex justify-between mt-1">
                        <p id="ket-err" class="hidden text-xs text-red-600 dark:text-red-400">Keterangan minimal 5 karakter.</p>
                        <p id="char-count" class="text-xs text-gray-400 dark:text-gray-500 ml-auto">0 karakter</p>
                    </div>
                </div>

                {{-- Preview perubahan stok --}}
                <div id="preview-panel" class="hidden rounded-xl border p-4">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Preview Perubahan
                    </p>
                    <p id="preview-text" class="text-sm font-medium text-gray-900 dark:text-gray-100 leading-relaxed">—</p>
                    <div class="mt-2 flex items-center gap-2">
                        <span id="preview-stok-lama" class="text-base font-mono font-bold text-gray-400">—</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                        <span id="preview-stok-baru" class="text-base font-mono font-bold">—</span>
                        <span id="preview-satuan" class="text-sm text-gray-500 dark:text-gray-400">—</span>
                    </div>
                </div>

            </div>
        </div>

        {{-- Footer --}}
        <div id="modal-footer" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 shrink-0">
            {{-- Footer Langkah 1: Batal saja (lanjut via tombol di dalam panel) --}}
            <div id="footer-step1" class="flex justify-end">
                <button onclick="tutupModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    Batal
                </button>
            </div>
            {{-- Footer Langkah 2: Batal + Simpan --}}
            <div id="footer-step2" class="hidden items-center justify-between">
                <button onclick="kembaliLangkah1()"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    ← Kembali
                </button>
                <div class="flex gap-3">
                    <button onclick="tutupModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Batal
                    </button>
                    <button id="btn-submit" onclick="submitPenyesuaian()"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg id="btn-spinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Simpan Penyesuaian
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
'use strict';

// ─────────────────────────────────────────────────────────────────────────────
// KONSTANTA
// ─────────────────────────────────────────────────────────────────────────────
const TIPE_CONFIG = {
    barang_rusak:   { label: 'Rusak',      cls: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',         dir: -1 },
    barang_hilang:  { label: 'Hilang',     cls: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400', dir: -1 },
    barang_expired: { label: 'Expired',    cls: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400', dir: -1 },
    koreksi_minus:  { label: '− Koreksi',  cls: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',         dir: -1 },
    koreksi_plus:   { label: '+ Koreksi',  cls: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',  dir: +1 },
};

const TIPE_KURANG = new Set(['barang_rusak', 'barang_hilang', 'barang_expired', 'koreksi_minus']);

// ─────────────────────────────────────────────────────────────────────────────
// STATE
// ─────────────────────────────────────────────────────────────────────────────
let currentPage    = 1;
let selectedProduk = null;  // { id, kode_produk, nama_produk, satuan_dasar, qty_stok }
let selectedTipe   = '';
let kodeTimer      = null;
let kodeAutoTimer  = null;

// ─────────────────────────────────────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toLocaleDateString('sv');
    document.getElementById('filter-dari').value   = today;
    document.getElementById('filter-sampai').value = today;
    loadData(1);

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') tutupModal();
    });
    // Tutup dropdown saat klik luar
    document.addEventListener('click', e => {
        if (!e.target.closest('#input-kode') && !e.target.closest('#dropdown-produk')) {
            document.getElementById('dropdown-produk').classList.add('hidden');
        }
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// LOAD DATA
// ─────────────────────────────────────────────────────────────────────────────
async function loadData(page = 1) {
    currentPage = page;
    showSkeleton(true);

    const params = new URLSearchParams({ page, limit: 20 });
    const tipe   = document.getElementById('filter-tipe').value;
    const dari   = document.getElementById('filter-dari').value;
    const sampai = document.getElementById('filter-sampai').value;
    if (tipe)   params.set('tipe', tipe);
    if (dari)   params.set('dari', dari);
    if (sampai) params.set('sampai', sampai);

    try {
        const { data: res } = await axios.get(`/api/gudang/penyesuaian-stok?${params}`);
        renderTable(res.data ?? []);
        renderPagination(res.pagination ?? {});
    } catch (err) {
        console.error('[penyesuaian] Gagal load:', err.message);
        showEmpty();
    } finally {
        showSkeleton(false);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// RENDER TABEL
// ─────────────────────────────────────────────────────────────────────────────
function renderTable(data) {
    const tbody = document.getElementById('tbody-data');

    if (!data.length) {
        showEmpty();
        return;
    }

    document.getElementById('tbl-empty').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');
    showPagination(true);

    tbody.innerHTML = data.map(item => {
        const cfg = TIPE_CONFIG[item.tipe] ?? { label: item.tipe, cls: 'bg-gray-100 text-gray-600', dir: 0 };

        const statusCls = item.status === 'COMPLETED'
            ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
            : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400';

        const tgl = item.created_at
            ? new Date(item.created_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
            : '—';

        const qtySign = cfg.dir < 0 ? `<span class="text-red-500 font-bold">−</span>` : `<span class="text-green-500 font-bold">+</span>`;

        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">${tgl}</td>
            <td class="px-4 py-3">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${esc(item.produk?.nama_produk ?? '—')}</p>
                <p class="text-xs font-mono text-gray-400 dark:text-gray-500">${esc(item.produk?.kode_produk ?? '')}</p>
            </td>
            <td class="px-4 py-3">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold ${cfg.cls}">
                    ${cfg.label}
                </span>
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
                <span class="text-sm font-mono font-semibold text-gray-900 dark:text-gray-100">
                    ${qtySign}${item.qty_satuan_dasar}
                </span>
                <span class="text-xs text-gray-400 ml-0.5">${esc(item.produk?.satuan_dasar ?? '')}</span>
            </td>
            <td class="px-4 py-3 max-w-[200px]">
                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2" title="${esc(item.keterangan ?? '')}">${esc(item.keterangan ?? '')}</p>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">${esc(item.nama_user ?? '—')}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${statusCls}">
                    ${item.status === 'COMPLETED' ? 'Selesai' : 'Proses'}
                </span>
            </td>
        </tr>`;
    }).join('');
}

function renderPagination(pg) {
    if (!pg.last_page || pg.last_page <= 1) {
        showPagination(false);
        return;
    }
    document.getElementById('pagination-info').textContent =
        `Halaman ${pg.current_page} dari ${pg.last_page} (${pg.total} entri)`;

    let html = '';
    if (pg.current_page > 1) {
        html += `<button onclick="loadData(${pg.current_page - 1})"
            class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors">← Prev</button>`;
    }
    if (pg.current_page < pg.last_page) {
        html += `<button onclick="loadData(${pg.current_page + 1})"
            class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors">Next →</button>`;
    }
    document.getElementById('pagination-btns').innerHTML = html;
    showPagination(true);
}

// ─────────────────────────────────────────────────────────────────────────────
// MODAL — BUKA / TUTUP / NAVIGASI LANGKAH
// ─────────────────────────────────────────────────────────────────────────────
function bukaModal() {
    resetModal();
    const m = document.getElementById('modal-penyesuaian');
    m.classList.remove('hidden');
    m.classList.add('flex');
    setTimeout(() => document.getElementById('input-kode').focus(), 100);
}

function tutupModal() {
    const m = document.getElementById('modal-penyesuaian');
    m.classList.add('hidden');
    m.classList.remove('flex');
}

function resetModal() {
    selectedProduk = null;
    selectedTipe   = '';

    // Reset langkah 1
    document.getElementById('input-kode').value = '';
    document.getElementById('dropdown-produk').classList.add('hidden');
    document.getElementById('produk-terpilih').classList.add('hidden');
    document.getElementById('produk-tidak-ada').classList.add('hidden');

    // Reset langkah 2
    document.querySelectorAll('input[name="tipe-penyesuaian"]').forEach(r => r.checked = false);
    document.getElementById('input-qty').value = '';
    document.getElementById('input-keterangan').value = '';
    document.getElementById('char-count').textContent = '0 karakter';
    document.getElementById('alert-error').classList.add('hidden');
    document.getElementById('preview-panel').classList.add('hidden');
    document.getElementById('qty-warning').classList.add('hidden');
    document.getElementById('ket-err').classList.add('hidden');

    tampilLangkah(1);
}

function tampilLangkah(n) {
    const isStep2 = n === 2;

    document.getElementById('panel-langkah-1').classList.toggle('hidden', isStep2);
    document.getElementById('panel-langkah-2').classList.toggle('hidden', !isStep2);
    document.getElementById('footer-step1').classList.toggle('hidden', isStep2);

    // footer-step2 tidak boleh punya class `flex` di HTML statis (conflict dengan `hidden`),
    // sehingga `flex` harus ditambah/dihapus via JS saat ditampilkan.
    const fs2 = document.getElementById('footer-step2');
    if (isStep2) {
        fs2.classList.remove('hidden');
        fs2.classList.add('flex');
    } else {
        fs2.classList.add('hidden');
        fs2.classList.remove('flex');
    }

    // Indikator langkah
    document.getElementById('modal-langkah-label').textContent =
        isStep2 ? 'Langkah 2 dari 2 — Isi Penyesuaian' : 'Langkah 1 dari 2 — Pilih Produk';
    document.getElementById('step-line').style.width = isStep2 ? '100%' : '0';
    document.getElementById('step-dot-2').className =
        isStep2
            ? 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold bg-indigo-600 text-white'
            : 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400';
    document.getElementById('step2-label-txt').className =
        isStep2 ? 'text-[10px] text-indigo-600 dark:text-indigo-400 font-medium' : 'text-[10px] text-gray-400 dark:text-gray-500';
}

function lanjutLangkah2() {
    if (!selectedProduk) return;
    // Isi mini-ringkasan di langkah 2
    document.getElementById('step2-nama').textContent = selectedProduk.nama_produk;
    document.getElementById('step2-stok').textContent = `Stok: ${selectedProduk.qty_stok} ${selectedProduk.satuan_dasar}`;
    document.getElementById('lbl-satuan-dasar').textContent = `(${selectedProduk.satuan_dasar})`;
    document.getElementById('lbl-satuan-unit').textContent = selectedProduk.satuan_dasar;
    tampilLangkah(2);
    document.querySelectorAll('input[name="tipe-penyesuaian"]')[0]?.focus();
}

function kembaliLangkah1() {
    tampilLangkah(1);
    document.getElementById('input-kode').focus();
}

// ─────────────────────────────────────────────────────────────────────────────
// LANGKAH 1 — CARI PRODUK
// ─────────────────────────────────────────────────────────────────────────────
function onKodeInput(val) {
    document.getElementById('produk-tidak-ada').classList.add('hidden');
    clearTimeout(kodeAutoTimer);

    if (val.length < 2) {
        document.getElementById('dropdown-produk').classList.add('hidden');
        return;
    }

    // Autocomplete nama setelah 350ms
    kodeAutoTimer = setTimeout(() => autocompleteNama(val), 350);
}

function onKodeKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const val = e.target.value.trim();
        if (val) cariByKode(val);
    }
}

async function cariByKode(kode) {
    setKodeLoading(true);
    document.getElementById('produk-tidak-ada').classList.add('hidden');
    document.getElementById('dropdown-produk').classList.add('hidden');

    try {
        // Cari di gudang produk endpoint — filter by kode atau nama eksak
        const { data: res } = await axios.get(`/api/gudang/produk?nama=${encodeURIComponent(kode)}&status=aktif&limit=10`);
        const list = res.data ?? [];

        // Jika ada yang kode_produknya cocok persis, pilih langsung
        const cocok = list.find(p => p.kode_produk?.toLowerCase() === kode.toLowerCase());
        if (cocok) {
            pilihProduk(cocok);
            return;
        }

        // Jika ada satu hasil, pilih langsung
        if (list.length === 1) {
            pilihProduk(list[0]);
            return;
        }

        // Jika banyak hasil, tampilkan dropdown
        if (list.length > 1) {
            tampilDropdown(list);
            return;
        }

        document.getElementById('produk-tidak-ada').classList.remove('hidden');
    } catch (err) {
        console.error('[cariByKode] gagal:', err.message);
    } finally {
        setKodeLoading(false);
    }
}

async function autocompleteNama(q) {
    try {
        const { data: res } = await axios.get(`/api/gudang/produk?nama=${encodeURIComponent(q)}&status=aktif&limit=8`);
        tampilDropdown(res.data ?? []);
    } catch (_) {}
}

function tampilDropdown(list) {
    const dd = document.getElementById('dropdown-produk');
    if (!list.length) {
        dd.classList.add('hidden');
        return;
    }
    dd.innerHTML = list.map(p => `
        <button type="button" onclick="pilihProdukById(${p.id})"
            class="w-full text-left px-4 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${esc(p.nama_produk)}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                <span class="font-mono">${esc(p.kode_produk)}</span>
                · Stok: <strong>${p.qty_stok}</strong> ${esc(p.satuan_dasar)}
            </p>
        </button>`).join('');
    // Simpan cache untuk pilih via id
    window._produkCache = Object.fromEntries(list.map(p => [p.id, p]));
    dd.classList.remove('hidden');
}

function pilihProdukById(id) {
    const p = (window._produkCache || {})[id];
    if (p) pilihProduk(p);
}

function pilihProduk(p) {
    selectedProduk = {
        id:          p.id,
        kode_produk: p.kode_produk ?? '',
        nama_produk: p.nama_produk ?? '',
        satuan_dasar:p.satuan_dasar ?? '',
        qty_stok:    p.qty_stok ?? 0,
    };

    document.getElementById('input-kode').value = p.kode_produk ?? p.nama_produk;
    document.getElementById('dropdown-produk').classList.add('hidden');

    // Kartu info produk
    document.getElementById('info-nama-produk').textContent = p.nama_produk ?? '—';
    document.getElementById('info-kode-produk').textContent = p.kode_produk ?? '—';
    document.getElementById('info-stok-nilai').textContent  = p.qty_stok ?? 0;
    document.getElementById('info-stok-satuan').textContent = p.satuan_dasar ?? '—';

    document.getElementById('produk-terpilih').classList.remove('hidden');
    document.getElementById('produk-tidak-ada').classList.add('hidden');
}

function resetProduk() {
    selectedProduk = null;
    document.getElementById('produk-terpilih').classList.add('hidden');
    document.getElementById('input-kode').value = '';
    document.getElementById('input-kode').focus();
}

function setKodeLoading(on) {
    document.getElementById('kode-spinner').classList.toggle('hidden', !on);
}

// ─────────────────────────────────────────────────────────────────────────────
// LANGKAH 2 — TIPE + QTY + KETERANGAN + PREVIEW
// ─────────────────────────────────────────────────────────────────────────────
function onTipeChange(val) {
    selectedTipe = val;
    updatePreview();
}

function updateCharCount() {
    const len = document.getElementById('input-keterangan').value.length;
    document.getElementById('char-count').textContent = `${len} karakter`;
    if (len >= 5) document.getElementById('ket-err').classList.add('hidden');
    updatePreview();
}

function updatePreview() {
    const tipe = selectedTipe;
    const qty  = parseInt(document.getElementById('input-qty').value) || 0;

    if (!selectedProduk || !tipe || qty <= 0) {
        document.getElementById('preview-panel').classList.add('hidden');
        document.getElementById('qty-warning').classList.add('hidden');
        return;
    }

    const isKurang = TIPE_KURANG.has(tipe);
    const stokBaru = isKurang ? selectedProduk.qty_stok - qty : selectedProduk.qty_stok + qty;
    const cfg      = TIPE_CONFIG[tipe];

    // Warning stok melebihi
    const overStok = isKurang && qty > selectedProduk.qty_stok;
    document.getElementById('qty-warning').classList.toggle('hidden', !overStok);

    // Preview panel
    const panel = document.getElementById('preview-panel');
    const borderCls = isKurang
        ? 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/10'
        : 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/10';
    panel.className = `rounded-xl border p-4 ${borderCls}`;

    // "Stok {nama_produk} akan berubah dari {stok_lama} menjadi {stok_baru} {satuan_dasar}"
    document.getElementById('preview-text').textContent =
        `Stok ${selectedProduk.nama_produk} akan ${isKurang ? 'berkurang' : 'bertambah'} sebanyak ${qty} ${selectedProduk.satuan_dasar}.`;

    document.getElementById('preview-stok-lama').textContent = selectedProduk.qty_stok;
    document.getElementById('preview-stok-baru').textContent = stokBaru;
    document.getElementById('preview-stok-baru').className   =
        `text-base font-mono font-bold ${stokBaru < 0 ? 'text-red-600 dark:text-red-400' : (isKurang ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400')}`;
    document.getElementById('preview-satuan').textContent = selectedProduk.satuan_dasar;

    panel.classList.remove('hidden');
}

// ─────────────────────────────────────────────────────────────────────────────
// SUBMIT
// ─────────────────────────────────────────────────────────────────────────────
async function submitPenyesuaian() {
    document.getElementById('alert-error').classList.add('hidden');

    const tipe       = selectedTipe;
    const qty        = parseInt(document.getElementById('input-qty').value) || 0;
    const keterangan = document.getElementById('input-keterangan').value.trim();

    // Validasi
    if (!selectedProduk) return showError('Pilih produk terlebih dahulu.');
    if (!tipe)           return showError('Pilih tipe penyesuaian.');
    if (qty < 1)         return showError('Jumlah harus minimal 1.');
    if (keterangan.length < 5) {
        document.getElementById('ket-err').classList.remove('hidden');
        return showError('Keterangan minimal 5 karakter.');
    }
    if (TIPE_KURANG.has(tipe) && qty > selectedProduk.qty_stok) {
        return showError(`Qty (${qty}) melebihi stok tersedia (${selectedProduk.qty_stok} ${selectedProduk.satuan_dasar}).`);
    }

    setBtnLoading(true);

    try {
        await axios.post('/api/gudang/penyesuaian-stok', {
            produk_id:        selectedProduk.id,
            tipe,
            qty_satuan_dasar: qty,
            keterangan,
        });

        tutupModal();
        // Toast sementara — toast final datang via WebSocket adjustment.completed
        if (window.showToast) window.showToast('Penyesuaian stok sedang diproses...', 'info');
        loadData(currentPage);

    } catch (err) {
        const errData = err.response?.data;
        const msg = errData?.errors
            ? Object.values(errData.errors).flat().join(' ')
            : (errData?.message ?? 'Gagal menyimpan penyesuaian stok.');
        showError(msg);
    } finally {
        setBtnLoading(false);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// WEBSOCKET — adjustment.completed dari user-{userId}
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('ws-event', e => {
    const { event: wsEvent, message, stokBaru, produkId } = e.detail ?? {};

    if (wsEvent === 'adjustment.completed') {
        if (window.showToast) window.showToast(message ?? 'Penyesuaian stok berhasil!', 'success');
        loadData(currentPage);
    }
    if (wsEvent === 'adjustment.failed') {
        if (window.showToast) window.showToast(e.detail?.message ?? 'Penyesuaian gagal diproses.', 'error');
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// UI HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function showSkeleton(show) {
    document.getElementById('tbl-skeleton').classList.toggle('hidden', !show);
    if (show) {
        document.getElementById('tbl-wrapper').classList.add('hidden');
        showPagination(false);
    }
}

function showEmpty() {
    document.getElementById('tbl-wrapper').classList.remove('hidden');
    document.getElementById('tbody-data').innerHTML = '';
    document.getElementById('tbl-empty').classList.remove('hidden');
    showPagination(false);
}

function showPagination(show) {
    const el = document.getElementById('pagination');
    if (show) {
        el.classList.remove('hidden');
        el.classList.add('flex');
    } else {
        el.classList.add('hidden');
        el.classList.remove('flex');
    }
}

function showError(msg) {
    const el = document.getElementById('alert-error');
    document.getElementById('alert-error-msg').textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function setBtnLoading(on) {
    document.getElementById('btn-submit').disabled = on;
    document.getElementById('btn-spinner').classList.toggle('hidden', !on);
}

function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// Klik backdrop tutup modal
document.getElementById('modal-penyesuaian')?.addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});
</script>
@endpush
