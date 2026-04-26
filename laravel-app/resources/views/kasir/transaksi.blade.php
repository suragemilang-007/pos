@extends('layouts.kasir')

@section('title', 'Transaksi Baru')
@section('page-title', 'Transaksi')

@section('content')
{{-- ══ SPLIT PANEL (flex row memenuhi sisa viewport setelah header h-14) ══════ --}}
<div class="flex h-full">

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
                {{-- Badge stok (diupdate real-time via WS) --}}
                <span id="badge-stok" class="hidden text-xs font-semibold px-2.5 py-1 rounded-full"></span>
            </div>

            {{-- Pilih Satuan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Satuan</label>
                    <select id="select-satuan"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                        onchange="onSatuanChange()">
                        {{-- Diisi JS setelah scan produk --}}
                    </select>
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
                {{-- Dropdown hasil pencarian customer --}}
                <div id="dropdown-customer"
                    class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden">
                    <div id="customer-list"></div>
                </div>
            </div>
            {{-- Customer terpilih --}}
            <div id="customer-selected" class="hidden mt-2 flex items-center justify-between">
                <span id="customer-nama" class="text-sm font-medium text-indigo-600 dark:text-indigo-400"></span>
                <button onclick="clearCustomer()" class="text-xs text-gray-400 hover:text-red-500 transition-colors">× Hapus</button>
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

        {{-- Footer: total + metode bayar + tombol proses --}}
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

            {{-- Input uang diterima (hanya untuk tunai) --}}
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
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-green-50 dark:bg-green-900/20">
            <div class="flex items-center gap-2">
                <span class="text-green-600 dark:text-green-400 text-xl">✓</span>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Transaksi Berhasil!</h3>
            </div>
            <button onclick="tutupModal()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="px-6 py-4">
            <p id="modal-no-trx" class="text-sm text-gray-600 dark:text-gray-400">No. Transaksi: —</p>
            <div id="modal-struk-konten" class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Struk sedang dimuat...
            </div>
        </div>
        <div class="flex gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="cetakStruk()"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
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

{{-- Hidden iframe untuk cetak struk via print() --}}
<iframe id="iframe-struk" class="hidden" title="Struk"></iframe>
@endsection

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
// authToken, authUser, authRole, axios config, wsConn, toggleTheme → sudah di layouts/kasir.blade.php
let cart            = [];
let currentProduk   = null;
let selectedCustomer= null;
let pendingTrxId    = null;
let strutHtml       = '';
let pollTimer       = null;  // fallback polling saat WS miss event

// ── Polling Fallback ──────────────────────────────────────────────────────────
// Diaktifkan setelah 202 diterima. Cek status transaksi setiap 3 detik.
// Menangani kasus WS reconnect (race condition antara consumer push dan WS disconnect).
function startPolling() {
    if (pollTimer) return;
    pollTimer = setInterval(async function () {
        if (!pendingTrxId) { stopPolling(); return; }
        try {
            const { data } = await axios.get(`/api/kasir/transaksi/${pendingTrxId}`);
            const status   = data.data?.status;
            if (status === 'COMPLETED') {
                stopPolling();
                document.getElementById('status-kafka').classList.add('hidden');
                tampilkanSukses(pendingTrxId);
            } else if (status === 'FAILED') {
                stopPolling();
                document.getElementById('status-kafka').classList.add('hidden');
                document.getElementById('btn-proses').disabled = false;
                document.getElementById('lbl-proses').textContent = 'Proses Transaksi';
                const id = pendingTrxId;
                pendingTrxId = null;
                tampilkanError(`Transaksi #${id} gagal (stok tidak mencukupi)`);
            }
            // Status masih PENDING → tunggu interval berikutnya
        } catch (_) { /* abaikan error jaringan, coba lagi di interval berikutnya */ }
    }, 3000);
}

function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

// Dipanggil oleh layout saat WS berhasil reconnect — cek pending transaction
function onWsReconnect() {
    if (pendingTrxId) startPolling();
}

// ── Scan / Cari Produk ────────────────────────────────────────────────────────
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
        setErrScan(err.response?.data?.message || 'Produk tidak ditemukan');
    }
}

function tampilkanProduk(p) {
    document.getElementById('produk-kode').textContent = p.kode_produk;
    document.getElementById('produk-nama').textContent = p.nama_produk;

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
    const opt = document.getElementById('select-satuan').selectedOptions[0];
    if (!opt) return;

    const harga   = parseFloat(opt.dataset.harga);
    const stok    = parseInt(opt.dataset.stok);
    const hemat   = parseFloat(opt.dataset.hemat);
    const namaSat = opt.dataset.nama;

    document.getElementById('info-harga').textContent = 'Rp ' + fmt(harga);
    document.getElementById('info-stok-satuan').textContent = `Tersedia: ${stok} ${namaSat}`;

    // Badge stok (§10.6E) — warning jika menipis, merah jika habis
    const badge = document.getElementById('badge-stok');
    const btnTambah = document.getElementById('btn-tambah');
    if (stok === 0) {
        badge.textContent = 'Stok Habis';
        badge.className   = 'text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        badge.classList.remove('hidden');
        btnTambah.disabled = true;
    } else if (stok <= 5) {
        badge.textContent = 'Stok Menipis';
        badge.className   = 'text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
        badge.classList.remove('hidden');
        btnTambah.disabled = false;
    } else {
        badge.classList.add('hidden');
        btnTambah.disabled = false;
    }

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
    const opt = document.getElementById('select-satuan').selectedOptions[0];
    if (!opt) return;
    const harga    = parseFloat(opt.dataset.harga);
    const qty      = parseInt(document.getElementById('input-qty').value) || 1;
    document.getElementById('info-subtotal').textContent = 'Rp ' + fmt(harga * qty);
}

// ── Keranjang ─────────────────────────────────────────────────────────────────
function tambahKeKeranjang() {
    if (!currentProduk) return;

    const opt   = document.getElementById('select-satuan').selectedOptions[0];
    const qty   = parseInt(document.getElementById('input-qty').value) || 1;
    const harga = parseFloat(opt.dataset.harga);
    const stok  = parseInt(opt.dataset.stok);

    if (stok === 0) return;
    if (qty > stok) { setErrScan(`Stok ${opt.dataset.nama} hanya ${stok} unit`); return; }

    // Jika produk + satuan sudah ada di keranjang → tambah qty saja
    const existing = cart.find(c =>
        c.produk_id === currentProduk.id && c.produk_satuan_id === parseInt(opt.value)
    );

    if (existing) {
        existing.qty += qty;
    } else {
        cart.push({
            produk_id:          currentProduk.id,
            produk_satuan_id:   parseInt(opt.value),
            kode_produk:        currentProduk.kode_produk,
            nama_produk:        currentProduk.nama_produk,
            satuan_dasar:       currentProduk.satuan_dasar,
            nama_satuan:        opt.dataset.nama,
            isi_satuan:         parseInt(opt.dataset.isi),
            qty,
            harga_satuan:       harga,
            stok_dalam_satuan:  stok,
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
    stopPolling();
    cart = [];
    selectedCustomer = null;
    pendingTrxId = null;
    document.getElementById('input-customer').value = '';
    document.getElementById('customer-selected').classList.add('hidden');
    document.getElementById('input-bayar').value = '';
    document.getElementById('status-kafka').classList.add('hidden');
    document.getElementById('lbl-proses').textContent = 'Proses Transaksi';
    renderKeranjang();
}

function hitungKembalian() {
    const total  = cart.reduce((s, c) => s + c.qty * c.harga_satuan, 0);
    const bayar  = parseFloat(document.getElementById('input-bayar').value) || 0;
    document.getElementById('lbl-kembalian').textContent = 'Rp ' + fmt(Math.max(0, bayar - total));
}

// ── Customer Search ───────────────────────────────────────────────────────────
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
    list.innerHTML = !items.length
        ? '<p class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500">Tidak ada customer</p>'
        : items.map(c => `
            <button class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                onclick="pilihCustomer(${c.id}, '${c.nama.replace(/'/g, "\\'")}', '${c.no_telp}')">
                <span class="font-medium text-gray-900 dark:text-gray-100">${c.nama}</span>
                <span class="text-xs text-gray-400 ml-2">${c.no_telp}</span>
            </button>`).join('');
    showCustomerDrop();
}

function pilihCustomer(id, nama, noTelp) {
    selectedCustomer = { id, nama };
    document.getElementById('input-customer').value    = nama + ' - ' + noTelp;
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

// ── Proses Transaksi ──────────────────────────────────────────────────────────
async function prosesTransaksi() {
    if (!cart.length) return;

    const total    = cart.reduce((s, c) => s + c.qty * c.harga_satuan, 0);
    const metode   = document.querySelector('input[name="metode_bayar"]:checked')?.value || 'tunai';
    const bayar    = parseFloat(document.getElementById('input-bayar').value) || total;
    const kembalian = Math.max(0, bayar - total);

    if (metode === 'tunai' && bayar < total) {
        alert('Uang yang diterima kurang dari total transaksi');
        return;
    }

    setLoading(true);
    document.getElementById('status-kafka').classList.add('hidden');

    const payload = {
        items: cart.map(c => ({
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
        // 202 Accepted — Kafka Consumer yang menyelesaikan proses
        pendingTrxId = data.id_transaksi;
        setLoading(false);
        document.getElementById('status-kafka').classList.remove('hidden');
        document.getElementById('btn-proses').disabled = true;
        document.getElementById('lbl-proses').textContent = 'Menunggu Konfirmasi...';
        startPolling(); // Mulai polling sebagai fallback jika WS miss event
    } catch (err) {
        setLoading(false);
        tampilkanError(err.response?.data?.message || 'Gagal memproses transaksi');
    }
}

function setLoading(loading) {
    const btn     = document.getElementById('btn-proses');
    const spinner = document.getElementById('spinner-proses');
    btn.disabled  = loading || !cart.length;
    spinner.classList.toggle('hidden', !loading);
    document.getElementById('lbl-proses').textContent = loading ? 'Memproses...' : 'Proses Transaksi';
}

// ── WebSocket Event Listeners ─────────────────────────────────────────────────
// Menggunakan DOM CustomEvent yang di-dispatch oleh layouts/kasir.blade.php
// sehingga tidak ada duplikasi kode WS setup di sini.

document.addEventListener('ws:transaction-completed', function (e) {
    const data = e.detail;
    // Cukup cek pendingTrxId — jika ada transaksi pending, anggap ini miliknya
    if (pendingTrxId) {
        stopPolling();
        document.getElementById('status-kafka').classList.add('hidden');
        tampilkanSukses(data.transaksiId || pendingTrxId);
    }
});

document.addEventListener('ws:transaction-failed', function (e) {
    const data = e.detail;
    if (pendingTrxId) {
        stopPolling();
        document.getElementById('status-kafka').classList.add('hidden');
        document.getElementById('btn-proses').disabled = false;
        document.getElementById('lbl-proses').textContent = 'Proses Transaksi';
        pendingTrxId = null;
        tampilkanError(data.message || 'Stok tidak mencukupi');
    }
});

document.addEventListener('stok-updated', function (e) {
    const updates = e.detail || [];
    updates.forEach(u => {
        cart.forEach(item => {
            if (item.produk_id === u.produkId) {
                item.stok_dalam_satuan = Math.floor(u.stokBaru / item.isi_satuan);
            }
        });
    });
});

// ── Struk ─────────────────────────────────────────────────────────────────────
async function tampilkanSukses(trxId) {
    pendingTrxId = null;
    document.getElementById('modal-no-trx').textContent = 'No. Transaksi: #' + trxId;
    document.getElementById('modal-struk').classList.remove('hidden');

    try {
        const { data } = await axios.get(`/api/kasir/transaksi/${trxId}/struk`);
        strutHtml = data.data.html;
        document.getElementById('modal-struk-konten').innerHTML =
            '<p class="text-green-600 dark:text-green-400 font-medium">Struk siap dicetak.</p>';
    } catch (_) {
        strutHtml = '';
        document.getElementById('modal-struk-konten').textContent = 'Gagal memuat struk.';
    }
}

function cetakStruk() {
    if (!strutHtml) return;
    const iframe = document.getElementById('iframe-struk');
    iframe.srcdoc = strutHtml;
    iframe.onload = function () {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };
}

function tutupModal()       { document.getElementById('modal-struk').classList.add('hidden'); }
function tutupModalError()  { document.getElementById('modal-error').classList.add('hidden'); }
function tampilkanError(msg) {
    document.getElementById('modal-error-msg').textContent = msg;
    document.getElementById('modal-error').classList.remove('hidden');
}
function transaksiBerikutnya() {
    tutupModal();
    resetKeranjang();
    document.getElementById('input-kode').focus();
}

// ── Utilitas ──────────────────────────────────────────────────────────────────
function fmt(n) { return new Intl.NumberFormat('id-ID').format(n); }

function setErrScan(msg) {
    const el = document.getElementById('err-scan');
    el.textContent = msg;
    msg ? el.classList.remove('hidden') : el.classList.add('hidden');
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    renderKeranjang();
    document.getElementById('input-kode').focus();

    // Sembunyikan panel uang diterima untuk metode non-tunai
    document.querySelectorAll('input[name="metode_bayar"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('panel-bayar').style.display =
                this.value === 'tunai' ? 'block' : 'none';
        });
    });
});
</script>
@endpush
