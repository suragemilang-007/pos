<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Kasir') — Toko Melati</title>

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
    <script>(function(){if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');})();</script>

    @stack('head')
</head>

{{-- Layout khusus kasir: full-screen, overflow-hidden, tanpa sidebar (§10.5 Kasir Layout) --}}
<body class="h-full bg-gray-100 dark:bg-gray-900 font-sans antialiased overflow-hidden">

{{-- ══ HEADER KASIR (h-14) ══════════════════════════════════════════════════════ --}}
<header class="h-14 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 shrink-0">

    {{-- Kiri: Logo + halaman aktif --}}
    <div class="flex items-center gap-3">
        <div class="w-7 h-7 bg-indigo-600 rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Toko Melati</span>
        <span class="hidden sm:block text-xs text-gray-400 dark:text-gray-500">/ @yield('page-title', 'Kasir')</span>
    </div>

    {{-- Kanan: WS indicator, nama user, dark mode, tombol navigasi --}}
    <div class="flex items-center gap-3">

        {{-- Indikator WebSocket real-time (§10.6I) --}}
        <div class="flex items-center gap-1.5" title="Status koneksi real-time">
            <span class="relative flex h-2 w-2">
                <span id="ws-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gray-300 opacity-75"></span>
                <span id="ws-dot"  class="relative inline-flex rounded-full h-2 w-2 bg-gray-300"></span>
            </span>
            <span id="ws-label" class="hidden sm:block text-xs text-gray-500 dark:text-gray-400">Menghubungkan...</span>
        </div>

        {{-- Nama user yang login --}}
        <span id="kasir-nama" class="text-xs font-medium text-gray-600 dark:text-gray-400 hidden sm:block">—</span>

        {{-- Dark mode toggle (§10.9) --}}
        <button onclick="toggleTheme()" title="Ganti Tema"
            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="hidden dark:block w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <svg class="block dark:hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>

        {{-- Navigasi balik — pages dapat override section ini --}}
        @hasSection('header-back')
            @yield('header-back')
        @else
            <a href="/kasir" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">← Dashboard</a>
        @endif
    </div>
</header>

{{-- ══ AREA KONTEN — sisa viewport di bawah header ═══════════════════════════ --}}
<div style="height: calc(100vh - 3.5rem)">
    @yield('content')
</div>

{{-- Toast container (§10.6G) --}}
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2 w-80 pointer-events-none"></div>

{{-- ══ JAVASCRIPT GLOBAL KASIR ═══════════════════════════════════════════════ --}}
<script>
// ── Auth check ────────────────────────────────────────────────────────────────
const authToken = localStorage.getItem('auth_token');
const authUser  = JSON.parse(localStorage.getItem('auth_user') || 'null');
const authRole  = localStorage.getItem('auth_role');

if (!authToken || !authUser) { window.location.replace('/login'); }
if (!['kasir', 'owner'].includes(authRole)) { window.location.replace('/login'); }

// Set header Authorization untuk semua Axios request
axios.defaults.headers.common['Authorization'] = 'Bearer ' + authToken;

// 401 → token expired atau session dicabut → paksa re-login
axios.interceptors.response.use(res => res, err => {
    if (err.response && err.response.status === 401) {
        ['auth_token', 'auth_user', 'auth_role'].forEach(k => localStorage.removeItem(k));
        window.location.replace('/login');
    }
    return Promise.reject(err);
});

// ── Init sidebar & nama user ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const namaEl = document.getElementById('kasir-nama');
    if (namaEl && authUser) namaEl.textContent = authUser.nama || '—';

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
let wsConn  = null;
let wsTimer = null;

function connectWebSocket() {
    if (wsConn && wsConn.readyState === WebSocket.OPEN) return;
    setWsState('connecting');

    wsConn = new WebSocket('ws://' + location.host + '/ws?channel=stok-global');

    wsConn.addEventListener('open', function () {
        setWsState('connected');
        if (wsTimer) { clearTimeout(wsTimer); wsTimer = null; }
        // Subscribe ke channel personal user untuk notifikasi transaksi
        if (authUser && authUser.id) {
            wsConn.send(JSON.stringify({ action: 'subscribe', channel: 'user-' + authUser.id }));
        }
    });

    wsConn.addEventListener('message', function (e) {
        try { handleWsEvent(JSON.parse(e.data)); } catch (_) {}
    });

    wsConn.addEventListener('close', function () {
        setWsState('disconnected');
        wsTimer = setTimeout(connectWebSocket, 3000);
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

    ping.className = `animate-ping absolute inline-flex h-full w-full rounded-full bg-${cfg.c}-400 opacity-75`;
    dot.className  = `relative inline-flex rounded-full h-2 w-2 bg-${cfg.c}-500`;
    if (label) label.textContent = cfg.t;
}

// Dispatch DOM event agar setiap halaman bisa listen event WS secara independen.
// Nama event: 'ws:transaction-completed', 'ws:transaction-failed', 'ws:stock-updated', dst.
function handleWsEvent(data) {
    const eventName = (data.event || '').replace(/\./g, '-');
    document.dispatchEvent(new CustomEvent('ws:' + eventName, { detail: data }));

    // Emit stok-updated sebagai alias untuk kompatibilitas listener yang sudah ada
    if (data.event === 'stock.updated') {
        document.dispatchEvent(new CustomEvent('stok-updated', { detail: data.updates || [] }));
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

function formatRupiah(n) { return new Intl.NumberFormat('id-ID').format(n); }
</script>

@stack('scripts')
</body>
</html>
