@extends('layouts.app')

@section('title', 'Dashboard Gudang')
@section('page-title', 'Dashboard Gudang')

@section('content')
<div class="space-y-6">

    {{-- Aksi cepat (§10.6A) --}}
    <div class="flex items-center gap-3">
        <a href="/gudang/pembelian/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Buat PO Baru
        </a>
        <a href="/stok"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Lihat Stok
        </a>
    </div>

    {{-- 4 kartu statistik --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Produk Aktif</p>
            <p id="stat-produk-aktif" class="mt-2 text-3xl font-bold font-mono text-gray-900 dark:text-gray-100">
                <span class="animate-pulse inline-block w-16 h-8 bg-gray-200 dark:bg-gray-700 rounded"></span>
            </p>
        </div>

        {{-- Stok rendah — merah + klik ke halaman stok (§10.6E FAILED color) --}}
        <a href="/stok?filter=rendah" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors block">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Produk Stok Rendah</p>
                    <p id="stat-stok-rendah" class="mt-2 text-3xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
                    <p id="hint-stok-rendah" class="hidden mt-1 text-xs font-medium text-red-600 dark:text-red-400">↑ Perlu perhatian</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 mt-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pembelian Bulan Ini</p>
            <p id="stat-belanja-bulan" class="mt-2 text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">
                <span class="animate-pulse inline-block w-32 h-8 bg-gray-200 dark:bg-gray-700 rounded"></span>
            </p>
        </div>

        {{-- PO Pending — amber jika ada (§10.6E PENDING color) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">PO Menunggu Proses</p>
            <p id="stat-po-pending" class="mt-2 text-3xl font-bold font-mono text-gray-900 dark:text-gray-100">—</p>
            <p id="hint-po-pending" class="hidden mt-1 text-xs text-amber-600 dark:text-amber-400">Consumer sedang memproses</p>
        </div>
    </div>

    {{-- Tabel 5 pembelian terakhir --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">5 Pembelian Terakhir</h3>
        </div>

        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 5; $i++)
            <div class="animate-pulse flex gap-4">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
            </div>
            @endfor
        </div>

        <div id="tbl-pembelian" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No PO</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody id="tbody-pembelian" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>
            <p id="tbl-empty" class="hidden px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Belum ada pembelian.</p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const BADGE = {
    COMPLETED: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    PENDING:   'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    CANCELLED: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
};
const LABEL = { COMPLETED: 'Selesai', PENDING: 'Menunggu', CANCELLED: 'Dibatalkan' };

async function loadRingkasan() {
    try {
        const { data } = await axios.get('/api/gudang/ringkasan');
        const d = data.data;

        document.getElementById('stat-produk-aktif').textContent =
            new Intl.NumberFormat('id-ID').format(d.total_produk_aktif);
        document.getElementById('stat-belanja-bulan').textContent =
            'Rp ' + new Intl.NumberFormat('id-ID').format(d.total_belanja_bulan);

        // Stok rendah
        const elStok = document.getElementById('stat-stok-rendah');
        elStok.textContent = d.produk_stok_rendah;
        if (d.produk_stok_rendah > 0) {
            elStok.classList.replace('text-gray-900', 'text-red-600');
            elStok.classList.replace('dark:text-gray-100', 'dark:text-red-400');
            document.getElementById('hint-stok-rendah').classList.remove('hidden');
        }

        // PO Pending
        const elPO = document.getElementById('stat-po-pending');
        elPO.textContent = d.po_pending;
        if (d.po_pending > 0) {
            elPO.classList.replace('text-gray-900', 'text-amber-600');
            elPO.classList.replace('dark:text-gray-100', 'dark:text-amber-400');
            document.getElementById('hint-po-pending').classList.remove('hidden');
        }

        renderTabel(d.pembelian_terakhir);
    } catch (err) {
        console.error('Gagal load ringkasan gudang:', err);
    }
}

function renderTabel(rows) {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-pembelian').classList.remove('hidden');

    const tbody = document.getElementById('tbody-pembelian');
    if (!rows || rows.length === 0) {
        document.getElementById('tbl-empty').classList.remove('hidden');
        return;
    }

    tbody.innerHTML = rows.map(p => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">${p.no_pembelian}</td>
            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">${p.supplier}</td>
            <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
                Rp ${new Intl.NumberFormat('id-ID').format(p.total)}
            </td>
            <td class="px-4 py-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${BADGE[p.status] || ''}">
                    ${LABEL[p.status] || p.status}
                </span>
            </td>
        </tr>`).join('');
}

// WebSocket: refresh saat purchase.completed (stok berubah setelah restock)
document.addEventListener('stok-updated', loadRingkasan);

document.addEventListener('DOMContentLoaded', loadRingkasan);
</script>
@endpush
