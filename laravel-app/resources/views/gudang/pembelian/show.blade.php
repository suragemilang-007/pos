@extends('layouts.app')

@section('title', 'Detail Pembelian')
@section('page-title', 'Detail PO')

@section('content')
<div class="space-y-6 max-w-4xl">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="/gudang/pembelian" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Pembelian</a>
        <span>/</span>
        <span id="breadcrumb-no" class="text-gray-900 dark:text-gray-100 font-medium">Memuat...</span>
    </nav>

    {{-- ── Skeleton Loading ─────────────────────────────────────────────────── --}}
    <div id="page-skeleton" class="space-y-5">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <div class="animate-pulse space-y-3">
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
                    @for($i=0;$i<4;$i++)<div class="h-12 bg-gray-200 dark:bg-gray-700 rounded"></div>@endfor
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <div class="animate-pulse space-y-3">
                @for($i=0;$i<4;$i++)
                <div class="flex gap-4">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 ml-auto"></div>
                </div>
                @endfor
            </div>
        </div>
    </div>

    {{-- ── Konten Utama (muncul setelah load) ──────────────────────────────── --}}
    <div id="page-content" class="hidden space-y-5">

        {{-- Header Info PO --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-mono" id="info-no-po">—</p>
                    <h3 id="info-supplier" class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-0.5">—</h3>
                </div>
                <div class="flex items-center gap-3">
                    <span id="badge-status" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold">—</span>
                    {{-- Tombol Retur (muncul jika COMPLETED) --}}
                    <button id="btn-retur" onclick="bukaModalRetur()"
                        class="hidden inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Buat Retur
                    </button>
                </div>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal PO</p>
                    <p id="info-tanggal" class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Dibuat Oleh</p>
                    <p id="info-user" class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Jumlah Item</p>
                    <p id="info-jml-item" class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total PO</p>
                    <p id="info-total" class="text-lg font-bold font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">—</p>
                </div>
            </div>
        </div>

        {{-- Tabel Item --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Item Pembelian</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Satuan</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Qty</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Harga Beli</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-items" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                </table>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 flex justify-end">
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                    <p id="tbl-total" class="text-lg font-bold font-mono text-indigo-600 dark:text-indigo-400">—</p>
                </div>
            </div>
        </div>

        {{-- Tombol Kembali --}}
        <div class="pb-6">
            <a href="/gudang/pembelian"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                ← Kembali ke Daftar PO
            </a>
        </div>

    </div>

</div>

{{-- ══ MODAL RETUR PEMBELIAN ═════════════════════════════════════════════════ --}}
<div id="modal-retur"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Retur Pembelian</h3>
            <button onclick="tutupModalRetur()"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Info singkat --}}
        <div class="px-6 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800">
            <p class="text-xs text-blue-700 dark:text-blue-300">
                Centang item yang diretur, isi qty retur (tidak boleh melebihi qty beli), dan masukkan alasan.
            </p>
        </div>

        {{-- Tabel item retur --}}
        <div class="overflow-x-auto max-h-72 overflow-y-auto">
            <table class="w-full text-sm text-left min-w-[550px]">
                <thead class="sticky top-0">
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/80">
                        <th class="px-3 py-2.5 w-10">
                            <input type="checkbox" id="retur-check-all" onchange="toggleAllRetur(this)"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th class="px-3 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Produk</th>
                        <th class="px-3 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase text-center">Satuan</th>
                        <th class="px-3 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase text-center">Qty Beli</th>
                        <th class="px-3 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase text-center w-24">Qty Retur</th>
                        <th class="px-3 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody id="tbody-retur" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>
        </div>

        {{-- Ringkasan kredit --}}
        <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 flex justify-between items-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <span id="lbl-item-retur" class="font-semibold text-gray-900 dark:text-gray-100">0</span> item dipilih
            </p>
            <div class="text-right">
                <p class="text-xs text-gray-500 dark:text-gray-400">Estimasi Kredit</p>
                <p id="lbl-kredit" class="text-base font-bold font-mono text-green-600 dark:text-green-400">Rp 0</p>
            </div>
        </div>

        {{-- Alasan --}}
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Alasan Retur <span class="text-red-500">*</span>
            </label>
            <textarea id="retur-alasan" rows="2"
                placeholder="Produk rusak, tidak sesuai pesanan, kelebihan pengiriman..."
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors resize-none"
                oninput="clearErrRetur()"></textarea>
            <p id="err-retur" class="hidden mt-1 text-xs text-red-600 dark:text-red-400"></p>
        </div>

        {{-- Status Kafka retur --}}
        <div id="status-kafka-retur" class="hidden px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-amber-50 dark:bg-amber-900/20">
            <div class="flex items-center gap-2">
                <svg class="animate-spin w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm text-amber-600 dark:text-amber-400 font-medium">Memproses retur... harap tunggu.</span>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalRetur()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-submit-retur" onclick="submitRetur()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-retur" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-submit-retur">Proses Retur</span>
            </button>
        </div>
    </div>
</div>

{{-- ══ MODAL SUKSES RETUR ══════════════════════════════════════════════════════ --}}
<div id="modal-sukses-retur"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Retur Berhasil!</h3>
            <p id="sukses-no-retur" class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1"></p>
            <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400">Nilai Kredit ke Supplier</p>
                <p id="sukses-kredit" class="text-2xl font-bold font-mono text-green-600 dark:text-green-400 mt-1">—</p>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">Stok produk telah dikurangi sesuai qty retur.</p>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <a href="/gudang/pembelian"
                class="flex-1 text-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Daftar PO
            </a>
            <button onclick="tutupModalSuksesRetur()"
                class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Konstanta ─────────────────────────────────────────────────────────────────
const PEMBELIAN_ID = {{ $pembelianId }};

const BADGE = {
    PENDING:   'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    COMPLETED: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    CANCELLED: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
};
const LABEL_STATUS = { PENDING: 'Menunggu', COMPLETED: 'Selesai', CANCELLED: 'Dibatalkan' };

let poData        = null;
let pendingReturId= null;
let pollTimerRetur= null;

// ── Load Data ─────────────────────────────────────────────────────────────────
async function loadPO() {
    try {
        const { data } = await axios.get(`/api/gudang/pembelian/${PEMBELIAN_ID}`);
        poData = data.data;
        renderPO(poData);
    } catch (err) {
        showToast('error', 'Gagal memuat data', err.response?.data?.message || 'PO tidak ditemukan');
    }
}

function renderPO(po) {
    document.getElementById('page-skeleton').classList.add('hidden');
    document.getElementById('page-content').classList.remove('hidden');

    document.getElementById('breadcrumb-no').textContent = po.no_pembelian;
    document.getElementById('info-no-po').textContent    = po.no_pembelian;
    document.getElementById('info-supplier').textContent = po.supplier?.nama_supplier || '—';
    document.getElementById('info-tanggal').textContent  = fmtTgl(po.tanggal_pembelian);
    document.getElementById('info-user').textContent     = po.dibuat_oleh || '—';
    document.getElementById('info-jml-item').textContent = (po.items?.length || 0) + ' item';
    document.getElementById('info-total').textContent    = 'Rp ' + fmt(po.total);
    document.getElementById('tbl-total').textContent     = 'Rp ' + fmt(po.total);

    const badge = document.getElementById('badge-status');
    badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ' + (BADGE[po.status] || '');
    badge.textContent = LABEL_STATUS[po.status] || po.status;

    // Tampilkan tombol Retur hanya jika COMPLETED
    if (po.status === 'COMPLETED') {
        document.getElementById('btn-retur').classList.remove('hidden');
    }

    // Render item tabel
    const tbody = document.getElementById('tbody-items');
    if (!po.items || !po.items.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">Tidak ada item</td></tr>';
        return;
    }

    tbody.innerHTML = po.items.map(item => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${esc(item.nama_produk || '—')}</p>
            </td>
            <td class="px-4 py-3 text-center">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
                    ${esc(item.nama_satuan)} (×${item.isi_satuan})
                </span>
            </td>
            <td class="px-4 py-3 text-center text-sm font-semibold text-gray-900 dark:text-gray-100">
                ${fmt(item.qty)}
            </td>
            <td class="px-4 py-3 text-right text-sm font-mono text-gray-900 dark:text-gray-100">
                Rp ${fmt(item.harga_beli_satuan)}
            </td>
            <td class="px-4 py-3 text-right text-sm font-semibold font-mono text-gray-900 dark:text-gray-100">
                Rp ${fmt(item.subtotal)}
            </td>
        </tr>`).join('');
}

// ── Modal Retur ───────────────────────────────────────────────────────────────
function bukaModalRetur() {
    if (!poData || !poData.items.length) return;

    // Render baris retur
    const tbody = document.getElementById('tbody-retur');
    tbody.innerHTML = poData.items.map((item, i) => `
        <tr id="retur-row-${i}">
            <td class="px-3 py-2.5">
                <input type="checkbox" id="retur-chk-${i}" onchange="onCheckRetur(${i})"
                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
            </td>
            <td class="px-3 py-2.5 text-sm font-medium text-gray-900 dark:text-gray-100">${esc(item.nama_produk)}</td>
            <td class="px-3 py-2.5 text-center text-xs text-gray-500 dark:text-gray-400">${esc(item.nama_satuan)}</td>
            <td class="px-3 py-2.5 text-center text-sm font-semibold text-gray-900 dark:text-gray-100">${fmt(item.qty)}</td>
            <td class="px-3 py-2.5 text-center">
                <input type="number" id="retur-qty-${i}" value="1" min="1" max="${item.qty}" disabled
                    class="w-20 px-2 py-1.5 text-sm text-center bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    oninput="onQtyRetur(${i}, ${item.qty}, ${item.harga_beli_satuan})">
                <p id="err-retur-qty-${i}" class="hidden text-xs text-red-500 mt-0.5">Maks ${fmt(item.qty)}</p>
            </td>
            <td class="px-3 py-2.5 text-right text-sm font-mono">
                <span id="retur-kredit-${i}" class="text-gray-400 dark:text-gray-600">—</span>
            </td>
        </tr>`).join('');

    document.getElementById('retur-alasan').value = '';
    document.getElementById('err-retur').classList.add('hidden');
    document.getElementById('status-kafka-retur').classList.add('hidden');
    hitungKreditTotal();
    tampilkanModal('modal-retur');
}

function toggleAllRetur(masterChk) {
    (poData?.items || []).forEach((_, i) => {
        const chk = document.getElementById('retur-chk-'+i);
        if (chk) { chk.checked = masterChk.checked; onCheckRetur(i); }
    });
}

function onCheckRetur(i) {
    const chk   = document.getElementById('retur-chk-'+i);
    const qtyEl = document.getElementById('retur-qty-'+i);
    const item  = poData.items[i];

    qtyEl.disabled = !chk.checked;
    if (chk.checked) {
        onQtyRetur(i, item.qty, item.harga_beli_satuan);
    } else {
        document.getElementById('retur-kredit-'+i).textContent = '—';
        document.getElementById('retur-kredit-'+i).className   = 'text-gray-400 dark:text-gray-600';
    }
    hitungKreditTotal();
}

function onQtyRetur(i, maxQty, harga) {
    const qtyEl  = document.getElementById('retur-qty-'+i);
    const errEl  = document.getElementById('err-retur-qty-'+i);
    const kredEl = document.getElementById('retur-kredit-'+i);
    const chk    = document.getElementById('retur-chk-'+i);
    const qty    = parseInt(qtyEl.value) || 0;

    if (qty > maxQty) {
        qtyEl.classList.add('border-red-500');
        errEl.classList.remove('hidden');
        kredEl.textContent = '!';
    } else {
        qtyEl.classList.remove('border-red-500');
        errEl.classList.add('hidden');
        if (chk.checked && qty > 0) {
            kredEl.textContent = 'Rp ' + fmt(qty * harga);
            kredEl.className   = 'text-green-600 dark:text-green-400 font-semibold';
        }
    }
    hitungKreditTotal();
}

function hitungKreditTotal() {
    if (!poData) return;
    let total = 0, count = 0;
    poData.items.forEach((item, i) => {
        const chk = document.getElementById('retur-chk-'+i);
        const qty = parseInt(document.getElementById('retur-qty-'+i)?.value) || 0;
        if (chk?.checked && qty > 0 && qty <= item.qty) {
            total += qty * item.harga_beli_satuan;
            count++;
        }
    });
    document.getElementById('lbl-kredit').textContent   = 'Rp ' + fmt(total);
    document.getElementById('lbl-item-retur').textContent = count;
}

async function submitRetur() {
    const alasan = document.getElementById('retur-alasan').value.trim();

    if (alasan.length < 5) {
        document.getElementById('err-retur').textContent = 'Alasan minimal 5 karakter';
        document.getElementById('err-retur').classList.remove('hidden');
        return;
    }

    const itemsRetur = [];
    let adaError = false;
    (poData?.items || []).forEach((item, i) => {
        const chk = document.getElementById('retur-chk-'+i);
        const qty = parseInt(document.getElementById('retur-qty-'+i)?.value) || 0;
        if (chk?.checked && qty > 0) {
            if (qty > item.qty) { adaError = true; return; }
            itemsRetur.push({
                produk_id:    item.produk_id,
                nama_satuan:  item.nama_satuan,
                isi_satuan:   item.isi_satuan,
                qty_retur:    qty,
                harga_satuan: item.harga_beli_satuan,
            });
        }
    });

    if (adaError) {
        document.getElementById('err-retur').textContent = 'Qty retur melebihi qty beli pada salah satu item';
        document.getElementById('err-retur').classList.remove('hidden');
        return;
    }

    if (!itemsRetur.length) {
        document.getElementById('err-retur').textContent = 'Pilih minimal 1 item untuk diretur';
        document.getElementById('err-retur').classList.remove('hidden');
        return;
    }

    setReturLoading(true);

    try {
        const { data } = await axios.post('/api/gudang/retur-pembelian', {
            pembelian_id: PEMBELIAN_ID,
            items_retur:  itemsRetur,
            alasan,
        });
        pendingReturId = data.id_retur;
        document.getElementById('status-kafka-retur').classList.remove('hidden');
        document.getElementById('btn-submit-retur').disabled = true;
        startPollingRetur();

    } catch (err) {
        setReturLoading(false);
        document.getElementById('err-retur').textContent =
            err.response?.data?.message || 'Gagal memproses retur';
        document.getElementById('err-retur').classList.remove('hidden');
    }
}

function startPollingRetur() {
    if (pollTimerRetur) return;
    pollTimerRetur = setInterval(async () => {
        if (!pendingReturId) { stopPollingRetur(); return; }
        // Tidak ada endpoint GET retur detail, rely pada WS
        // (polling hanya fallback tambahan — tidak ada API spesifik untuk ini)
    }, 3000);
}

function stopPollingRetur() {
    if (pollTimerRetur) { clearInterval(pollTimerRetur); pollTimerRetur = null; }
}

// WS: return.purchase.completed
document.addEventListener('ws:return-purchase-completed', function (e) {
    if (!pendingReturId) return;
    stopPollingRetur();

    const data = e.detail;
    tutupModalRetur();
    document.getElementById('sukses-no-retur').textContent =
        'No. Retur: ' + (data.returId || '—');
    document.getElementById('sukses-kredit').textContent =
        'Rp ' + fmt(data.nilaiKredit || 0);
    tampilkanModal('modal-sukses-retur');
    pendingReturId = null;

    // Reload PO data agar status mungkin berubah
    loadPO();
});

function tutupModalRetur() {
    sembunyikanModal('modal-retur');
    setReturLoading(false);
    document.getElementById('status-kafka-retur').classList.add('hidden');
    document.getElementById('btn-submit-retur').disabled = false;
}

function tutupModalSuksesRetur() {
    sembunyikanModal('modal-sukses-retur');
}

function setReturLoading(on) {
    document.getElementById('btn-submit-retur').disabled = on;
    document.getElementById('spinner-retur').classList.toggle('hidden', !on);
    document.getElementById('lbl-submit-retur').textContent = on ? 'Memproses...' : 'Proses Retur';
}

function clearErrRetur() {
    document.getElementById('err-retur').classList.add('hidden');
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

// ── Utilitas ──────────────────────────────────────────────────────────────────
function fmt(n)    { return new Intl.NumberFormat('id-ID').format(n ?? 0); }
function fmtTgl(s) { if(!s)return'—'; try{return new Date(s).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});}catch(_){return s;} }
function esc(str)  { if(str==null)return''; return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

document.addEventListener('DOMContentLoaded', loadPO);
</script>
@endpush
