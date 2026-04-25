<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir — Toko Melati</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { fontFamily: { sans: ['Inter','ui-sans-serif','system-ui'] } } }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>(function(){if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');})();</script>
</head>

{{-- Layout full-screen split — tidak pakai sidebar (§10.5 Kasir Layout) --}}
<body class="h-full bg-gray-100 dark:bg-gray-900 font-sans antialiased overflow-hidden">

{{-- ══ HEADER KASIR (h-14) ══════════════════════════════════════════════════════ --}}
<header class="h-14 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 shrink-0">
    <div class="flex items-center gap-3">
        <div class="w-7 h-7 bg-indigo-600 rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Toko Melati</span>
        <span class="hidden sm:block text-xs text-gray-400 dark:text-gray-500">/ Transaksi</span>
    </div>

    <div class="flex items-center gap-3">
        {{-- WS indicator --}}
        <div class="flex items-center gap-1.5" title="Status real-time">
            <span class="relative flex h-2 w-2">
                <span id="ws-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gray-300 opacity-75"></span>
                <span id="ws-dot"  class="relative inline-flex rounded-full h-2 w-2 bg-gray-300"></span>
            </span>
            <span id="ws-label" class="hidden sm:block text-xs text-gray-500 dark:text-gray-400">Menghubungkan...</span>
        </div>

        {{-- Nama kasir --}}
        <span id="nama-kasir" class="text-xs font-medium text-gray-600 dark:text-gray-400 hidden sm:block">—</span>

        {{-- Dark mode toggle --}}
        <button onclick="toggleTheme()" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="hidden dark:block w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <svg class="block dark:hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>

        <a href="/kasir" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">← Dashboard</a>
    </div>
</header>

{{-- ══ SPLIT PANEL ══════════════════════════════════════════════════════════════ --}}
<div class="flex" style="height: calc(100vh - 3.5rem)">

    {{-- ── PANEL KIRI (~60%): Scan + Info Produk + Customer ──────────────────── --}}
    <div class="flex-1 flex flex-col overflow-y-auto bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 p-4 gap-4">

        {{-- Input scan/ketik kode produk --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                Scan / Ketik Kode Produk
            </label>
            <div class="flex gap-2">
                <input id="input-kode" type="text" autocomplete="off" autofocus
                    placeholder="Scan barcode atau ketik kode produk..."
                    class="flex-1 px-3 py-2.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors font-mono">
                <button id="btn-cari" onclick="cariProduk()"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg transition-colors shrink-0">
                    Cari
                </button>
            </div>
            <p id="err-scan" class="hidden mt-1.5 text-xs text-red-600 dark:text-red-400"></p>
        </div>

        {{-- Info produk hasil scan --}}
        <div id="panel-produk" class="hidden bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">

            {{-- Header produk --}}
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p id="produk-kode" class="text-xs font-mono text-gray-400 dark:text-gray-500"></p>
                    <h3 id="produk-nama" class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-0.5"></h3>
                </div>
                {{-- Badge stok (update real-time) --}}
                <span id="badge-stok" class="hidden text-xs font-semibold px-2.5 py-1 rounded-full"></span>
            </div>

            {{-- Pilih Satuan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Satuan</label>
                    <select id="select-satuan"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                        onchange="onSatuanChange()">
                        {{-- diisi JS --}}
                    </select>
                    {{-- Info hemat --}}
                    <p id="info-hemat" class="hidden text-xs text-green-600 dark:text-green-400 font-medium"></p>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Jumlah (Qty)</label>
                    <input id="input-qty" type="number" min="1" value="1"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                        oninput="onQtyChange()">
                    <p id="info-stok-satuan" class="text-xs text-gray-500 dark:text-gray-400"></p>
                </div>
            </div>

            {{-- Harga & Subtotal --}}
            <div class="flex items-center justify-between py-3 border-t border-gray-100 dark:border-gray-700 mb-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Harga per satuan</p>
                    <p id="info-harga" class="text-lg font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Subtotal</p>
                    <p id="info-subtotal" class="text-lg font-bold font-mono text-indigo-600 dark:text-indigo-400">—</p>
                </div>
            </div>

            {{-- Tombol tambah ke keranjang --}}
            <button id="btn-tambah" onclick="tambahKeKeranjang()"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah ke Keranjang
            </button>
        </div>

        {{-- Pilih Customer (opsional) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                Customer (Opsional)
            </label>
            <div class="relative">
                <input id="input-customer" type="text" autocomplete="off"
                    placeholder="Cari nama / nomor HP customer..."
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    oninput="searchCustomer(this.value)" onfocus="showCustomerDrop()"
                    onblur="setTimeout(hideCustomerDrop, 200)">
                {{-- Dropdown hasil pencarian --}}
                <div id="dropdown-customer"
                    class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden">
                    <div id="customer-list"></div>
                </div>
            </div>
            {{-- Customer terpilih --}}
            <div id="customer-selected" class="hidden mt-2 flex items-center justify-between">
                <span id="customer-nama" class="text-sm font-medium text-indigo-600 dark:text-indigo-400"></span>
                <button onclick="clearCustomer()" class="text-xs text-gray-400 hover:text-red-500 transition-colors">×Hapus</button>
            </div>
        </div>

    </div>{{-- /panel kiri --}}

    {{-- ── PANEL KANAN (~40%): Keranjang + Bayar ──────────────────────────────── --}}
    <div class="w-96 xl:w-[420px] flex flex-col bg-white dark:bg-gray-800 shrink-0">

        {{-- Header keranjang --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shrink-0">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Keranjang</h2>
            <span id="badge-items"
                class="text-xs font-bold px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-full">0 item</span>
        </div>

        {{-- Tabel keranjang --}}
        <div class="flex-1 overflow-y-auto">
            {{-- Empty state --}}
            <div id="cart-empty" class="h-full flex flex-col items-center justify-center p-8 text-center">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-sm font-medium text-gray-400 dark:text-gray-500">Keranjang kosong</p>
                <p class="text-xs text-gray-300 dark:text-gray-600 mt-1">Scan produk untuk mulai transaksi</p>
            </div>

            {{-- Item list --}}
            <div id="cart-items" class="hidden divide-y divide-gray-100 dark:divide-gray-700">
                {{-- Diisi JS --}}
            </div>
        </div>

        {{-- Footer: total + bayar + tombol proses --}}
        <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-3 shrink-0">

            {{-- Summary --}}
            <div class="space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                    <span id="lbl-subtotal" class="font-mono text-gray-900 dark:text-gray-100">Rp 0</span>
                </div>
                <div class="flex justify-between text-base font-bold border-t border-gray-200 dark:border-gray-700 pt-1 mt-1">
                    <span class="text-gray-900 dark:text-gray-100">TOTAL</span>
                    <span id="lbl-total" class="font-mono text-indigo-600 dark:text-indigo-400">Rp 0</span>
                </div>
            </div>

            {{-- Metode bayar --}}
            <div class="grid grid-cols-3 gap-2">
                @foreach(['tunai'=>'Tunai','transfer'=>'Transfer','qris'=>'QRIS'] as $val => $label)
                <label class="metode-label cursor-pointer">
                    <input type="radio" name="metode_bayar" value="{{ $val }}"
                        class="sr-only peer" {{ $val === 'tunai' ? 'checked' : '' }}>
                    <div class="text-center px-2 py-1.5 text-xs font-medium border rounded-lg border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 peer-checked:text-indigo-700 dark:peer-checked:text-indigo-300 transition-colors">
                        {{ $label }}
                    </div>
                </label>
                @endforeach
            </div>

            {{-- Input bayar (untuk tunai) --}}
            <div id="panel-bayar" class="space-y-1">
                <label class="text-xs text-gray-500 dark:text-gray-400">Uang Diterima</label>
                <input id="input-bayar" type="number" min="0" placeholder="0"
                    class="block w-full px-3 py-2 text-sm font-mono bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    oninput="hitungKembalian()">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Kembalian</span>
                    <span id="lbl-kembalian" class="font-mono font-semibold text-green-600 dark:text-green-400">Rp 0</span>
                </div>
            </div>

            {{-- Tombol Proses Transaksi --}}
            <button id="btn-proses" onclick="prosesTransaksi()"
                disabled
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 text-white text-sm font-bold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-proses" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-proses">Proses Transaksi</span>
            </button>

            {{-- Status Kafka (muncul setelah 202) --}}
            <div id="status-kafka" class="hidden text-center">
                <div class="flex items-center justify-center gap-2 py-2">
                    <svg class="animate-spin w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span class="text-sm text-amber-600 dark:text-amber-400 font-medium">Memproses transaksi...</span>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Menunggu konfirmasi dari server</p>
            </div>

            {{-- Tombol bersihkan keranjang --}}
            <button onclick="resetKeranjang()"
                class="w-full text-xs text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors py-1">
                Bersihkan Keranjang
            </button>
        </div>
    </div>{{-- /panel kanan --}}

</div>{{-- /split --}}

{{-- ══ MODAL STRUK ══════════════════════════════════════════════════════════════ --}}
<div id="modal-struk" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
        {{-- Header modal --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-green-50 dark:bg-green-900/20">
            <div class="flex items-center gap-2">
                <span class="text-green-600 dark:text-green-400 text-xl">✓</span>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Transaksi Berhasil!</h3>
            </div>
            <button onclick="tutupModal()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        {{-- Body --}}
        <div class="px-6 py-4">
            <p id="modal-no-trx" class="text-sm text-gray-600 dark:text-gray-400">No. Transaksi: —</p>
            <div id="modal-struk-konten" class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Struk sedang dimuat...
            </div>
        </div>
        {{-- Footer --}}
        <div class="flex gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="cetakStruk()"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Struk
            </button>
            <button onclick="transaksiBerikutnya()"
                class="flex-1 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Transaksi Baru
            </button>
        </div>
    </div>
</div>

{{-- ══ MODAL ERROR ══════════════════════════════════════════════════════════════ --}}
<div id="modal-error" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
            <div class="flex items-center gap-2">
                <span class="text-red-600 dark:text-red-400 text-xl">✕</span>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Transaksi Gagal</h3>
            </div>
        </div>
        <div class="px-6 py-4">
            <p id="modal-error-msg" class="text-sm text-gray-600 dark:text-gray-400"></p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalError()"
                class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Tutup & Coba Lagi
            </button>
        </div>
    </div>
</div>

{{-- ══ HIDDEN IFRAME untuk cetak struk ══════════════════════════════════════════ --}}
<iframe id="iframe-struk" class="hidden" title="Struk"></iframe>

<script>
// ── AUTH ──────────────────────────────────────────────────────────────────────
const authToken = localStorage.getItem('auth_token');
const authUser  = JSON.parse(localStorage.getItem('auth_user') || 'null');
const authRole  = localStorage.getItem('auth_role');

if (!authToken || !authUser) { window.location.replace('/login'); }
if (!['kasir', 'owner'].includes(authRole)) { window.location.replace('/login'); }

axios.defaults.headers.common['Authorization'] = 'Bearer ' + authToken;

axios.interceptors.response.use(res => res, err => {
    if (err.response?.status === 401) {
        ['auth_token','auth_user','auth_role'].forEach(k => localStorage.removeItem(k));
        window.location.replace('/login');
    }
    return Promise.reject(err);
});

document.getElementById('nama-kasir').textContent = authUser?.nama || '—';

// ── STATE ─────────────────────────────────────────────────────────────────────
let cart           = [];        // item keranjang
let currentProduk  = null;      // produk sedang ditampilkan
let selectedCustomer = null;    // {id, nama}
let pendingTrxId   = null;      // id setelah 202
let strutHtml      = '';        // HTML struk untuk cetak

// ── DARK MODE ─────────────────────────────────────────────────────────────────
function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

// ── SCAN / CARI PRODUK ────────────────────────────────────────────────────────
document.getElementById('input-kode').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') cariProduk();
});

async function cariProduk() {
    const kode = document.getElementById('input-kode').value.trim();
    if (!kode) return;

    setErrScan('');
    document.getElementById('panel-produk').classList.add('hidden');

    try {
        const { data } = await axios.get(`/api/kasir/produk/${encodeURIComponent(kode)}/stok`);
        currentProduk = data.data;
        tampilkanProduk(currentProduk);
        document.getElementById('input-kode').value = '';
        document.getElementById('input-qty').focus();
    } catch (err) {
        const msg = err.response?.data?.message || 'Produk tidak ditemukan';
        setErrScan(msg);
    }
}

function tampilkanProduk(p) {
    document.getElementById('produk-kode').textContent = p.kode_produk;
    document.getElementById('produk-nama').textContent = p.nama_produk;

    // Isi dropdown satuan
    const sel = document.getElementById('select-satuan');
    sel.innerHTML = p.satuan_tersedia.map(s =>
        `<option value="${s.id}"
            data-nama="${s.nama_satuan}"
            data-isi="${s.isi}"
            data-harga="${s.harga_jual}"
            data-stok="${s.stok_dalam_satuan}"
            data-hemat="${s.hemat_persen}"
            ${s.is_default ? 'selected' : ''}>
            ${s.nama_satuan}${s.hemat_persen > 0 ? ' (hemat '+s.hemat_persen+'%)' : ''}
        </option>`
    ).join('');

    document.getElementById('panel-produk').classList.remove('hidden');
    document.getElementById('input-qty').value = 1;
    onSatuanChange();
}

function onSatuanChange() {
    if (!currentProduk) return;
    const opt   = document.getElementById('select-satuan').selectedOptions[0];
    if (!opt) return;

    const harga    = parseFloat(opt.dataset.harga);
    const stok     = parseInt(opt.dataset.stok);
    const hemat    = parseFloat(opt.dataset.hemat);
    const namaSat  = opt.dataset.nama;

    // Update harga display
    document.getElementById('info-harga').textContent = 'Rp ' + fmt(harga);

    // Stok tersedia untuk satuan ini
    const infoStok = document.getElementById('info-stok-satuan');
    infoStok.textContent = `Tersedia: ${stok} ${namaSat}`;

    // Badge stok (logika warning di JS — bukan Backend, sesuai AD-07)
    const badge = document.getElementById('badge-stok');
    if (stok === 0) {
        badge.textContent = 'Stok Habis';
        badge.className   = 'text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        badge.classList.remove('hidden');
        document.getElementById('btn-tambah').disabled = true;
    } else if (stok <= 5) {
        badge.textContent = 'Stok Menipis';
        badge.className   = 'text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
        badge.classList.remove('hidden');
        document.getElementById('btn-tambah').disabled = false;
    } else {
        badge.classList.add('hidden');
        document.getElementById('btn-tambah').disabled = false;
    }

    // Info hemat
    const infoHemat = document.getElementById('info-hemat');
    if (hemat > 0) {
        infoHemat.textContent = `Hemat ${hemat}% dibanding satuan dasar`;
        infoHemat.classList.remove('hidden');
    } else {
        infoHemat.classList.add('hidden');
    }

    onQtyChange();
}

function onQtyChange() {
    const opt      = document.getElementById('select-satuan').selectedOptions[0];
    if (!opt) return;
    const harga    = parseFloat(opt.dataset.harga);
    const qty      = parseInt(document.getElementById('input-qty').value) || 1;
    const subtotal = harga * qty;
    document.getElementById('info-subtotal').textContent = 'Rp ' + fmt(subtotal);
}

// ── KERANJANG ─────────────────────────────────────────────────────────────────
function tambahKeKeranjang() {
    if (!currentProduk) return;

    const opt      = document.getElementById('select-satuan').selectedOptions[0];
    const qty      = parseInt(document.getElementById('input-qty').value) || 1;
    const harga    = parseFloat(opt.dataset.harga);
    const stok     = parseInt(opt.dataset.stok);

    if (stok === 0) return;
    if (qty > stok) {
        setErrScan(`Stok ${opt.dataset.nama} hanya ${stok} unit`);
        return;
    }

    // Cek duplikat (produk + satuan sama → tambah qty)
    const existing = cart.find(c =>
        c.produk_id === currentProduk.id && c.produk_satuan_id === parseInt(opt.value)
    );

    if (existing) {
        existing.qty += qty;
    } else {
        cart.push({
            produk_id:       currentProduk.id,
            produk_satuan_id:parseInt(opt.value),
            kode_produk:     currentProduk.kode_produk,
            nama_produk:     currentProduk.nama_produk,
            satuan_dasar:    currentProduk.satuan_dasar,
            nama_satuan:     opt.dataset.nama,
            isi_satuan:      parseInt(opt.dataset.isi),
            qty,
            harga_satuan:    harga,
            stok_dalam_satuan: stok,
        });
    }

    renderKeranjang();
    document.getElementById('panel-produk').classList.add('hidden');
    document.getElementById('input-kode').focus();
    currentProduk = null;
    setErrScan('');
}

function renderKeranjang() {
    const totalItems = cart.reduce((s, c) => s + c.qty, 0);
    const totalHarga = cart.reduce((s, c) => s + c.qty * c.harga_satuan, 0);

    document.getElementById('badge-items').textContent = totalItems + ' item';

    const empty = document.getElementById('cart-empty');
    const items = document.getElementById('cart-items');

    if (cart.length === 0) {
        empty.classList.remove('hidden');
        items.classList.add('hidden');
        document.getElementById('btn-proses').disabled = true;
    } else {
        empty.classList.add('hidden');
        items.classList.remove('hidden');

        items.innerHTML = cart.map((c, i) => `
            <div class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">${c.nama_produk}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">${c.kode_produk} · ${c.nama_satuan}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Rp ${fmt(c.harga_satuan)} × ${c.qty}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-semibold font-mono text-gray-900 dark:text-gray-100">Rp ${fmt(c.qty * c.harga_satuan)}</p>
                    <div class="flex items-center gap-1 mt-1 justify-end">
                        <button onclick="ubahQty(${i}, -1)" class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors text-base font-bold">−</button>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 min-w-[1.5rem] text-center">${c.qty}</span>
                        <button onclick="ubahQty(${i}, +1)" class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors text-base font-bold">+</button>
                        <button onclick="hapusItem(${i})" class="w-6 h-6 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors ml-1">×</button>
                    </div>
                </div>
            </div>`).join('');

        document.getElementById('btn-proses').disabled = false;
    }

    document.getElementById('lbl-subtotal').textContent = 'Rp ' + fmt(totalHarga);
    document.getElementById('lbl-total').textContent    = 'Rp ' + fmt(totalHarga);
    hitungKembalian();
}

function ubahQty(idx, delta) {
    cart[idx].qty = Math.max(1, cart[idx].qty + delta);
    renderKeranjang();
}

function hapusItem(idx) {
    cart.splice(idx, 1);
    renderKeranjang();
}

function resetKeranjang() {
    cart = [];
    selectedCustomer = null;
    pendingTrxId = null;
    document.getElementById('input-customer').value = '';
    document.getElementById('customer-selected').classList.add('hidden');
    document.getElementById('input-bayar').value = '';
    document.getElementById('status-kafka').classList.add('hidden');
    document.getElementById('btn-proses').textContent = 'Proses Transaksi';
    renderKeranjang();
}

function hitungKembalian() {
    const total  = cart.reduce((s, c) => s + c.qty * c.harga_satuan, 0);
    const bayar  = parseFloat(document.getElementById('input-bayar').value) || 0;
    const kembal = Math.max(0, bayar - total);
    document.getElementById('lbl-kembalian').textContent = 'Rp ' + fmt(kembal);
}

// ── CUSTOMER SEARCH ───────────────────────────────────────────────────────────
let customerTimer = null;

function searchCustomer(q) {
    clearTimeout(customerTimer);
    if (q.length < 2) { hideCustomerDrop(); return; }
    customerTimer = setTimeout(async () => {
        try {
            const { data } = await axios.get(`/api/kasir/customer?search=${encodeURIComponent(q)}&limit=5`);
            renderCustomerDrop(data.data || []);
        } catch (_) {}
    }, 300);
}

function renderCustomerDrop(items) {
    const list = document.getElementById('customer-list');
    if (!items.length) {
        list.innerHTML = '<p class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500">Tidak ada customer</p>';
    } else {
        list.innerHTML = items.map(c => `
            <button class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                onclick="pilihCustomer(${c.id}, '${c.nama.replace(/'/g, "\\'")}', '${c.no_telp}')">
                <span class="font-medium text-gray-900 dark:text-gray-100">${c.nama}</span>
                <span class="text-xs text-gray-400 ml-2">${c.no_telp}</span>
            </button>`).join('');
    }
    showCustomerDrop();
}

function pilihCustomer(id, nama, noTelp) {
    selectedCustomer = { id, nama };
    document.getElementById('input-customer').value = nama + ' - ' + noTelp;
    document.getElementById('customer-nama').textContent = nama;
    document.getElementById('customer-selected').classList.remove('hidden');
    hideCustomerDrop();
}

function clearCustomer() {
    selectedCustomer = null;
    document.getElementById('input-customer').value = '';
    document.getElementById('customer-selected').classList.add('hidden');
}

function showCustomerDrop() { document.getElementById('dropdown-customer').classList.remove('hidden'); }
function hideCustomerDrop() { document.getElementById('dropdown-customer').classList.add('hidden'); }

// ── PROSES TRANSAKSI ──────────────────────────────────────────────────────────
async function prosesTransaksi() {
    if (!cart.length) return;

    const total    = cart.reduce((s, c) => s + c.qty * c.harga_satuan, 0);
    const metode   = document.querySelector('input[name="metode_bayar"]:checked')?.value || 'tunai';
    const bayar    = parseFloat(document.getElementById('input-bayar').value) || total;
    const kembalian = Math.max(0, bayar - total);

    // Validasi bayar untuk tunai
    if (metode === 'tunai' && bayar < total) {
        alert('Uang yang diterima kurang dari total transaksi');
        return;
    }

    setLoading(true);
    document.getElementById('status-kafka').classList.add('hidden');

    const payload = {
        items:       cart.map(c => ({
            produk_id:        c.produk_id,
            produk_satuan_id: c.produk_satuan_id,
            nama_satuan:      c.nama_satuan,
            isi_satuan:       c.isi_satuan,
            qty:              c.qty,
            harga_satuan:     c.harga_satuan,
            diskon_item:      0,
        })),
        customer_id:  selectedCustomer?.id || null,
        metode_bayar: metode,
        subtotal:     total,
        diskon:       0,
        total:        total,
        bayar:        bayar,
        kembalian:    kembalian,
    };

    try {
        const { data } = await axios.post('/api/kasir/transaksi', payload);
        // HTTP 202 — transaksi diterima, menunggu Kafka Consumer
        pendingTrxId = data.id_transaksi;
        setLoading(false);
        document.getElementById('status-kafka').classList.remove('hidden');
        document.getElementById('btn-proses').disabled = true;
        document.getElementById('lbl-proses').textContent = 'Menunggu Konfirmasi...';

    } catch (err) {
        setLoading(false);
        const msg = err.response?.data?.message || 'Gagal memproses transaksi';
        tampilkanError(msg);
    }
}

function setLoading(loading) {
    const btn     = document.getElementById('btn-proses');
    const spinner = document.getElementById('spinner-proses');
    const lbl     = document.getElementById('lbl-proses');
    btn.disabled  = loading || !cart.length;
    spinner.classList.toggle('hidden', !loading);
    lbl.textContent = loading ? 'Memproses...' : 'Proses Transaksi';
}

// ── WEBSOCKET EVENTS ──────────────────────────────────────────────────────────
// WS sudah diinisialisasi di app.blade.php, tapi halaman ini standalone.
// Setup WS sendiri karena tidak extend layouts.app.

let wsConn = null;
let wsTimer = null;

function connectWS() {
    if (wsConn && wsConn.readyState === WebSocket.OPEN) return;
    setWsState('connecting');
    wsConn = new WebSocket('ws://' + location.host + '/ws?channel=stok-global');

    wsConn.addEventListener('open', function () {
        setWsState('connected');
        if (wsTimer) { clearTimeout(wsTimer); wsTimer = null; }
        // Subscribe ke channel personal kasir untuk notifikasi transaksi
        if (authUser?.id) {
            wsConn.send(JSON.stringify({ action: 'subscribe', channel: 'user-' + authUser.id }));
        }
    });

    wsConn.addEventListener('message', function (e) {
        try {
            const data = JSON.parse(e.data);
            handleWsEvent(data);
        } catch (_) {}
    });

    wsConn.addEventListener('close', function () {
        setWsState('disconnected');
        wsTimer = setTimeout(connectWS, 3000);
    });
    wsConn.addEventListener('error', function () { setWsState('disconnected'); });
}

function setWsState(state) {
    const ping  = document.getElementById('ws-ping');
    const dot   = document.getElementById('ws-dot');
    const label = document.getElementById('ws-label');
    const cfg   = { connected:{c:'green',t:'Real-time aktif'}, disconnected:{c:'red',t:'Terputus'}, connecting:{c:'gray',t:'Menghubungkan...'} }[state] || {c:'gray',t:''};
    ping.className  = `animate-ping absolute inline-flex h-full w-full rounded-full bg-${cfg.c}-400 opacity-75`;
    dot.className   = `relative inline-flex rounded-full h-2 w-2 bg-${cfg.c}-500`;
    if (label) label.textContent = cfg.t;
}

function handleWsEvent(data) {
    switch (data.event) {
        case 'transaction.completed':
            // Hanya proses jika ini transaksi kita yang pending
            if (data.transaksiId === pendingTrxId || pendingTrxId) {
                document.getElementById('status-kafka').classList.add('hidden');
                tampilkanSukses(data.transaksiId || pendingTrxId);
            }
            break;

        case 'transaction.failed':
            if (data.transaksiId === pendingTrxId || pendingTrxId) {
                document.getElementById('status-kafka').classList.add('hidden');
                document.getElementById('btn-proses').disabled = false;
                document.getElementById('lbl-proses').textContent = 'Proses Transaksi';
                pendingTrxId = null;
                tampilkanError(data.message || 'Stok tidak mencukupi');
            }
            break;

        case 'stock.updated':
            // Update stok_dalam_satuan di keranjang (referensi informasi)
            if (data.updates && data.updates.length) {
                data.updates.forEach(u => {
                    cart.forEach(item => {
                        if (item.produk_id === u.produkId) {
                            item.stok_dalam_satuan = Math.floor(u.stokBaru / item.isi_satuan);
                        }
                    });
                });
            }
            break;
    }
}

// ── STRUK ─────────────────────────────────────────────────────────────────────
async function tampilkanSukses(trxId) {
    pendingTrxId = null;
    document.getElementById('modal-no-trx').textContent = 'No. Transaksi: #' + trxId;
    document.getElementById('modal-struk').classList.remove('hidden');

    // Fetch HTML struk
    try {
        const { data } = await axios.get(`/api/kasir/transaksi/${trxId}/struk`);
        strutHtml = data.data.html;
        document.getElementById('modal-struk-konten').innerHTML =
            '<p class="text-green-600 dark:text-green-400 font-medium">Struk siap dicetak.</p>';
    } catch (err) {
        strutHtml = '';
        document.getElementById('modal-struk-konten').textContent = 'Gagal memuat struk.';
    }
}

function cetakStruk() {
    if (!strutHtml) return;
    // Tulis HTML struk ke iframe tersembunyi, lalu trigger print
    const iframe = document.getElementById('iframe-struk');
    iframe.srcdoc = strutHtml;
    iframe.onload = function () {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };
}

function tutupModal() {
    document.getElementById('modal-struk').classList.add('hidden');
}

function transaksiBerikutnya() {
    tutupModal();
    resetKeranjang();
    document.getElementById('input-kode').focus();
}

function tampilkanError(msg) {
    document.getElementById('modal-error-msg').textContent = msg;
    document.getElementById('modal-error').classList.remove('hidden');
}

function tutupModalError() {
    document.getElementById('modal-error').classList.add('hidden');
}

// ── UTILITAS ──────────────────────────────────────────────────────────────────
function fmt(n) { return new Intl.NumberFormat('id-ID').format(n); }
function setErrScan(msg) {
    const el = document.getElementById('err-scan');
    el.textContent = msg;
    msg ? el.classList.remove('hidden') : el.classList.add('hidden');
}

// ── INIT ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    connectWS();
    renderKeranjang();
    document.getElementById('input-kode').focus();

    // Sembunyikan panel bayar jika non-tunai (transfer/qris tidak perlu input bayar)
    document.querySelectorAll('input[name="metode_bayar"]').forEach(function (r) {
        r.addEventListener('change', function () {
            const panel = document.getElementById('panel-bayar');
            panel.style.display = this.value === 'tunai' ? 'block' : 'none';
        });
    });
});
</script>
</body>
</html>
