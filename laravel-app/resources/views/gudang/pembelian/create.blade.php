@extends('layouts.app')

@section('title', 'Buat PO Baru')
@section('page-title', 'Buat PO Baru')

@section('content')
<div class="space-y-6 max-w-5xl">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="/gudang/pembelian" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Pembelian</a>
        <span>/</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">Buat PO Baru</span>
    </nav>

    {{-- ── Info Dasar PO ─────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Informasi Pembelian</h3>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">

            {{-- Supplier --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Supplier <span class="text-red-500">*</span>
                </label>
                <select id="input-supplier"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="clearErr('err-supplier')">
                    <option value="">-- Pilih Supplier --</option>
                </select>
                <p id="err-supplier" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Tanggal Pembelian --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tanggal Pembelian <span class="text-red-500">*</span>
                </label>
                <input id="input-tanggal" type="date"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="clearErr('err-tanggal')">
                <p id="err-tanggal" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>
        </div>
    </div>

    {{-- ── Tabel Item Pembelian ──────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Item Pembelian</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Cari produk, pilih satuan, isi qty dan harga beli (bisa dinegosiasikan)
                </p>
            </div>
            <button onclick="tambahBaris()"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 rounded-lg transition-colors shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Item
            </button>
        </div>

        <div id="err-items" class="hidden mx-6 mt-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p id="err-items-msg" class="text-sm text-red-700 dark:text-red-400"></p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left min-w-[750px]">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">Satuan</th>
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24 text-center">Qty</th>
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-36">Harga Beli</th>
                        <th class="px-3 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32 text-right">Subtotal</th>
                        <th class="px-3 py-3 w-8"></th>
                    </tr>
                </thead>
                <tbody id="tbody-items" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>
            <div id="items-empty" class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                Klik "+ Tambah Item" untuk menambahkan produk ke PO ini.
            </div>
        </div>

        {{-- Total --}}
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 flex justify-between items-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <span id="lbl-jumlah-item" class="font-semibold text-gray-900 dark:text-gray-100">0</span> item
            </p>
            <div class="text-right">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total PO</p>
                <p id="lbl-total" class="text-xl font-bold font-mono text-indigo-600 dark:text-indigo-400">Rp 0</p>
            </div>
        </div>
    </div>

    {{-- ── Tipe Pembayaran ─────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Tipe Pembayaran</h3>
        </div>
        <div class="p-6 space-y-5">

            {{-- Radio Cash / Kredit --}}
            <div class="flex gap-6">
                <label class="flex items-center gap-2.5 cursor-pointer group">
                    <input type="radio" name="tipe-pembayaran" value="cash" checked
                        class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600"
                        onchange="onTipePembayaranChange('cash')">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-gray-100">
                        Cash <span class="text-xs font-normal text-gray-400">(lunas saat diterima)</span>
                    </span>
                </label>
                <label class="flex items-center gap-2.5 cursor-pointer group">
                    <input type="radio" name="tipe-pembayaran" value="kredit"
                        class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600"
                        onchange="onTipePembayaranChange('kredit')">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-gray-100">
                        Kredit <span class="text-xs font-normal text-gray-400">(bayar nanti / cicilan)</span>
                    </span>
                </label>
            </div>

            {{-- Section kredit — muncul hanya saat pilih Kredit --}}
            <div id="section-kredit" class="hidden space-y-4 pl-6 border-l-2 border-indigo-200 dark:border-indigo-700">

                {{-- DP + Jatuh Tempo --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Down Payment (DP)
                            <span class="text-xs font-normal text-gray-400">(opsional, isi 0 jika tidak ada DP)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">Rp</span>
                            <input id="input-dp" type="number" value="0" min="0" step="1000"
                                class="block w-full pl-8 pr-3 py-2 text-sm font-mono bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                                oninput="updatePaymentSummary(); clearErr('err-dp')">
                        </div>
                        <p id="err-dp" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tanggal Jatuh Tempo <span class="text-red-500">*</span>
                        </label>
                        <input id="input-jatuh-tempo" type="date"
                            class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                            onchange="updatePaymentSummary(); clearErr('err-jatuh-tempo')">
                        <p id="err-jatuh-tempo" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Catatan Pembayaran <span class="text-xs font-normal text-gray-400">(opsional)</span>
                    </label>
                    <textarea id="input-catatan" rows="2" placeholder="Contoh: Net 30, cicil 3x, dll."
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors resize-none"></textarea>
                </div>
            </div>

            {{-- Summary otomatis --}}
            <div id="payment-summary" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 space-y-1.5">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Total PO</span>
                    <span id="sum-total" class="font-mono font-semibold text-gray-900 dark:text-gray-100">Rp 0</span>
                </div>
                <div id="sum-dp-row" class="hidden justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">DP Dibayar</span>
                    <span id="sum-dp" class="font-mono text-green-600 dark:text-green-400">Rp 0</span>
                </div>
                <div id="sum-sisa-row" class="hidden justify-between text-sm pt-1.5 border-t border-gray-200 dark:border-gray-600">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Sisa Hutang</span>
                    <span id="sum-sisa" class="font-mono font-bold text-red-600 dark:text-red-400">Rp 0</span>
                </div>
                <div id="sum-jt-row" class="hidden justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Jatuh Tempo</span>
                    <span id="sum-jt" class="text-amber-600 dark:text-amber-400 font-medium">—</span>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Tombol Aksi ──────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between pb-6">
        <a href="/gudang/pembelian"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            ← Batal
        </a>

        <div class="flex items-center gap-3">
            {{-- Status Kafka --}}
            <div id="status-kafka" class="hidden flex items-center gap-2">
                <svg class="animate-spin w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm text-amber-600 dark:text-amber-400 font-medium">Memproses PO...</span>
            </div>

            <button id="btn-buat" onclick="buatPO()"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-buat" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-buat">Buat PO</span>
            </button>
        </div>
    </div>

</div>

{{-- Dropdown produk autocomplete (shared, diposisikan absolute oleh JS) --}}
<div id="produk-dropdown"
    class="hidden fixed z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl max-h-48 overflow-y-auto">
</div>
@endsection

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let items      = [];   // {rowId, produk_id, produk_satuan_id, nama_produk, nama_satuan, isi_satuan, qty, harga_beli_satuan, satuan_list}
let rowCounter = 0;
let pendingPoId = null;
let pollTimer   = null;
let searchTimer = null;
let activeSearchRow = null;
let tipePembayaran  = 'cash';  // 'cash' | 'kredit'
let wsPoDetail      = null;    // payload WS purchase.completed — untuk hutang notification

// Cache hasil search produk — onclick di dropdown hanya kirim produkId (angka),
// data lengkap diambil dari sini untuk menghindari JSON-in-HTML yang rusak parsing.
const produkCache = new Map();

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async function () {
    // Set tanggal default = hari ini
    document.getElementById('input-tanggal').value = new Date().toLocaleDateString('sv');
    await loadSupplierDropdown();
    tambahBaris(); // Mulai dengan 1 baris kosong
    document.addEventListener('click', closeProdukDropdown);
});

async function loadSupplierDropdown() {
    try {
        const { data } = await axios.get('/api/gudang/supplier?limit=200&status=aktif');
        const sel = document.getElementById('input-supplier');
        (data.data || []).forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.nama_supplier;
            sel.appendChild(opt);
        });
    } catch (_) {}
}

// ── Tambah/Render Baris ───────────────────────────────────────────────────────
function tambahBaris() {
    const row = {
        rowId:           ++rowCounter,
        produk_id:       null,
        produk_satuan_id:null,
        nama_produk:     '',
        nama_satuan:     '',
        isi_satuan:      1,
        qty:             1,
        harga_beli_satuan: 0,
        satuan_list:     [],
    };
    items.push(row);
    renderItems();
    // Focus input produk baris baru
    setTimeout(() => {
        const el = document.querySelector(`[data-rowid="${row.rowId}"] .input-produk`);
        if (el) el.focus();
    }, 50);
}

function renderItems() {
    const tbody = document.getElementById('tbody-items');
    const empty = document.getElementById('items-empty');

    if (!items.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        updateTotal();
        return;
    }

    empty.classList.add('hidden');
    tbody.innerHTML = items.map(r => buildBaris(r)).join('');

    // Pasang event listeners
    items.forEach(r => {
        const row = document.querySelector(`[data-rowid="${r.rowId}"]`);
        if (!row) return;

        const inputProduk = row.querySelector('.input-produk');
        const selSatuan   = row.querySelector('.sel-satuan');
        const inputQty    = row.querySelector('.input-qty');
        const inputHarga  = row.querySelector('.input-harga');

        if (inputProduk) {
            inputProduk.addEventListener('input', (e) => {
                r.nama_produk = e.target.value;
                activeSearchRow = r.rowId;
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => searchProduk(r.rowId, e.target.value), 300);
            });
            inputProduk.addEventListener('focus', () => { activeSearchRow = r.rowId; });
        }

        if (selSatuan) {
            selSatuan.addEventListener('change', function () {
                const sat = r.satuan_list.find(s => s.id == this.value);
                if (!sat) return;
                r.produk_satuan_id = sat.id;
                r.nama_satuan      = sat.nama_satuan;
                r.isi_satuan       = sat.isi;
                r.harga_beli_satuan= sat.harga_beli;
                // Auto-fill harga beli
                const hargaEl = row.querySelector('.input-harga');
                if (hargaEl) hargaEl.value = sat.harga_beli;
                hitungSubtotal(r);
                updateTotal();
            });
        }

        if (inputQty) {
            inputQty.addEventListener('input', function () {
                r.qty = parseInt(this.value) || 1;
                hitungSubtotal(r);
                updateTotal();
            });
        }

        if (inputHarga) {
            inputHarga.addEventListener('input', function () {
                r.harga_beli_satuan = parseFloat(this.value) || 0;
                hitungSubtotal(r);
                updateTotal();
            });
        }
    });
}

function buildBaris(r) {
    const satuanOptions = r.satuan_list.map(s =>
        `<option value="${s.id}" ${s.id == r.produk_satuan_id ? 'selected' : ''}>${esc(s.nama_satuan)} (isi ${s.isi})</option>`
    ).join('');

    const subtotal = (r.qty || 1) * (r.harga_beli_satuan || 0);

    return `
    <tr data-rowid="${r.rowId}">
        <td class="px-3 py-2.5">
            <input type="text" value="${esc(r.nama_produk)}" placeholder="Cari nama produk..."
                class="input-produk block w-full px-2.5 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors"
                autocomplete="off">
            ${r.produk_id ? `<p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 font-mono">ID: ${r.produk_id}</p>` : ''}
        </td>
        <td class="px-3 py-2.5">
            <select class="sel-satuan block w-full px-2.5 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors ${!r.satuan_list.length ? 'opacity-40 cursor-not-allowed' : ''}"
                ${!r.satuan_list.length ? 'disabled' : ''}>
                ${r.satuan_list.length ? satuanOptions : '<option value="">— pilih produk —</option>'}
            </select>
        </td>
        <td class="px-3 py-2.5">
            <input type="number" value="${r.qty}" min="1"
                class="input-qty block w-full px-2.5 py-1.5 text-sm text-center bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors">
        </td>
        <td class="px-3 py-2.5">
            <div class="relative">
                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-gray-400">Rp</span>
                <input type="number" value="${r.harga_beli_satuan}" min="0" step="0.01"
                    class="input-harga block w-full pl-7 pr-2 py-1.5 text-sm font-mono text-right bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>
        </td>
        <td class="px-3 py-2.5 text-right">
            <p class="subtotal-cell text-sm font-semibold font-mono text-gray-900 dark:text-gray-100">
                ${subtotal > 0 ? 'Rp ' + fmt(subtotal) : '—'}
            </p>
        </td>
        <td class="px-3 py-2.5 text-center">
            <button onclick="hapusBaris(${r.rowId})"
                class="p-1 text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 transition-colors rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </td>
    </tr>`;
}

function hapusBaris(rowId) {
    items = items.filter(r => r.rowId !== rowId);
    renderItems();
}

function hitungSubtotal(row) {
    const subtotal = (row.qty || 1) * (row.harga_beli_satuan || 0);
    const el = document.querySelector(`[data-rowid="${row.rowId}"] .subtotal-cell`);
    if (el) el.textContent = subtotal > 0 ? 'Rp ' + fmt(subtotal) : '—';
}

function updateTotal() {
    const total = items.reduce((s, r) => s + (r.qty||1) * (r.harga_beli_satuan||0), 0);
    document.getElementById('lbl-total').textContent = 'Rp ' + fmt(total);
    document.getElementById('lbl-jumlah-item').textContent = items.length;
    updatePaymentSummary();
}

// ── Tipe Pembayaran ───────────────────────────────────────────────────────────
function onTipePembayaranChange(tipe) {
    tipePembayaran = tipe;
    const sectionKredit = document.getElementById('section-kredit');
    if (tipe === 'kredit') {
        sectionKredit.classList.remove('hidden');
        // Set minimal tanggal jatuh tempo = besok
        const besok = new Date();
        besok.setDate(besok.getDate() + 1);
        document.getElementById('input-jatuh-tempo').min = besok.toLocaleDateString('sv');
    } else {
        sectionKredit.classList.add('hidden');
        // Reset field kredit saat kembali ke cash
        document.getElementById('input-dp').value          = '0';
        document.getElementById('input-jatuh-tempo').value = '';
        document.getElementById('input-catatan').value     = '';
        clearErr('err-dp');
        clearErr('err-jatuh-tempo');
    }
    updatePaymentSummary();
}

function updatePaymentSummary() {
    const total = items.reduce((s, r) => s + (r.qty||1) * (r.harga_beli_satuan||0), 0);
    const dp    = tipePembayaran === 'kredit' ? (parseFloat(document.getElementById('input-dp').value) || 0) : 0;
    const sisa  = Math.max(0, total - dp);
    const jt    = tipePembayaran === 'kredit' ? document.getElementById('input-jatuh-tempo').value : '';

    document.getElementById('sum-total').textContent = 'Rp ' + fmt(total);

    // Rows kredit — gunakan flex saat tampil
    const showRow = (id, show) => {
        const el = document.getElementById(id);
        if (show) { el.classList.remove('hidden'); el.classList.add('flex'); }
        else       { el.classList.add('hidden');    el.classList.remove('flex'); }
    };

    const isKredit = tipePembayaran === 'kredit';
    showRow('sum-dp-row',   isKredit && dp > 0);
    showRow('sum-sisa-row', isKredit);
    showRow('sum-jt-row',   isKredit);

    document.getElementById('sum-dp').textContent   = 'Rp ' + fmt(dp);
    document.getElementById('sum-sisa').textContent = 'Rp ' + fmt(sisa);
    document.getElementById('sum-jt').textContent   = jt
        ? new Date(jt).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
        : '— belum diisi —';
}

// ── Autocomplete Produk ───────────────────────────────────────────────────────
async function searchProduk(rowId, q) {
    if (q.length < 2) { closeProdukDropdown(); return; }

    try {
        const { data } = await axios.get(`/api/gudang/produk?nama=${encodeURIComponent(q)}&status=aktif&limit=8`);
        const list = data.data || [];
        // Simpan ke cache — onclick hanya kirim p.id (integer), data diambil dari sini
        list.forEach(p => produkCache.set(p.id, p));
        showProdukDropdown(rowId, list);
    } catch (_) { closeProdukDropdown(); }
}

function showProdukDropdown(rowId, produkList) {
    const dropdown = document.getElementById('produk-dropdown');
    const inputEl  = document.querySelector(`[data-rowid="${rowId}"] .input-produk`);
    if (!inputEl) return;

    if (!produkList.length) { dropdown.classList.add('hidden'); return; }

    const rect = inputEl.getBoundingClientRect();
    dropdown.style.top   = (rect.bottom + window.scrollY + 4) + 'px';
    dropdown.style.left  = rect.left + 'px';
    dropdown.style.width = rect.width + 'px';

    // onclick hanya kirim angka — tidak ada string/JSON yang bisa merusak atribut HTML
    dropdown.innerHTML = produkList.map(p => `
        <div onclick="pilihProduk(${rowId}, ${p.id})"
            class="px-3 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${esc(p.nama_produk)}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">${esc(p.kode_produk)} · ${esc(p.satuan_dasar)} · stok ${fmt(p.qty_stok)}</p>
        </div>`).join('');

    dropdown.classList.remove('hidden');
}

function pilihProduk(rowId, produkId) {
    const row = items.find(r => r.rowId === rowId);
    if (!row) return;

    // Ambil data lengkap dari cache — tidak ada passing data lewat HTML attribute
    const p = produkCache.get(produkId);
    if (!p) return;

    const satuanList = p.satuan_tersedia || [];
    row.produk_id   = p.id;
    row.nama_produk = p.nama_produk;
    row.satuan_list = satuanList;

    // Set satuan default (atau satuan pertama jika tidak ada default)
    const def = satuanList.find(s => s.is_default) || satuanList[0];
    if (def) {
        row.produk_satuan_id  = def.id;
        row.nama_satuan       = def.nama_satuan;
        row.isi_satuan        = def.isi;
        row.harga_beli_satuan = def.harga_beli;
    }

    closeProdukDropdown();
    renderItems();
}

function closeProdukDropdown(e) {
    const dd = document.getElementById('produk-dropdown');
    if (e && dd.contains(e.target)) return; // klik di dalam dropdown sendiri
    dd.classList.add('hidden');
}

// ── Validasi & Submit ─────────────────────────────────────────────────────────
function validasi() {
    let ok = true;
    clearAllErr();

    if (!document.getElementById('input-supplier').value) {
        showErr('err-supplier', 'Pilih supplier terlebih dahulu');
        ok = false;
    }
    if (!document.getElementById('input-tanggal').value) {
        showErr('err-tanggal', 'Tanggal pembelian wajib diisi');
        ok = false;
    }
    if (!items.length) {
        showErrItems('Tambahkan minimal 1 item pembelian');
        ok = false;
    }

    const invalid = items.filter(r => !r.produk_id);
    if (invalid.length) { showErrItems('Semua baris harus memilih produk'); ok = false; }

    const noSatuan = items.filter(r => !r.produk_satuan_id);
    if (noSatuan.length) { showErrItems('Semua baris harus memilih satuan'); ok = false; }

    const noHarga = items.filter(r => !(r.harga_beli_satuan > 0));
    if (noHarga.length) { showErrItems('Harga beli semua item harus lebih dari 0'); ok = false; }

    const noQty = items.filter(r => !(r.qty >= 1));
    if (noQty.length) { showErrItems('Qty semua item harus ≥ 1'); ok = false; }

    // Validasi kredit
    if (tipePembayaran === 'kredit') {
        const dp    = parseFloat(document.getElementById('input-dp').value) || 0;
        const total = items.reduce((s, r) => s + (r.qty||1) * (r.harga_beli_satuan||0), 0);
        if (dp < 0) {
            showErr('err-dp', 'DP tidak boleh negatif');
            ok = false;
        } else if (dp >= total && total > 0) {
            showErr('err-dp', 'DP melebihi atau sama dengan total PO — gunakan tipe Cash');
            ok = false;
        }
        if (!document.getElementById('input-jatuh-tempo').value) {
            showErr('err-jatuh-tempo', 'Tanggal jatuh tempo wajib diisi untuk pembayaran kredit');
            ok = false;
        }
    }

    return ok;
}

async function buatPO() {
    if (!validasi()) return;

    setBuatLoading(true);

    const dp             = tipePembayaran === 'kredit' ? (parseFloat(document.getElementById('input-dp').value) || 0) : 0;
    const jatuhTempo     = tipePembayaran === 'kredit' ? document.getElementById('input-jatuh-tempo').value : null;
    const catatanBayar   = tipePembayaran === 'kredit' ? (document.getElementById('input-catatan').value.trim() || null) : null;

    const payload = {
        supplier_id:          parseInt(document.getElementById('input-supplier').value),
        tanggal_pembelian:    document.getElementById('input-tanggal').value,
        tipe_pembayaran:      tipePembayaran,
        jumlah_dp:            dp,
        tanggal_jatuh_tempo:  jatuhTempo,
        catatan_pembayaran:   catatanBayar,
        items: items.map(r => ({
            produk_id:         r.produk_id,
            produk_satuan_id:  r.produk_satuan_id,
            nama_satuan:       r.nama_satuan,
            isi_satuan:        r.isi_satuan,
            qty:               r.qty,
            harga_beli_satuan: r.harga_beli_satuan,
        })),
    };

    try {
        const { data } = await axios.post('/api/gudang/pembelian', payload);
        // 202 Accepted — Consumer yang proses stok dan pembayaran
        pendingPoId = data.id_pembelian;
        setBuatLoading(false);
        document.getElementById('status-kafka').classList.remove('hidden');
        document.getElementById('btn-buat').disabled = true;
        startPolling();

    } catch (err) {
        setBuatLoading(false);
        const errors = err.response?.data?.errors;
        if (errors) {
            if (errors.supplier_id)        showErr('err-supplier', errors.supplier_id[0]);
            if (errors.tanggal_pembelian)  showErr('err-tanggal', errors.tanggal_pembelian[0]);
            if (errors.jumlah_dp)          showErr('err-dp', errors.jumlah_dp[0]);
            if (errors.tanggal_jatuh_tempo) showErr('err-jatuh-tempo', errors.tanggal_jatuh_tempo[0]);
            const itemErr = Object.entries(errors).find(([k]) => k.startsWith('items'));
            if (itemErr) showErrItems(itemErr[1][0]);
        } else {
            showToast('error', 'Gagal membuat PO', err.response?.data?.message || 'Terjadi kesalahan');
        }
    }
}

// ── Polling Fallback ──────────────────────────────────────────────────────────
function startPolling() {
    if (pollTimer) return;
    pollTimer = setInterval(async () => {
        if (!pendingPoId) { stopPolling(); return; }
        try {
            const { data } = await axios.get(`/api/gudang/pembelian/${pendingPoId}`);
            const status = data.data?.status;
            if (status === 'COMPLETED') {
                stopPolling();
                onPoSelesai(pendingPoId);
            } else if (status === 'CANCELLED') {
                stopPolling();
                document.getElementById('status-kafka').classList.add('hidden');
                document.getElementById('btn-buat').disabled = false;
                pendingPoId = null;
                showToast('error', 'PO Dibatalkan', 'PO tidak dapat diproses');
            }
        } catch (_) {}
    }, 3000);
}

function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

function onPoSelesai(poId) {
    document.getElementById('status-kafka').classList.add('hidden');
    showToast('success', 'PO Berhasil Dibuat', 'Stok produk telah diperbarui. Mengarahkan ke detail PO...');

    // Notifikasi hutang untuk PO kredit
    // Prioritas data: payload WS (paling akurat) > form input (fallback)
    const tipe      = wsPoDetail?.tipePembayaran ?? tipePembayaran;
    if (tipe === 'kredit') {
        const sisa     = wsPoDetail?.sisaHutang
            ?? Math.max(0, items.reduce((s,r) => s+(r.qty||1)*(r.harga_beli_satuan||0), 0)
               - (parseFloat(document.getElementById('input-dp')?.value) || 0));
        const jt       = wsPoDetail?.tanggalJatuhTempo
            ?? document.getElementById('input-jatuh-tempo')?.value;
        const tglFmt   = jt
            ? new Date(jt).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
            : '—';
        const selEl    = document.getElementById('input-supplier');
        const supplier = selEl?.options[selEl.selectedIndex]?.text ?? 'Supplier';

        // Tunda sedikit agar toast sukses muncul duluan
        setTimeout(() => {
            showToast('warning', '⚠ Hutang Tercatat',
                `Rp ${fmt(sisa)} ke ${esc(supplier)} — jatuh tempo ${tglFmt}`);
        }, 400);
    }

    setTimeout(() => { window.location.href = '/gudang/pembelian/' + poId; }, 2200);
}

// WS purchase.completed — lebih cepat dari polling
document.addEventListener('ws:purchase-completed', function (e) {
    if (!pendingPoId) return;
    wsPoDetail = e.detail ?? null;  // simpan payload untuk onPoSelesai
    stopPolling();
    onPoSelesai(pendingPoId);
});

// ── Utilitas ──────────────────────────────────────────────────────────────────
function setBuatLoading(on) {
    document.getElementById('btn-buat').disabled = on;
    document.getElementById('spinner-buat').classList.toggle('hidden', !on);
    document.getElementById('lbl-buat').textContent = on ? 'Memproses...' : 'Buat PO';
}

function showErr(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('hidden');
}
function clearErr(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('hidden');
}
function clearAllErr() {
    ['err-supplier', 'err-tanggal', 'err-dp', 'err-jatuh-tempo'].forEach(id => clearErr(id));
    document.getElementById('err-items').classList.add('hidden');
}
function showErrItems(msg) {
    document.getElementById('err-items-msg').textContent = msg;
    document.getElementById('err-items').classList.remove('hidden');
    document.getElementById('err-items').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function fmt(n)   { return new Intl.NumberFormat('id-ID').format(n ?? 0); }
function esc(str) { if(str==null)return''; return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
</script>
@endpush
