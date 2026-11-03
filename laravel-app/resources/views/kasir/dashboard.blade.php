@extends('layouts.app')

@section('title', 'Dashboard Kasir')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Tombol Transaksi Baru (§10.6A Primary) --}}
    <div class="flex justify-center">
        <a href="/kasir/transaksi"
            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-base font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Transaksi Baru
        </a>
    </div>

    {{-- 4 kartu statistik (§10.6C stat card) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Hari Ini</p>
            <p id="stat-total-trx" class="mt-2 text-3xl font-bold font-mono text-gray-900 dark:text-gray-100">
                <span class="skeleton-val animate-pulse inline-block w-12 h-8 bg-gray-200 dark:bg-gray-700 rounded"></span>
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Omzet Hari Ini</p>
            <p id="stat-omzet" class="mt-2 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">
                <span class="skeleton-val animate-pulse inline-block w-32 h-8 bg-gray-200 dark:bg-gray-700 rounded"></span>
            </p>
        </div>

        {{-- Stok rendah: badge merah jika > 0, klik → halaman stok --}}
        <a href="/stok" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors block">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stok Rendah</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p id="stat-stok-rendah" class="text-3xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
                        <span id="badge-stok-rendah" class="hidden text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                            Perlu Restock
                        </span>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-400 mt-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Terakhir</p>
            <p id="stat-waktu-terakhir" class="mt-2 text-3xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">jam hari ini</p>
        </div>
    </div>

    {{-- Tabel 10 transaksi terakhir --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">10 Transaksi Terakhir</h3>
        </div>

        {{-- Skeleton saat loading --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 10; $i++)
            <div class="animate-pulse flex gap-4">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-transaksi" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No Transaksi</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Waktu</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metode</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-transaksi" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>
            <p id="tbl-empty" class="hidden px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                Belum ada transaksi hari ini.
            </p>
        </div>
    </div>

</div>

{{-- ══ MODAL DETAIL TRANSAKSI ══════════════════════════════════════════════════ --}}
<div id="modal-detail" class="hidden fixed inset-0 z-50 items-start justify-center p-4 pt-16 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Detail Transaksi</h3>
                <p id="detail-no" class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5"></p>
            </div>
            <button onclick="tutupDetail()"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Info ringkas --}}
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/40 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
            <span>Waktu: <span id="detail-waktu" class="font-medium text-gray-700 dark:text-gray-300"></span></span>
            <span>Metode: <span id="detail-metode" class="font-medium text-gray-700 dark:text-gray-300"></span></span>
            <span>Status: <span id="detail-status" class="font-semibold"></span></span>
        </div>

        {{-- Loading state --}}
        <div id="detail-loading" class="px-6 py-8 text-center">
            <svg class="animate-spin w-6 h-6 text-indigo-500 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <p class="mt-2 text-sm text-gray-400">Memuat detail...</p>
        </div>

        {{-- Tabel item --}}
        <div id="detail-konten" class="hidden">
            <div class="overflow-x-auto max-h-60 overflow-y-auto">
                <table class="w-full text-xs">
                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700/60">
                        <tr class="border-b border-gray-200 dark:border-gray-600">
                            <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400">Produk</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-500 dark:text-gray-400">Qty</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-500 dark:text-gray-400">Harga</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-500 dark:text-gray-400">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="detail-tbody" class="divide-y divide-gray-100 dark:divide-gray-700"></tbody>
                </table>
            </div>

            {{-- Ringkasan keuangan --}}
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 space-y-1 text-sm">
                <div class="flex justify-between text-gray-500 dark:text-gray-400">
                    <span>Subtotal</span>
                    <span id="detail-subtotal" class="font-mono"></span>
                </div>
                <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-100 text-base pt-1 border-t border-gray-200 dark:border-gray-700">
                    <span>Total</span>
                    <span id="detail-total" class="font-mono"></span>
                </div>
                <div class="flex justify-between text-gray-500 dark:text-gray-400 text-xs">
                    <span>Bayar</span>
                    <span id="detail-bayar" class="font-mono"></span>
                </div>
                <div class="flex justify-between text-gray-500 dark:text-gray-400 text-xs">
                    <span>Kembalian</span>
                    <span id="detail-kembalian" class="font-mono"></span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button id="btn-print-detail" onclick="cetakStrukDashboard()"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                disabled>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Struk
            </button>
            <button onclick="tutupDetail()"
                class="flex-1 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- Hidden iframe untuk cetak struk --}}
<iframe id="iframe-struk-dashboard" class="hidden" title="Struk"></iframe>

@endsection

@push('scripts')
<script>
const BADGE_STATUS = {
    COMPLETED: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    PENDING:   'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    FAILED:    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    CANCELLED: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    VOIDED:    'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
};
const LABEL_STATUS = {
    COMPLETED: 'Selesai', PENDING: 'Menunggu', FAILED: 'Gagal',
    CANCELLED: 'Dibatalkan', VOIDED: 'Dibatalkan (Void)',
};
const LABEL_METODE = { tunai: 'Tunai', transfer: 'Transfer', qris: 'QRIS' };

const fmt = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(n ?? 0);

// ── Fetch ringkasan dari GET /api/kasir/ringkasan ─────────────────────────────
async function loadRingkasan() {
    try {
        const { data } = await axios.get('/api/kasir/ringkasan');
        const d = data.data;

        document.getElementById('stat-total-trx').textContent = d.transaksi_hari_ini;
        document.getElementById('stat-omzet').textContent =
            'Rp ' + new Intl.NumberFormat('id-ID').format(d.omzet_hari_ini);
        document.getElementById('stat-waktu-terakhir').textContent =
            d.transaksi_terakhir_jam || '—';

        setStokRendah(d.stok_rendah);
        renderTabel(d.transaksi_terakhir);

    } catch (err) {
        console.error('Gagal load ringkasan kasir:', err);
    }
}

function setStokRendah(count) {
    document.getElementById('stat-stok-rendah').textContent = count;
    const badge = document.getElementById('badge-stok-rendah');
    count > 0 ? badge.classList.remove('hidden') : badge.classList.add('hidden');
}

function renderTabel(rows) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-transaksi').classList.remove('hidden');

    const tbody = document.getElementById('tbody-transaksi');
    const empty = document.getElementById('tbl-empty');

    if (!rows || rows.length === 0) {
        empty.classList.remove('hidden');
        return;
    }

    tbody.innerHTML = rows.map(t => {
        const canPrint = t.status === 'COMPLETED';
        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">${t.no_transaksi}</td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">${t.waktu || '—'}</td>
            <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100 font-mono">
                Rp ${new Intl.NumberFormat('id-ID').format(t.total)}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                ${LABEL_METODE[t.metode_bayar] || t.metode_bayar}
            </td>
            <td class="px-4 py-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${BADGE_STATUS[t.status] || ''}">
                    ${LABEL_STATUS[t.status] || t.status}
                </span>
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                    <button onclick="lihatDetail(${t.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Detail
                    </button>
                    ${canPrint ? `
                    <button onclick="lihatDetailDanCetak(${t.id})"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Struk
                    </button>` : ''}
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── Detail Modal ──────────────────────────────────────────────────────────────
let currentDetailId  = null;
let currentStrukHtml = null;

async function lihatDetail(trxId) {
    currentDetailId  = trxId;
    currentStrukHtml = null;

    // Reset modal ke state loading
    document.getElementById('detail-no').textContent      = '';
    document.getElementById('detail-waktu').textContent   = '—';
    document.getElementById('detail-metode').textContent  = '—';
    document.getElementById('detail-status').textContent  = '—';
    document.getElementById('detail-status').className    = 'font-semibold';
    document.getElementById('detail-loading').classList.remove('hidden');
    document.getElementById('detail-konten').classList.add('hidden');
    document.getElementById('btn-print-detail').disabled  = true;
    const modalEl = document.getElementById('modal-detail');
    modalEl.classList.remove('hidden');
    modalEl.classList.add('flex');

    try {
        const { data } = await axios.get(`/api/kasir/transaksi/${trxId}`);
        const d = data.data;

        // Info header
        document.getElementById('detail-no').textContent = d.no_transaksi;
        document.getElementById('detail-waktu').textContent =
            d.tanggal_transaksi
                ? new Date(d.tanggal_transaksi).toLocaleString('id-ID', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' })
                : '—';
        document.getElementById('detail-metode').textContent =
            LABEL_METODE[d.metode_bayar] || d.metode_bayar || '—';

        // Badge status
        const statusEl = document.getElementById('detail-status');
        statusEl.textContent = LABEL_STATUS[d.status] || d.status;
        statusEl.className   = 'font-semibold px-2 py-0.5 rounded-full text-xs ' + (BADGE_STATUS[d.status] || '');

        // Baris item
        document.getElementById('detail-tbody').innerHTML = (d.items || []).map(item => `
            <tr>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">${esc(item.nama_produk)}</td>
                <td class="px-4 py-2 text-center text-gray-500 dark:text-gray-400">${item.qty} ${esc(item.nama_satuan)}</td>
                <td class="px-4 py-2 text-right font-mono text-gray-600 dark:text-gray-400">${fmt(item.harga_satuan)}</td>
                <td class="px-4 py-2 text-right font-mono font-medium text-gray-800 dark:text-gray-200">${fmt(item.subtotal)}</td>
            </tr>`).join('');

        // Ringkasan keuangan
        document.getElementById('detail-subtotal').textContent  = fmt(d.subtotal ?? d.total);
        document.getElementById('detail-total').textContent     = fmt(d.total);
        document.getElementById('detail-bayar').textContent     = fmt(d.bayar);
        document.getElementById('detail-kembalian').textContent = fmt(d.kembalian);

        document.getElementById('detail-loading').classList.add('hidden');
        document.getElementById('detail-konten').classList.remove('hidden');

        // Aktifkan tombol print hanya untuk COMPLETED
        if (d.status === 'COMPLETED') {
            document.getElementById('btn-print-detail').disabled = false;
            // Pre-load struk di background agar print langsung
            axios.get(`/api/kasir/transaksi/${trxId}/struk`)
                .then(r => { currentStrukHtml = r.data.data.html; })
                .catch(() => {});
        }

    } catch (err) {
        document.getElementById('detail-loading').innerHTML =
            '<p class="text-sm text-red-500">Gagal memuat detail transaksi.</p>';
    }
}

// Buka modal detail lalu langsung cetak setelah struk siap
async function lihatDetailDanCetak(trxId) {
    await lihatDetail(trxId);
    // Tunggu pre-load struk selesai (maks 3 detik)
    let waited = 0;
    const interval = setInterval(() => {
        waited += 200;
        if (currentStrukHtml || waited >= 3000) {
            clearInterval(interval);
            if (currentStrukHtml) cetakStrukDashboard();
        }
    }, 200);
}

function cetakStrukDashboard() {
    if (!currentStrukHtml) return;
    const iframe = document.getElementById('iframe-struk-dashboard');
    iframe.srcdoc = currentStrukHtml;
    iframe.onload = function () {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };
}

function tutupDetail() {
    const modalEl = document.getElementById('modal-detail');
    modalEl.classList.add('hidden');
    modalEl.classList.remove('flex');
    currentDetailId  = null;
    currentStrukHtml = null;
}

// Tutup modal saat klik backdrop
document.getElementById('modal-detail').addEventListener('click', function (e) {
    if (e.target === this) tutupDetail();
});

function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── WebSocket real-time ───────────────────────────────────────────────────────
document.addEventListener('stok-updated', function () { loadRingkasan(); });

document.addEventListener('DOMContentLoaded', loadRingkasan);
</script>
@endpush
