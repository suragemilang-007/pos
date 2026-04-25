<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Toko Melati') — POS</title>

    {{-- Font Inter (§10.4) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CSS CDN + dark mode class strategy (§10.2) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] } } }
        }
    </script>

    {{-- Axios CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    {{-- Terapkan dark mode SEBELUM render agar tidak flicker --}}
    <script>
        (function () {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @stack('head')
</head>

<body class="h-full bg-gray-50 dark:bg-gray-900 font-sans antialiased">

{{-- ══════════════════════════════════════════════════════
     SIDEBAR — Fixed w-64 (§10.5)
══════════════════════════════════════════════════════ --}}
<aside class="fixed inset-y-0 left-0 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 z-30 flex flex-col">

    {{-- Logo & nama toko --}}
    <div class="flex items-center gap-3 px-5 h-16 border-b border-gray-200 dark:border-gray-700 shrink-0">
        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">Toko Melati</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Point of Sales</p>
        </div>
    </div>

    {{-- Navigasi — tiap grup data-roles dikontrol JS (§10.7) --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">

        {{-- Kasir & Owner --}}
        <div class="nav-group" data-roles="kasir,owner">
            <p class="px-3 pt-2 pb-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Transaksi</p>
            <a href="/kasir" class="nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Kasir
            </a>
            <a href="/stok" class="nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Pantau Stok
            </a>
            <a href="/customer" class="nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                Data Customer
            </a>
        </div>

        {{-- Admin Gudang & Owner --}}
        <div class="nav-group mt-2" data-roles="admin_gudang,owner">
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Inventori</p>
            <a href="/produk" class="nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                Data Produk
            </a>
            <a href="/pembelian" class="nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Pembelian
            </a>
            <a href="/supplier" class="nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Supplier
            </a>
        </div>

        {{-- Owner saja --}}
        <div class="nav-group mt-2" data-roles="owner">
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Manajemen</p>
            <a href="/laporan" class="nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Laporan
            </a>
            <a href="/user" class="nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Kelola User
            </a>
        </div>

    </nav>

    {{-- User info & Logout --}}
    <div class="shrink-0 px-4 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center shrink-0">
                <span id="sidebar-avatar" class="text-sm font-bold text-indigo-600 dark:text-indigo-400">?</span>
            </div>
            <div class="flex-1 min-w-0">
                <p id="sidebar-nama" class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">—</p>
                <p id="sidebar-role" class="text-xs text-gray-500 dark:text-gray-400">—</p>
            </div>
            <button onclick="logout()" title="Keluar"
                class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </div>
    </div>
</aside>

{{-- ══════════════════════════════════════════════════════
     MAIN AREA — ml-64
══════════════════════════════════════════════════════ --}}
<div class="ml-64 flex flex-col min-h-screen">

    {{-- Header sticky --}}
    <header class="sticky top-0 h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 z-20 flex items-center justify-between px-6">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            @yield('page-title', 'Dashboard')
        </h1>

        <div class="flex items-center gap-3">
            {{-- Indikator WebSocket real-time (§10.6I) --}}
            <div class="flex items-center gap-1.5" title="Status koneksi real-time">
                <span class="relative flex h-2.5 w-2.5">
                    <span id="ws-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gray-300 opacity-75"></span>
                    <span id="ws-dot"  class="relative inline-flex rounded-full h-2.5 w-2.5 bg-gray-300"></span>
                </span>
                <span id="ws-label" class="text-xs text-gray-500 dark:text-gray-400">Menghubungkan...</span>
            </div>

            {{-- Dark mode toggle (§10.9) --}}
            <button onclick="toggleTheme()" title="Ganti Tema"
                class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <svg class="hidden dark:block w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg class="block dark:hidden w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>
        </div>
    </header>

    <main class="flex-1 p-6">
        @yield('content')
    </main>
</div>

{{-- Toast container (§10.6G) --}}
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2 w-80 pointer-events-none"></div>

{{-- ══════════════════════════════════════════════════════
     JAVASCRIPT GLOBAL
══════════════════════════════════════════════════════ --}}
<script>
// ── Auth check ───────────────────────────────────────────────────────────────
// localStorage keys konsisten: auth_token, auth_user, auth_role
const authToken = localStorage.getItem('auth_token');
const authUser  = JSON.parse(localStorage.getItem('auth_user') || 'null');
const authRole  = localStorage.getItem('auth_role');

if (!authToken || !authUser) {
    window.location.replace('/login');
}

// Set token default — semua Axios request otomatis terbawa Authorization header
axios.defaults.headers.common['Authorization'] = 'Bearer ' + authToken;

// 401 global interceptor: token expired atau session dicabut → paksa re-login
axios.interceptors.response.use(
    res => res,
    err => {
        if (err.response && err.response.status === 401) {
            ['auth_token', 'auth_user', 'auth_role'].forEach(k => localStorage.removeItem(k));
            window.location.replace('/login');
        }
        return Promise.reject(err);
    }
);

// ── Sidebar init ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Isi avatar & info user
    const avatarEl = document.getElementById('sidebar-avatar');
    const namaEl   = document.getElementById('sidebar-nama');
    const roleEl   = document.getElementById('sidebar-role');

    if (authUser && avatarEl) {
        avatarEl.textContent = (authUser.nama || '?').charAt(0).toUpperCase();
        namaEl.textContent   = authUser.nama || '—';
    }

    const roleLabel = { kasir: 'Kasir', admin_gudang: 'Admin Gudang', owner: 'Pemilik Toko' };
    if (roleEl) roleEl.textContent = roleLabel[authRole] || authRole || '—';

    // Tampilkan/sembunyikan grup nav berdasarkan role
    document.querySelectorAll('.nav-group').forEach(function (g) {
        const allowed = (g.dataset.roles || '').split(',');
        g.style.display = allowed.includes(authRole) ? 'block' : 'none';
    });

    // Tandai link aktif sesuai URL — sesuai §10.7
    const path = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(function (a) {
        const href = a.getAttribute('href');
        if (href && href !== '/' && path.startsWith(href)) {
            a.classList.remove('text-gray-600', 'dark:text-gray-400',
                'hover:bg-gray-100', 'dark:hover:bg-gray-700/50');
            a.classList.add('text-indigo-700', 'dark:text-indigo-300',
                'bg-indigo-50', 'dark:bg-indigo-900/30', 'font-semibold');
        }
    });

    connectWebSocket();
});

// ── Dark mode ─────────────────────────────────────────────────────────────────
function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

// ── Logout ────────────────────────────────────────────────────────────────────
async function logout() {
    try { await axios.post('/api/auth/logout'); } catch (_) {}
    ['auth_token', 'auth_user', 'auth_role'].forEach(k => localStorage.removeItem(k));
    window.location.replace('/login');
}

// ── WebSocket Client ──────────────────────────────────────────────────────────
let wsConn = null;
let wsTimer = null;

function connectWebSocket() {
    if (wsConn && wsConn.readyState === WebSocket.OPEN) return;
    setWsState('connecting');

    // Path /ws diproxy nginx ke kafka-service:3001
    wsConn = new WebSocket('ws://' + location.host + '/ws?channel=stok-global');

    wsConn.addEventListener('open', function () {
        setWsState('connected');
        if (wsTimer) { clearTimeout(wsTimer); wsTimer = null; }

        // Subscribe ke channel personal user untuk notifikasi transaksi/retur
        if (authUser && authUser.id) {
            wsConn.send(JSON.stringify({ action: 'subscribe', channel: 'user-' + authUser.id }));
        }
    });

    wsConn.addEventListener('message', function (e) {
        try { handleWsEvent(JSON.parse(e.data)); } catch (_) {}
    });

    wsConn.addEventListener('close', function () {
        setWsState('disconnected');
        wsTimer = setTimeout(connectWebSocket, 3000); // Auto-reconnect
    });

    wsConn.addEventListener('error', function () { setWsState('disconnected'); });
}

function setWsState(state) {
    const ping  = document.getElementById('ws-ping');
    const dot   = document.getElementById('ws-dot');
    const label = document.getElementById('ws-label');
    if (!ping) return;

    const cfg = {
        connected:    { c: 'green', t: 'Real-time aktif'  },
        disconnected: { c: 'red',   t: 'Terputus'         },
        connecting:   { c: 'gray',  t: 'Menghubungkan...' },
    }[state] || { c: 'gray', t: '' };

    ping.className  = `animate-ping absolute inline-flex h-full w-full rounded-full bg-${cfg.c}-400 opacity-75`;
    dot.className   = `relative inline-flex rounded-full h-2.5 w-2.5 bg-${cfg.c}-500`;
    label.textContent = cfg.t;
}

function handleWsEvent(data) {
    switch (data.event) {
        case 'transaction.completed':
            showToast('success', 'Transaksi Berhasil',
                'No. ' + (data.transaksiId || '') + ' telah diproses.');
            break;
        case 'transaction.failed':
            showToast('error', 'Transaksi Gagal', data.message || 'Stok tidak mencukupi.');
            break;
        case 'purchase.completed':
            showToast('success', 'Restock Berhasil', 'Stok produk telah diperbarui.');
            break;
        case 'return.sale.completed':
            showToast('success', 'Retur Diterima',
                'Refund: Rp ' + formatRupiah(data.nilaiRefund || 0));
            break;
        case 'return.purchase.completed':
            showToast('success', 'Retur Pembelian Selesai',
                'Kredit: Rp ' + formatRupiah(data.nilaiKredit || 0));
            break;
        case 'stock.updated':
            // Emit custom event agar halaman stok update UI tanpa reload penuh
            document.dispatchEvent(new CustomEvent('stok-updated', { detail: data.updates || [] }));
            break;
    }
}

// ── Toast (§10.6G) ────────────────────────────────────────────────────────────
function showToast(type, title, message) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const styles = {
        success: { border: 'border-green-200 dark:border-green-800', icon: '✓', ic: 'text-green-500 dark:text-green-400' },
        error:   { border: 'border-red-200 dark:border-red-800',     icon: '✕', ic: 'text-red-500 dark:text-red-400'   },
        warning: { border: 'border-amber-200 dark:border-amber-800', icon: '⚠', ic: 'text-amber-500 dark:text-amber-400' },
        info:    { border: 'border-blue-200 dark:border-blue-800',   icon: 'ℹ', ic: 'text-blue-500 dark:text-blue-400'  },
    };
    const s = styles[type] || styles.info;

    const el = document.createElement('div');
    el.className = `flex items-start gap-3 p-4 bg-white dark:bg-gray-800 border ${s.border} rounded-xl shadow-lg pointer-events-auto transition-all duration-300`;
    el.innerHTML = `
        <div class="shrink-0 font-bold ${s.ic}">${s.icon}</div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${title}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${message}</p>
        </div>
        <button onclick="this.parentElement.remove()"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg leading-none shrink-0">×</button>`;

    container.appendChild(el);
    setTimeout(function () {
        el.style.opacity = '0';
        el.style.transform = 'translateX(2rem)';
        setTimeout(function () { el.remove(); }, 300);
    }, 5000);
}

// ── Utilitas ──────────────────────────────────────────────────────────────────
function formatRupiah(n) {
    return new Intl.NumberFormat('id-ID').format(n);
}
</script>

@stack('scripts')
</body>
</html>
