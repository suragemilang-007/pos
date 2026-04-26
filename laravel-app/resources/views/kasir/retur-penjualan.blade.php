@extends('layouts.app')

@section('title', 'Retur Penjualan')
@section('page-title', 'Retur Penjualan')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- ── Panduan singkat ────────────────────────────────────────────────── --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl px-5 py-3 flex items-start gap-3">
        <span class="text-blue-500 dark:text-blue-400 text-lg shrink-0 mt-0.5">ℹ</span>
        <p class="text-sm text-blue-700 dark:text-blue-300">
            Hanya transaksi berstatus <strong>COMPLETED</strong> yang dapat diretur.
            Masukkan nomor transaksi (TRX-...) atau ID angka.
        </p>
    </div>

    {{-- ── STEP 1: Cari Transaksi ─────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">1. Cari Transaksi Asal</h3>
        </div>
        <div class="p-6">
            <div class="flex gap-3">
                <input id="input-no-trx" type="text"
                    placeholder="TRX-20260424-00001 atau ID angka..."
                    class="flex-1 px-3 py-2.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors font-mono"
                    onkeydown="if(event.key==='Enter') cariTransaksi()">
                <button id="btn-cari" onclick="cariTransaksi()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 shrink-0">
                    <svg id="spinner-cari" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span id="lbl-cari">Cari</span>
                </button>
            </div>
            {{-- Error cari --}}
            <p id="err-cari" class="hidden mt-2 text-sm text-red-600 dark:text-red-400"></p>
        </div>
    </div>

    {{-- ── STEP 2: Info & Item Transaksi ──────────────────────────────────── --}}
    <div id="section-transaksi" class="hidden space-y-5">

        {{-- Info header transaksi --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">2. Transaksi Asal</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                    COMPLETED
                </span>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">No. Transaksi</p>
                    <p id="trx-no" class="text-sm font-mono font-semibold text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal</p>
                    <p id="trx-tgl" class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Kasir</p>
                    <p id="trx-kasir" class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Transaksi</p>
                    <p id="trx-total" class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                </div>
            </div>
        </div>

        {{-- Tabel item untuk diretur --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Pilih Item yang Diretur</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Centang item, isi jumlah yang diretur. Qty tidak boleh melebihi qty beli.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                            <th class="px-4 py-3 w-10">
                                <input type="checkbox" id="check-all" onchange="toggleAll(this)"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Satuan</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Harga</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Qty Beli</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center w-32">Qty Retur</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Subtotal Refund</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-items" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                </table>
            </div>
            {{-- Ringkasan refund --}}
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 flex items-center justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span id="lbl-item-dipilih" class="font-semibold text-gray-900 dark:text-gray-100">0</span> item dipilih
                </p>
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Estimasi Refund</p>
                    <p id="lbl-total-refund" class="text-base font-bold font-mono text-green-600 dark:text-green-400">Rp 0</p>
                </div>
            </div>
        </div>

        {{-- Alasan retur --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">3. Alasan Retur</h3>
            </div>
            <div class="p-6">
                <div class="space-y-1">
                    <label for="input-alasan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Alasan <span class="text-red-500">*</span>
                    </label>
                    <textarea id="input-alasan" rows="3"
                        placeholder="Contoh: Produk rusak / kadaluarsa, salah order, tidak sesuai pesanan..."
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors resize-none"
                        oninput="validasiForm()"></textarea>
                    <p id="err-alasan" class="hidden text-xs text-red-600 dark:text-red-400">Alasan wajib diisi</p>
                </div>
            </div>
        </div>

        {{-- Tombol Proses Retur --}}
        <div class="flex items-center justify-between">
            <button onclick="resetForm()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                ← Cari Transaksi Lain
            </button>

            <button id="btn-proses" onclick="prosesRetur()" disabled
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-bold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-proses" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-proses">Proses Retur</span>
            </button>
        </div>

        {{-- Status Kafka setelah 202 --}}
        <div id="status-kafka" class="hidden bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl px-5 py-4 items-center gap-3">
            <svg class="animate-spin w-5 h-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">Memproses retur...</p>
                <p class="text-xs text-amber-600 dark:text-amber-400">Consumer sedang mengembalikan stok ke gudang. Harap tunggu.</p>
            </div>
        </div>

    </div>{{-- /section-transaksi --}}

</div>{{-- /max-w --}}

{{-- ══ MODAL SUKSES ════════════════════════════════════════════════════════════ --}}
<div id="modal-sukses" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-5 text-center">
            {{-- Ikon sukses --}}
            <div class="mx-auto w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Retur Berhasil!</h3>
            <p id="modal-no-retur" class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1"></p>

            {{-- Nilai refund --}}
            <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400">Nilai Refund ke Customer</p>
                <p id="modal-refund" class="text-2xl font-bold font-mono text-green-600 dark:text-green-400 mt-1">Rp 0</p>
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">
                Stok produk telah dikembalikan ke gudang.
            </p>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <a href="/kasir" class="flex-1 text-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Dashboard
            </a>
            <button onclick="returBaru()"
                class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Retur Lain
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let trxData      = null;    // data transaksi yang ditemukan
let pendingReturId = null;  // id retur setelah 202

// ── Cari Transaksi ────────────────────────────────────────────────────────────
async function cariTransaksi() {
    const q = document.getElementById('input-no-trx').value.trim();
    if (!q) return;

    setErrCari('');
    setCariLoading(true);
    document.getElementById('section-transaksi').classList.add('hidden');

    try {
        const { data } = await axios.get(`/api/kasir/transaksi/cari?q=${encodeURIComponent(q)}`);
        trxData = data.data;
        tampilkanTransaksi(trxData);
    } catch (err) {
        const msg = err.response?.data?.message || 'Transaksi tidak ditemukan';
        setErrCari(msg);
    } finally {
        setCariLoading(false);
    }
}

function tampilkanTransaksi(d) {
    // Isi header info
    document.getElementById('trx-no').textContent    = d.no_transaksi;
    document.getElementById('trx-tgl').textContent   = formatTanggal(d.tanggal_transaksi);
    document.getElementById('trx-kasir').textContent = d.kasir || '—';
    document.getElementById('trx-total').textContent = 'Rp ' + fmt(d.total);

    // Render tabel item
    renderItems(d.items);

    document.getElementById('section-transaksi').classList.remove('hidden');
    // Scroll ke section transaksi
    document.getElementById('section-transaksi').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function renderItems(items) {
    const tbody = document.getElementById('tbody-items');
    tbody.innerHTML = items.map((item, i) => `
        <tr id="row-${i}" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            {{-- Checkbox --}}
            <td class="px-4 py-3">
                <input type="checkbox" id="chk-${i}"
                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"
                    onchange="onCheckItem(${i})"
                    data-idx="${i}">
            </td>
            {{-- Nama produk --}}
            <td class="px-4 py-3">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${item.nama_produk}</p>
            </td>
            {{-- Satuan (snapshot) --}}
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">${item.nama_satuan}</td>
            {{-- Harga satuan --}}
            <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-gray-100 text-right">
                Rp ${fmt(item.harga_satuan)}
            </td>
            {{-- Qty beli --}}
            <td class="px-4 py-3 text-sm text-center">
                <span class="font-semibold text-gray-900 dark:text-gray-100">${item.qty}</span>
                <span class="text-gray-400 dark:text-gray-500 text-xs ml-1">${item.nama_satuan}</span>
            </td>
            {{-- Input qty retur --}}
            <td class="px-4 py-3 text-center">
                <input type="number" id="qty-${i}"
                    min="1" max="${item.qty}" value="1"
                    disabled
                    class="w-20 px-2 py-1.5 text-sm text-center bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    oninput="onQtyRetur(${i}, ${item.qty}, ${item.harga_satuan})">
                {{-- Pesan error qty --}}
                <p id="err-qty-${i}" class="hidden text-xs text-red-500 mt-0.5">Maks ${item.qty}</p>
            </td>
            {{-- Subtotal refund --}}
            <td class="px-4 py-3 text-sm font-mono font-semibold text-right">
                <span id="refund-${i}" class="text-gray-400 dark:text-gray-500">—</span>
            </td>
        </tr>`).join('');

    hitungTotalRefund();
}

// ── Interaksi Item ────────────────────────────────────────────────────────────
function toggleAll(masterChk) {
    const rows = trxData?.items?.length || 0;
    for (let i = 0; i < rows; i++) {
        const chk = document.getElementById('chk-' + i);
        if (chk) {
            chk.checked = masterChk.checked;
            onCheckItem(i);
        }
    }
}

function onCheckItem(idx) {
    const chk    = document.getElementById('chk-' + idx);
    const qtyEl  = document.getElementById('qty-' + idx);
    const item   = trxData.items[idx];

    qtyEl.disabled = !chk.checked;

    if (chk.checked) {
        qtyEl.value = qtyEl.value || 1;
        onQtyRetur(idx, item.qty, item.harga_satuan);
    } else {
        document.getElementById(`refund-${idx}`).textContent = '—';
        document.getElementById(`refund-${idx}`).className   = 'text-gray-400 dark:text-gray-500';
    }

    hitungTotalRefund();
    validasiForm();
}

function onQtyRetur(idx, maxQty, harga) {
    const qtyEl  = document.getElementById('qty-' + idx);
    const errEl  = document.getElementById(`err-qty-${idx}`);
    const refEl  = document.getElementById(`refund-${idx}`);
    const chk    = document.getElementById('chk-' + idx);

    const qty = parseInt(qtyEl.value) || 0;

    // Validasi client-side — qty_retur tidak boleh > qty beli (AD-05)
    if (qty > maxQty) {
        qtyEl.classList.add('border-red-500', 'dark:border-red-400');
        errEl.classList.remove('hidden');
        refEl.textContent = '!';
    } else {
        qtyEl.classList.remove('border-red-500', 'dark:border-red-400');
        errEl.classList.add('hidden');

        if (chk.checked && qty > 0) {
            const refund = qty * harga;
            refEl.textContent = 'Rp ' + fmt(refund);
            refEl.className   = 'text-green-600 dark:text-green-400 font-semibold';
        }
    }

    hitungTotalRefund();
    validasiForm();
}

function hitungTotalRefund() {
    if (!trxData) return;
    let total = 0;
    let count = 0;

    trxData.items.forEach((item, i) => {
        const chk = document.getElementById('chk-' + i);
        const qty = parseInt(document.getElementById('qty-' + i)?.value) || 0;
        if (chk?.checked && qty > 0 && qty <= item.qty) {
            total += qty * item.harga_satuan;
            count++;
        }
    });

    document.getElementById('lbl-total-refund').textContent   = 'Rp ' + fmt(total);
    document.getElementById('lbl-item-dipilih').textContent   = count;
}

// ── Validasi Form ─────────────────────────────────────────────────────────────
function validasiForm() {
    if (!trxData) return;

    // Cek minimal 1 item valid dipilih
    let adaItemValid = false;
    let adaError     = false;
    trxData.items.forEach((item, i) => {
        const chk = document.getElementById('chk-' + i);
        const qty = parseInt(document.getElementById('qty-' + i)?.value) || 0;
        if (chk?.checked) {
            if (qty > 0 && qty <= item.qty) adaItemValid = true;
            if (qty > item.qty) adaError = true;
        }
    });

    const alasan = document.getElementById('input-alasan').value.trim();
    const errEl  = document.getElementById('err-alasan');

    if (alasan.length < 5 && alasan.length > 0) {
        errEl.textContent = 'Alasan terlalu singkat';
        errEl.classList.remove('hidden');
    } else {
        errEl.classList.add('hidden');
    }

    const valid = adaItemValid && !adaError && alasan.length >= 5;
    document.getElementById('btn-proses').disabled = !valid;
}

// ── Proses Retur ──────────────────────────────────────────────────────────────
async function prosesRetur() {
    if (!trxData) return;

    const alasan = document.getElementById('input-alasan').value.trim();

    // Kumpulkan item yang dipilih
    const itemsRetur = [];
    trxData.items.forEach((item, i) => {
        const chk = document.getElementById('chk-' + i);
        const qty = parseInt(document.getElementById('qty-' + i)?.value) || 0;
        if (chk?.checked && qty > 0 && qty <= item.qty) {
            itemsRetur.push({
                produk_id:    item.produk_id,
                nama_satuan:  item.nama_satuan,   // snapshot dari detail_transaksi asal
                isi_satuan:   item.isi_satuan,    // snapshot — Consumer pakai untuk konversi stok
                qty_retur:    qty,
                harga_satuan: item.harga_satuan,  // snapshot
            });
        }
    });

    if (!itemsRetur.length) return;

    setProsesLoading(true);

    try {
        const { data } = await axios.post('/api/kasir/retur-penjualan', {
            transaksi_id: trxData.id,
            items_retur:  itemsRetur,
            alasan,
        });

        // 202 Accepted — Kafka Consumer yang akan proses
        pendingReturId = data.id_retur;
        const kafka = document.getElementById('status-kafka');
        kafka.classList.remove('hidden');
        kafka.classList.add('flex');
        document.getElementById('btn-proses').disabled = true;

    } catch (err) {
        const msg = err.response?.data?.message || 'Gagal memproses retur';
        setErrCari(msg);
        // Scroll ke error
        document.getElementById('err-cari').scrollIntoView({ behavior: 'smooth' });
    } finally {
        setProsesLoading(false);
    }
}

// ── WebSocket ─────────────────────────────────────────────────────────────────
// layouts/app.blade.php menangani koneksi WS dan men-dispatch DOM CustomEvent
// 'ws:return-sale-completed' setiap kali Consumer menyelesaikan proses retur.
// Halaman ini cukup listen event DOM — tidak perlu akses wsConn langsung.

document.addEventListener('ws:return-sale-completed', function (e) {
    handleReturnCompleted(e.detail);
});

function handleReturnCompleted(data) {
    if (!pendingReturId) return;

    const kafkaEl = document.getElementById('status-kafka');
    kafkaEl.classList.add('hidden');
    kafkaEl.classList.remove('flex');
    pendingReturId = null;

    // Tampilkan modal sukses dengan nilai refund dari Consumer
    document.getElementById('modal-no-retur').textContent =
        'No. Retur: ' + (data.returId || '—');
    document.getElementById('modal-refund').textContent =
        'Rp ' + fmt(data.nilaiRefund || 0);
    const modalEl = document.getElementById('modal-sukses');
    modalEl.classList.remove('hidden');
    modalEl.classList.add('flex');
}

// ── Utilitas ──────────────────────────────────────────────────────────────────
function fmt(n) { return new Intl.NumberFormat('id-ID').format(n); }

function formatTanggal(str) {
    if (!str) return '—';
    try {
        return new Date(str).toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    } catch (_) { return str; }
}

function setErrCari(msg) {
    const el = document.getElementById('err-cari');
    el.textContent = msg;
    msg ? el.classList.remove('hidden') : el.classList.add('hidden');
}

function setCariLoading(on) {
    document.getElementById('spinner-cari').classList.toggle('hidden', !on);
    document.getElementById('lbl-cari').textContent = on ? 'Mencari...' : 'Cari';
    document.getElementById('btn-cari').disabled    = on;
}

function setProsesLoading(on) {
    document.getElementById('spinner-proses').classList.toggle('hidden', !on);
    document.getElementById('lbl-proses').textContent = on ? 'Memproses...' : 'Proses Retur';
}

function resetForm() {
    trxData      = null;
    pendingReturId = null;
    document.getElementById('section-transaksi').classList.add('hidden');
    const kafkaEl2 = document.getElementById('status-kafka');
    kafkaEl2.classList.add('hidden');
    kafkaEl2.classList.remove('flex');
    document.getElementById('input-no-trx').value = '';
    document.getElementById('input-alasan').value = '';
    setErrCari('');
    document.getElementById('input-no-trx').focus();
}

function returBaru() {
    const modalEl2 = document.getElementById('modal-sukses');
    modalEl2.classList.add('hidden');
    modalEl2.classList.remove('flex');
    resetForm();
}
</script>
@endpush
