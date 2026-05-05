@extends('layouts.app')

@section('title', 'Void Transaksi')
@section('page-title', 'Void Transaksi')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- ── Info Banner ─────────────────────────────────────────────────────── --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl px-5 py-3 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="text-sm text-amber-800 dark:text-amber-300 space-y-0.5">
            <p class="font-semibold">Void hanya bisa dilakukan dalam batas waktu sejak transaksi dibuat.</p>
            <p class="text-amber-700 dark:text-amber-400 text-xs">
                Void membatalkan <strong>semua item</strong> sekaligus dan mengembalikan stok ke gudang.
                Untuk pembatalan sebagian, gunakan menu <strong>Retur Penjualan</strong>.
            </p>
        </div>
    </div>

    {{-- ── STEP 1: Cari Transaksi ───────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">1. Cari Transaksi</h3>
        </div>
        <div class="p-6">
            <div class="flex gap-3">
                <input id="input-no-trx" type="text"
                    placeholder="TRX-20260502-00001 atau ID angka..."
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
            <p id="err-cari" class="hidden mt-2 text-sm text-red-600 dark:text-red-400"></p>
        </div>
    </div>

    {{-- ── STEP 2 & 3: Detail + Status Void ────────────────────────────────── --}}
    <div id="section-transaksi" class="hidden space-y-5">

        {{-- Header transaksi --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">2. Detail Transaksi</h3>
                <span id="badge-status"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
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
                    <p class="text-xs text-gray-500 dark:text-gray-400">Customer</p>
                    <p id="trx-customer" class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                    <p id="trx-total" class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100 mt-0.5">—</p>
                </div>
            </div>
        </div>

        {{-- Status void eligibility + countdown --}}
        <div id="panel-void-eligible"
            class="hidden bg-white dark:bg-gray-800 rounded-xl border border-green-300 dark:border-green-700 shadow-sm">
            <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Transaksi dapat di-void</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Semua item akan dibatalkan dan stok dikembalikan</p>
                    </div>
                </div>
                {{-- Countdown timer --}}
                <div class="flex items-center gap-2 shrink-0">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span id="countdown-label"
                        class="text-sm font-mono font-bold text-green-700 dark:text-green-400">
                        Sisa waktu: --:--
                    </span>
                </div>
            </div>
        </div>

        <div id="panel-void-not-eligible"
            class="hidden bg-white dark:bg-gray-800 rounded-xl border border-red-300 dark:border-red-700 shadow-sm">
            <div class="px-6 py-4 flex items-start gap-3">
                <div class="w-9 h-9 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Transaksi tidak dapat di-void</p>
                    <p id="void-reason-text" class="text-sm text-red-600 dark:text-red-400 mt-0.5"></p>
                </div>
            </div>
        </div>

        {{-- Tabel item transaksi (read-only) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Item Transaksi</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Semua item di bawah akan dibatalkan jika void dilakukan</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Satuan</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Qty</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Harga Satuan</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-items" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                            <td colspan="4" class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 text-right">Total</td>
                            <td class="px-4 py-3 text-sm font-bold font-mono text-gray-900 dark:text-gray-100 text-right" id="tfoot-total">—</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Tombol Void --}}
        <div id="div-btn-void" class="flex justify-end">
            <button id="btn-void" onclick="bukaModalVoid()"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:opacity-40 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                Void Transaksi
            </button>
        </div>

        {{-- Panel "sedang diproses" — muncul setelah submit, sebelum WS callback --}}
        <div id="panel-processing" class="hidden">
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl px-6 py-5">
                <div class="flex items-center gap-3">
                    <svg class="animate-spin w-6 h-6 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Void sedang diproses…</p>
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">Stok sedang dikembalikan ke gudang. Tunggu notifikasi.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel sukses — muncul setelah WS void.completed --}}
        <div id="panel-sukses" class="hidden">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700 rounded-xl px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-green-800 dark:text-green-300">Transaksi Berhasil Di-void</p>
                        <p id="sukses-detail" class="text-xs text-green-700 dark:text-green-400 mt-0.5">Stok produk telah dikembalikan ke gudang.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
    {{-- /section-transaksi --}}

</div>
@endsection

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL KONFIRMASI VOID
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-void"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Konfirmasi Void Transaksi</h3>
                <p id="modal-void-subtitle" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></p>
            </div>
            <button onclick="tutupModalVoid()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">

            {{-- Peringatan --}}
            <div class="flex items-start gap-2.5 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-xs text-red-700 dark:text-red-300">
                    Tindakan ini akan membatalkan <strong>semua item</strong> dan tidak dapat dibatalkan.
                    Stok akan dikembalikan ke gudang secara otomatis.
                </p>
            </div>

            {{-- Alasan --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Alasan Void <span class="text-red-500">*</span>
                </label>
                <select id="input-alasan"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    onchange="handleAlasanChange()">
                    <option value="">-- Pilih alasan --</option>
                    <option value="kesalahan_input">Kesalahan Input Data</option>
                    <option value="barang_rusak_saat_transaksi">Barang Rusak Saat Transaksi</option>
                    <option value="permintaan_pembeli">Permintaan Pembeli</option>
                    <option value="lainnya">Lainnya (wajib isi keterangan)</option>
                </select>
                <p id="err-alasan" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Keterangan --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Keterangan
                    <span id="lbl-ket-req" class="hidden text-red-500">*</span>
                    <span id="lbl-ket-opt" class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">(opsional)</span>
                </label>
                <textarea id="input-keterangan" rows="3" maxlength="500"
                    placeholder="Detail tambahan..."
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors resize-none"
                    oninput="clearErrField('err-keterangan','input-keterangan')"></textarea>
                <p id="err-keterangan" class="hidden text-xs text-red-600 dark:text-red-400"></p>
            </div>

        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="tutupModalVoid()"
                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Batal
            </button>
            <button id="btn-submit-void" onclick="submitVoid()"
                class="inline-flex items-center gap-2 px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-void" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-submit-void">Ya, Void Sekarang</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let transaksiData   = null;   // data dari GET /transaksi/{id}
let countdownTimer  = null;   // setInterval reference
let sisaDetik       = 0;      // sisa waktu void dalam detik

// ── Cari Transaksi ────────────────────────────────────────────────────────────
async function cariTransaksi() {
    const query = document.getElementById('input-no-trx').value.trim();

    if (!query) {
        tampilErrCari('Masukkan nomor transaksi atau ID.');
        return;
    }

    bersihErrCari();
    setCariLoading(true);
    resetTampilan();

    try {
        // Langkah 1: cari berdasarkan no_transaksi atau ID
        const { data: cari } = await axios.get('/api/kasir/transaksi/cari', { params: { q: query } });
        const id = cari.data.id;

        // Langkah 2: ambil detail + info void dari endpoint show()
        const { data: detail } = await axios.get(`/api/kasir/transaksi/${id}`);
        transaksiData = detail.data;

        renderTransaksi(transaksiData);

    } catch (err) {
        const msg = err.response?.data?.message || 'Transaksi tidak ditemukan.';
        tampilErrCari(msg);
    } finally {
        setCariLoading(false);
    }
}

function renderTransaksi(data) {
    // Header info
    document.getElementById('trx-no').textContent       = data.no_transaksi || '—';
    document.getElementById('trx-tgl').textContent      = fmtDatetime(data.tanggal_transaksi);
    document.getElementById('trx-customer').textContent = data.customer?.nama || 'Walk-in';
    document.getElementById('trx-total').textContent    = 'Rp ' + fmt(data.total);
    document.getElementById('tfoot-total').textContent  = 'Rp ' + fmt(data.total);

    // Badge status
    const badge = document.getElementById('badge-status');
    const statusCls = {
        COMPLETED: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        VOIDED:    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        FAILED:    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ' +
        (statusCls[data.status] || 'bg-gray-100 text-gray-600');
    badge.textContent = data.status;

    // Tabel item
    const tbody = document.getElementById('tbody-items');
    tbody.innerHTML = (data.items || []).map(item => {
        const isBonus = item.is_bonus;
        return `
        <tr class="${isBonus ? 'bg-green-50 dark:bg-green-900/10' : ''}">
            <td class="px-4 py-3">
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${esc(item.nama_produk || '—')}</span>
                ${isBonus ? `<span class="ml-1.5 text-xs text-green-600 dark:text-green-400 font-medium">(Bonus)</span>` : ''}
            </td>
            <td class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400">${esc(item.nama_satuan)}</td>
            <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100 font-mono">${item.qty}</td>
            <td class="px-4 py-3 text-right text-sm font-mono text-gray-600 dark:text-gray-400">
                ${isBonus ? '<span class="text-green-600 dark:text-green-400">Rp 0</span>' : 'Rp ' + fmt(item.harga_satuan)}
            </td>
            <td class="px-4 py-3 text-right text-sm font-mono text-gray-900 dark:text-gray-100">
                ${isBonus ? '<span class="text-green-600 dark:text-green-400">Rp 0</span>' : 'Rp ' + fmt(item.subtotal)}
            </td>
        </tr>`;
    }).join('');

    // Tampilkan section
    document.getElementById('section-transaksi').classList.remove('hidden');

    // Eligibility
    if (data.can_void) {
        document.getElementById('panel-void-eligible').classList.remove('hidden');
        document.getElementById('panel-void-not-eligible').classList.add('hidden');
        document.getElementById('div-btn-void').classList.remove('hidden');

        // Mulai countdown
        sisaDetik = data.sisa_waktu_void || 0;
        mulaiCountdown();
    } else {
        document.getElementById('panel-void-eligible').classList.add('hidden');
        document.getElementById('panel-void-not-eligible').classList.remove('hidden');
        document.getElementById('void-reason-text').textContent = data.void_reason || 'Tidak dapat di-void.';
        document.getElementById('div-btn-void').classList.add('hidden');
        stopCountdown();
    }
}

// ── Countdown Timer ───────────────────────────────────────────────────────────
function mulaiCountdown() {
    stopCountdown();
    updateCountdownUI();

    countdownTimer = setInterval(() => {
        sisaDetik = Math.max(0, sisaDetik - 1);
        updateCountdownUI();

        if (sisaDetik === 0) {
            stopCountdown();
            // Nonaktifkan tombol void
            document.getElementById('btn-void').disabled = true;
            // Ganti panel eligible → tidak eligible
            document.getElementById('panel-void-eligible').classList.add('hidden');
            document.getElementById('panel-void-not-eligible').classList.remove('hidden');
            document.getElementById('void-reason-text').textContent = 'Batas waktu void telah habis.';
            document.getElementById('div-btn-void').classList.add('hidden');
        }
    }, 1000);
}

function stopCountdown() {
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
}

function updateCountdownUI() {
    const el = document.getElementById('countdown-label');
    if (!el) return;

    const menit  = Math.floor(sisaDetik / 60);
    const detik  = sisaDetik % 60;
    const label  = `Sisa waktu: ${String(menit).padStart(2, '0')}:${String(detik).padStart(2, '0')}`;

    el.textContent = label;

    // Warna berdasarkan sisa waktu
    el.classList.remove(
        'text-green-700','dark:text-green-400',
        'text-amber-600','dark:text-amber-400',
        'text-red-600',  'dark:text-red-400',
        'animate-pulse'
    );

    if (sisaDetik < 60) {
        // < 1 menit: merah + pulse
        el.classList.add('text-red-600', 'dark:text-red-400', 'animate-pulse');
    } else if (sisaDetik < 300) {
        // < 5 menit: kuning
        el.classList.add('text-amber-600', 'dark:text-amber-400');
    } else {
        // Aman: hijau
        el.classList.add('text-green-700', 'dark:text-green-400');
    }
}

// ── Modal Void ────────────────────────────────────────────────────────────────
function bukaModalVoid() {
    if (!transaksiData || !transaksiData.can_void || sisaDetik === 0) return;

    document.getElementById('modal-void-subtitle').textContent =
        transaksiData.no_transaksi + ' — Total Rp ' + fmt(transaksiData.total);

    document.getElementById('input-alasan').value     = '';
    document.getElementById('input-keterangan').value = '';

    ['err-alasan','err-keterangan'].forEach(id => {
        const e = document.getElementById(id);
        e.classList.add('hidden');
        e.textContent = '';
    });

    // Reset keterangan label
    document.getElementById('lbl-ket-req').classList.add('hidden');
    document.getElementById('lbl-ket-opt').classList.remove('hidden');
    document.getElementById('input-keterangan').placeholder = 'Detail tambahan...';

    tampilModal('modal-void');
    setTimeout(() => document.getElementById('input-alasan').focus(), 100);
}

function tutupModalVoid() {
    sembunyiModal('modal-void');
}

function handleAlasanChange() {
    const alasan = document.getElementById('input-alasan').value;
    const reqLabel = document.getElementById('lbl-ket-req');
    const optLabel = document.getElementById('lbl-ket-opt');
    const textarea = document.getElementById('input-keterangan');

    if (alasan === 'lainnya') {
        reqLabel.classList.remove('hidden');
        optLabel.classList.add('hidden');
        textarea.placeholder = 'Jelaskan alasan void lebih detail... (wajib)';
    } else {
        reqLabel.classList.add('hidden');
        optLabel.classList.remove('hidden');
        textarea.placeholder = 'Detail tambahan...';
    }

    clearErrField('err-alasan', 'input-alasan');
}

async function submitVoid() {
    if (!transaksiData) return;

    const alasan     = document.getElementById('input-alasan').value;
    const keterangan = document.getElementById('input-keterangan').value.trim();

    // Bersihkan error
    ['err-alasan','err-keterangan'].forEach(id => document.getElementById(id).classList.add('hidden'));

    let ok = true;
    if (!alasan) {
        tampilErrField('err-alasan', 'input-alasan', 'Pilih alasan void.');
        ok = false;
    }
    if (alasan === 'lainnya' && !keterangan) {
        tampilErrField('err-keterangan', 'input-keterangan', 'Keterangan wajib diisi untuk alasan "Lainnya".');
        ok = false;
    }
    if (!ok) return;

    setVoidLoading(true);

    try {
        await axios.post(`/api/kasir/transaksi/${transaksiData.id}/void`, {
            alasan:     alasan,
            keterangan: keterangan || null,
        });

        // 202 Accepted — async via Kafka, tunggu WS event void.completed
        tutupModalVoid();
        stopCountdown();

        document.getElementById('div-btn-void').classList.add('hidden');
        document.getElementById('panel-void-eligible').classList.add('hidden');
        document.getElementById('panel-processing').classList.remove('hidden');

        showToast('info', 'Void Diproses', 'Menunggu konfirmasi dari server...');

    } catch (err) {
        const msg = err.response?.data?.message || 'Gagal mengirim permintaan void.';
        if (err.response?.status === 400) {
            // Tidak eligible (race condition)
            tutupModalVoid();
            showToast('error', 'Void Ditolak', msg);
            // Reload info void terbaru
            if (transaksiData?.id) {
                const { data } = await axios.get(`/api/kasir/transaksi/${transaksiData.id}`);
                transaksiData = data.data;
                renderTransaksi(transaksiData);
            }
        } else if (err.response?.status === 422) {
            const errors = err.response.data?.errors || {};
            if (errors.alasan?.[0])     tampilErrField('err-alasan',     'input-alasan',     errors.alasan[0]);
            if (errors.keterangan?.[0]) tampilErrField('err-keterangan', 'input-keterangan', errors.keterangan[0]);
        } else {
            showToast('error', 'Gagal', msg);
        }
        setVoidLoading(false);
    }
}

function setVoidLoading(on) {
    document.getElementById('btn-submit-void').disabled = on;
    document.getElementById('spinner-void').classList.toggle('hidden', !on);
    document.getElementById('lbl-submit-void').textContent = on ? 'Memproses...' : 'Ya, Void Sekarang';
}

// ── WebSocket — handle void.completed ────────────────────────────────────────
// app.blade.php sudah subscribe ke channel 'user-{userId}' via WebSocket.
// Override handleWsEvent agar void.completed ditangani di halaman ini
// tanpa mengubah file layout global.
const _origHandleWsEvent = window.handleWsEvent;

window.handleWsEvent = function(data) {
    if (data.event === 'void.completed') {
        handleVoidCompleted(data);
        return;
    }
    // Delegasikan event lain ke handler global (transaksi, stok, dll.)
    if (typeof _origHandleWsEvent === 'function') {
        _origHandleWsEvent(data);
    }
};

function handleVoidCompleted(data) {
    // Hanya proses jika ini untuk transaksi yang sedang ditampilkan
    if (!transaksiData || data.transaksiId !== transaksiData.id) return;

    document.getElementById('panel-processing').classList.add('hidden');
    document.getElementById('panel-sukses').classList.remove('hidden');

    // Update badge status
    const badge = document.getElementById('badge-status');
    badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
    badge.textContent = 'VOIDED';

    document.getElementById('sukses-detail').textContent =
        `No. ${transaksiData.no_transaksi} berhasil dibatalkan. Stok telah dikembalikan ke gudang.`;

    showToast('success', 'Void Berhasil',
        `Transaksi ${transaksiData.no_transaksi} telah dibatalkan.`);

    // Update state lokal
    transaksiData.status   = 'VOIDED';
    transaksiData.can_void = false;

    stopCountdown();
}

// ── Utilitas Modal ────────────────────────────────────────────────────────────
function tampilModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
}

function sembunyiModal(id) {
    const el = document.getElementById(id);
    el.classList.add('hidden');
    el.classList.remove('flex');
}

// Klik backdrop tutup modal
document.getElementById('modal-void')?.addEventListener('click', function (e) {
    if (e.target === this) tutupModalVoid();
});

// Escape tutup modal
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') tutupModalVoid();
});

// ── Utilitas Error ────────────────────────────────────────────────────────────
function tampilErrCari(msg) {
    const el = document.getElementById('err-cari');
    el.textContent = msg;
    el.classList.remove('hidden');
}

function bersihErrCari() {
    const el = document.getElementById('err-cari');
    el.textContent = '';
    el.classList.add('hidden');
}

function tampilErrField(errId, inputId, msg) {
    const e = document.getElementById(errId);
    if (!e) return;
    e.textContent = msg;
    e.classList.remove('hidden');
    document.getElementById(inputId)?.classList.add('border-red-500', 'dark:border-red-400');
}

function clearErrField(errId, inputId) {
    const e = document.getElementById(errId);
    e?.classList.add('hidden');
    document.getElementById(inputId)?.classList.remove('border-red-500', 'dark:border-red-400');
}

function setCariLoading(on) {
    document.getElementById('btn-cari').disabled = on;
    document.getElementById('spinner-cari').classList.toggle('hidden', !on);
    document.getElementById('lbl-cari').textContent = on ? 'Mencari...' : 'Cari';
}

function resetTampilan() {
    stopCountdown();
    transaksiData = null;
    sisaDetik     = 0;

    document.getElementById('section-transaksi').classList.add('hidden');
    document.getElementById('panel-void-eligible').classList.add('hidden');
    document.getElementById('panel-void-not-eligible').classList.add('hidden');
    document.getElementById('panel-processing').classList.add('hidden');
    document.getElementById('panel-sukses').classList.add('hidden');
    document.getElementById('div-btn-void').classList.add('hidden');
    document.getElementById('btn-void').disabled = false;
    document.getElementById('tbody-items').innerHTML = '';
}

// ── Utilitas Format ───────────────────────────────────────────────────────────
function fmtDatetime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function fmt(n) {
    return new Intl.NumberFormat('id-ID').format(n ?? 0);
}

function esc(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('input-no-trx').focus();
});

// Cleanup timer saat navigasi keluar halaman
window.addEventListener('beforeunload', () => stopCountdown());
</script>
@endpush
