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

    {{-- Tabel 5 transaksi terakhir --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">5 Transaksi Terakhir</h3>
        </div>

        {{-- Skeleton saat loading --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 5; $i++)
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
@endsection

@push('scripts')
<script>
const BADGE_STATUS = {
    COMPLETED: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    PENDING:   'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    FAILED:    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    CANCELLED: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
};
const LABEL_STATUS  = { COMPLETED: 'Selesai', PENDING: 'Menunggu', FAILED: 'Gagal', CANCELLED: 'Dibatalkan' };
const LABEL_METODE  = { tunai: 'Tunai', transfer: 'Transfer', qris: 'QRIS' };

// ── Fetch data dari GET /api/kasir/ringkasan ──────────────────────────────────
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

    tbody.innerHTML = rows.map(t => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">${t.no_transaksi}</td>
            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">${t.waktu || '—'}</td>
            <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
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
        </tr>`).join('');
}

// ── WebSocket real-time ───────────────────────────────────────────────────────
// stok-updated = setelah ANY transaksi/pembelian/retur selesai diproses Consumer
// Satu listener cukup — loadRingkasan() sudah mencakup setStokRendah()
document.addEventListener('stok-updated', function () { loadRingkasan(); });

document.addEventListener('DOMContentLoaded', loadRingkasan);
</script>
@endpush
