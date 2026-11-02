@extends('layouts.app')

@section('title', 'Persetujuan Void')
@section('page-title', 'Persetujuan Void Transaksi')

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- ── Info Banner ──────────────────────────────────────────────────────── --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl px-5 py-3 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="text-sm text-amber-800 dark:text-amber-300 space-y-0.5">
            <p class="font-semibold">Persetujuan harus diberikan sebelum batas waktu habis.</p>
            <p class="text-amber-700 dark:text-amber-400 text-xs">
                Menyetujui void akan membatalkan <strong>semua item</strong> transaksi dan mengembalikan stok ke gudang.
                Tindakan ini tidak dapat dibatalkan.
            </p>
        </div>
    </div>

    {{-- ── Daftar Pengajuan ─────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Pengajuan Void Menunggu Persetujuan</h3>
                
            </div>
            <button onclick="muatPengajuan()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                <svg id="icon-refresh" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
        </div>

        {{-- Skeleton --}}
        <div id="tbl-skeleton" class="p-6 space-y-3">
            @for ($i = 0; $i < 3; $i++)
            <div class="animate-pulse flex gap-3 items-center">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-32"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded flex-1"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-28"></div>
            </div>
            @endfor
        </div>

        {{-- Tabel --}}
        <div id="tbl-wrapper" class="hidden overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No. Transaksi</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kasir</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alasan</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Total</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Sisa Waktu</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-void" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>

            <div id="tbl-empty" class="hidden px-6 py-14 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada pengajuan void yang menunggu.</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Halaman ini akan diperbarui otomatis saat ada pengajuan baru.</p>
            </div>
        </div>
    </div>

</div>
@endsection

{{-- ══ MODAL DETAIL TRANSAKSI ═════════════════════════════════════════════════ --}}
<div id="modal-detail"
    class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">

        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shrink-0">
            <div>
                <h3 id="modal-no-trx" class="text-base font-semibold text-gray-900 dark:text-gray-100">Detail Transaksi</h3>
                <p id="modal-kasir" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></p>
            </div>
            <button onclick="tutupModal()"
                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto px-6 py-4 space-y-4">

            {{-- Info alasan --}}
            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg px-4 py-3 border border-amber-200 dark:border-amber-800">
                <p class="text-xs text-amber-700 dark:text-amber-400 font-medium">Alasan Void</p>
                <p id="modal-alasan" class="text-sm text-amber-900 dark:text-amber-200 mt-0.5"></p>
                <p id="modal-keterangan" class="text-xs text-amber-700 dark:text-amber-400 mt-1 italic"></p>
            </div>

            {{-- Item list --}}
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-left">Produk</th>
                            <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Qty</th>
                            <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="modal-items" class="divide-y divide-gray-100 dark:divide-gray-700"></tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                            <td colspan="2" class="px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 text-right">Total</td>
                            <td id="modal-total" class="px-4 py-2.5 text-sm font-bold font-mono text-gray-900 dark:text-gray-100 text-right">—</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 shrink-0">
            <button onclick="tolakVoid()"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 border border-red-300 dark:border-red-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-tolak" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Tolak
            </button>
            <button onclick="setujuiVoid()"
                class="inline-flex items-center gap-2 px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="spinner-setuju" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="lbl-setuju">Setujui Void</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let pengajuanList  = [];   // cache hasil API
let activeVoidId   = null; // void yang sedang dibuka di modal
let countdowns     = {};   // { voidId: sisaDetik }
let countdownTimer = null;

// ── Muat Pengajuan ────────────────────────────────────────────────────────────
async function muatPengajuan() {
    document.getElementById('tbl-skeleton').classList.remove('hidden');
    document.getElementById('tbl-wrapper').classList.add('hidden');

    try {
        const { data } = await axios.get('/api/owner/void/pending');
        pengajuanList = data.data || [];
        renderTabel();
        startCountdowns();
    } catch {
        showToast('error', 'Gagal', 'Gagal memuat data pengajuan void.');
        document.getElementById('tbl-skeleton').classList.add('hidden');
        document.getElementById('tbl-wrapper').classList.remove('hidden');
    }
}

function renderTabel() {
    document.getElementById('tbl-skeleton').classList.add('hidden');
    document.getElementById('tbl-wrapper').classList.remove('hidden');

    const tbody = document.getElementById('tbody-void');
    const empty = document.getElementById('tbl-empty');

    if (!pengajuanList.length) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');

    const alasanLabel = {
        kesalahan_input:              'Kesalahan Input',
        barang_rusak_saat_transaksi:  'Barang Rusak',
        permintaan_pembeli:           'Permintaan Pembeli',
        lainnya:                      'Lainnya',
    };

    tbody.innerHTML = pengajuanList.map(v => {
        const sisa = v.sisa_detik || 0;
        const urgent = sisa < 120; // < 2 menit = merah
        const mnt = Math.floor(sisa / 60);
        const dtk = sisa % 60;
        const countdownHtml = sisa > 0
            ? `<span id="cd-${v.id}" class="font-mono font-bold ${urgent ? 'text-red-600 dark:text-red-400 animate-pulse' : 'text-amber-600 dark:text-amber-400'}">
                ${String(mnt).padStart(2,'0')}:${String(dtk).padStart(2,'0')}
               </span>`
            : `<span class="text-red-500 dark:text-red-400 text-xs">Kadaluarsa</span>`;

        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-4 py-3">
                <p class="text-sm font-mono font-semibold text-gray-900 dark:text-gray-100">${esc(v.no_transaksi)}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">${fmtDatetime(v.tanggal)}</p>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">${esc(v.kasir || '—')}</td>
            <td class="px-4 py-3">
                <span class="text-sm text-gray-700 dark:text-gray-300">${alasanLabel[v.alasan] || v.alasan}</span>
                ${v.keterangan ? `<p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate max-w-[160px]">${esc(v.keterangan)}</p>` : ''}
            </td>
            <td class="px-4 py-3 text-right text-sm font-mono font-semibold text-gray-900 dark:text-gray-100">
                Rp ${fmt(v.total_transaksi)}
            </td>
            <td class="px-4 py-3 text-center">${countdownHtml}</td>
            <td class="px-4 py-3 text-center">
                <button onclick="bukaDetail(${v.id})"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Tinjau
                </button>
            </td>
        </tr>`;
    }).join('');
}

// ── Countdown Timer ───────────────────────────────────────────────────────────
function startCountdowns() {
    stopCountdowns();
    // Init dari data API
    pengajuanList.forEach(v => { countdowns[v.id] = v.sisa_detik || 0; });

    countdownTimer = setInterval(() => {
        let anyChanged = false;
        pengajuanList.forEach(v => {
            if (countdowns[v.id] > 0) {
                countdowns[v.id]--;
                anyChanged = true;
                updateCountdownCell(v.id, countdowns[v.id]);
            }
        });
        if (!anyChanged) stopCountdowns();
    }, 1000);
}

function stopCountdowns() {
    if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
}

function updateCountdownCell(id, sisa) {
    const el = document.getElementById('cd-' + id);
    if (!el) return;
    const mnt = Math.floor(sisa / 60);
    const dtk = sisa % 60;
    el.textContent = `${String(mnt).padStart(2,'0')}:${String(dtk).padStart(2,'0')}`;

    if (sisa < 60) {
        el.classList.remove('text-amber-600','dark:text-amber-400');
        el.classList.add('text-red-600','dark:text-red-400','animate-pulse');
    } else if (sisa < 120) {
        el.classList.remove('text-amber-600','dark:text-amber-400');
        el.classList.add('text-red-600','dark:text-red-400');
    }
}

// ── Buka Detail + Approve/Reject ──────────────────────────────────────────────
async function bukaDetail(voidId) {
    activeVoidId = voidId;
    const v = pengajuanList.find(x => x.id === voidId);
    if (!v) return;

    // Header modal
    document.getElementById('modal-no-trx').textContent = v.no_transaksi || '—';
    document.getElementById('modal-kasir').textContent  = `Kasir: ${v.kasir || '—'} · ${fmtDatetime(v.created_at)}`;

    // Alasan
    const alasanLabel = {
        kesalahan_input:              'Kesalahan Input Data',
        barang_rusak_saat_transaksi:  'Barang Rusak Saat Transaksi',
        permintaan_pembeli:           'Permintaan Pembeli',
        lainnya:                      'Lainnya',
    };
    document.getElementById('modal-alasan').textContent = alasanLabel[v.alasan] || v.alasan;
    const ketEl = document.getElementById('modal-keterangan');
    ketEl.textContent = v.keterangan ? `"${v.keterangan}"` : '';
    ketEl.classList.toggle('hidden', !v.keterangan);

    // Muat item transaksi
    document.getElementById('modal-items').innerHTML =
        `<tr><td colspan="3" class="px-4 py-4 text-center text-xs text-gray-400"><span class="animate-pulse">Memuat item...</span></td></tr>`;
    document.getElementById('modal-total').textContent = 'Rp ' + fmt(v.total_transaksi);

    tampilkanModal('modal-detail');

    try {
        const { data } = await axios.get(`/api/kasir/transaksi/${v.transaksi_id}`);
        const items = data.data?.items || [];
        document.getElementById('modal-items').innerHTML = items.map(item => `
            <tr>
                <td class="px-4 py-2.5 text-sm text-gray-800 dark:text-gray-200">
                    ${esc(item.nama_produk)}
                    ${item.is_bonus ? '<span class="ml-1 text-xs text-green-600 dark:text-green-400">(Bonus)</span>' : ''}
                </td>
                <td class="px-4 py-2.5 text-center text-sm font-mono text-gray-600 dark:text-gray-400">
                    ${item.qty} ${esc(item.nama_satuan)}
                </td>
                <td class="px-4 py-2.5 text-right text-sm font-mono text-gray-800 dark:text-gray-200">
                    ${item.is_bonus ? '<span class="text-green-600 dark:text-green-400">Bonus</span>' : 'Rp ' + fmt(item.subtotal)}
                </td>
            </tr>`).join('');
    } catch {
        document.getElementById('modal-items').innerHTML =
            `<tr><td colspan="3" class="px-4 py-4 text-center text-xs text-red-400">Gagal memuat item.</td></tr>`;
    }
}

async function setujuiVoid() {
    if (!activeVoidId) return;

    const btn = document.querySelector('[onclick="setujuiVoid()"]');
    btn.disabled = true;
    document.getElementById('spinner-setuju').classList.remove('hidden');
    document.getElementById('lbl-setuju').textContent = 'Menyetujui...';

    try {
        await axios.post(`/api/owner/void/${activeVoidId}/approve`);
        tutupModal();
        showToast('success', 'Void Disetujui', 'Event dikirim ke antrian untuk diproses.');
        // Hapus dari list lokal, re-render
        pengajuanList = pengajuanList.filter(v => v.id !== activeVoidId);
        renderTabel();
    } catch (err) {
        const msg = err.response?.data?.message || 'Gagal menyetujui void.';
        showToast('error', 'Gagal', msg);
        btn.disabled = false;
        document.getElementById('spinner-setuju').classList.add('hidden');
        document.getElementById('lbl-setuju').textContent = 'Setujui Void';
    }
}

async function tolakVoid() {
    if (!activeVoidId) return;

    const btnTolak = document.querySelector('[onclick="tolakVoid()"]');
    btnTolak.disabled = true;
    document.getElementById('spinner-tolak').classList.remove('hidden');

    try {
        await axios.post(`/api/owner/void/${activeVoidId}/reject`);
        tutupModal();
        showToast('warning', 'Void Ditolak', 'Pengajuan void ditolak. Kasir sudah diberitahu.');
        pengajuanList = pengajuanList.filter(v => v.id !== activeVoidId);
        renderTabel();
    } catch (err) {
        const msg = err.response?.data?.message || 'Gagal menolak void.';
        showToast('error', 'Gagal', msg);
        btnTolak.disabled = false;
        document.getElementById('spinner-tolak').classList.add('hidden');
    }
}

// ── Utilitas Modal ────────────────────────────────────────────────────────────
function tampilkanModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
}

function tutupModal() {
    const el = document.getElementById('modal-detail');
    el.classList.add('hidden');
    el.classList.remove('flex');
    activeVoidId = null;

    // Reset tombol modal
    const btnSetuju = document.querySelector('[onclick="setujuiVoid()"]');
    const btnTolak  = document.querySelector('[onclick="tolakVoid()"]');
    if (btnSetuju) { btnSetuju.disabled = false; }
    if (btnTolak)  { btnTolak.disabled  = false; }
    document.getElementById('spinner-setuju').classList.add('hidden');
    document.getElementById('spinner-tolak').classList.add('hidden');
    document.getElementById('lbl-setuju').textContent = 'Setujui Void';
}

document.getElementById('modal-detail')?.addEventListener('click', e => {
    if (e.target === document.getElementById('modal-detail')) tutupModal();
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupModal(); });

// ── WebSocket — dengarkan via CustomEvent dari global handleWsEvent ──────────
// Global handleWsEvent (app.blade.php) dispatch CustomEvent setelah proses toast.
// Halaman ini tinggal listen event tersebut — tidak perlu override handleWsEvent.

document.addEventListener('ws:void-submitted', (e) => {
    // Kasir baru ajukan void → reload dari API agar data lengkap (termasuk nama kasir)
    const { voidId } = e.detail;
    const sudahAda = pengajuanList.some(v => v.id === voidId);
    if (!sudahAda) {
        muatPengajuan();
    }
});

document.addEventListener('ws:void-completed', (e) => {
    // Consumer selesai proses void yang disetujui owner → hapus dari list
    pengajuanList = pengajuanList.filter(v => v.transaksi_id !== e.detail.transaksiId);
    renderTabel();
});

// ── Format Helpers ────────────────────────────────────────────────────────────
function fmt(n) { return new Intl.NumberFormat('id-ID').format(n ?? 0); }
function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function fmtDatetime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    muatPengajuan();
    // Tidak perlu auto-refresh — update real-time via WebSocket (void.submitted / void.completed)
});

window.addEventListener('beforeunload', stopCountdowns);
</script>
@endpush
