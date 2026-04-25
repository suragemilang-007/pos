<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Toko Melati POS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] } } }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    {{-- Terapkan dark mode sebelum render --}}
    <script>
        (function () {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>

<body class="h-full bg-gray-50 dark:bg-gray-900 font-sans antialiased flex items-center justify-center p-4">

    {{-- Dark mode toggle pojok kanan atas --}}
    <div class="fixed top-4 right-4">
        <button onclick="toggleTheme()" title="Ganti Tema"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm transition-colors">
            <svg class="hidden dark:block w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <svg class="block dark:hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>
    </div>

    {{-- Card login --}}
    <div class="w-full max-w-sm">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-600 rounded-2xl shadow-lg mb-4">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Toko Melati</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sistem Point of Sales Grosir</p>
        </div>

        {{-- Form card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-8">

            {{-- Alert error --}}
            {{-- `flex` tidak diletakkan di sini — ditambahkan via JS saat ditampilkan
                 agar tidak konflik dengan `hidden` (keduanya set property display) --}}
            <div id="alert-error"
                class="hidden mb-5 items-start gap-3 p-3.5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <span class="text-red-500 dark:text-red-400 font-bold shrink-0">✕</span>
                <p id="alert-error-text" class="text-sm text-red-700 dark:text-red-300"></p>
            </div>

            <form id="login-form" class="space-y-5" novalidate>

                {{-- Username --}}
                <div class="space-y-1">
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Username
                    </label>
                    <input type="text" id="username" name="username" autocomplete="username"
                        placeholder="Masukkan username"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
                    <p id="err-username" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                </div>

                {{-- Password --}}
                <div class="space-y-1">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" autocomplete="current-password"
                            placeholder="Masukkan password"
                            class="block w-full px-3 py-2 pr-10 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
                        {{-- Toggle tampil/sembunyikan password --}}
                        <button type="button" id="toggle-password"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg id="eye-open"  class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg id="eye-closed" class="hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    <p id="err-password" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                </div>

                {{-- Tombol masuk --}}
                <button type="submit" id="btn-login"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    <svg id="btn-spinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span id="btn-text">Masuk</span>
                </button>

            </form>
        </div>

        <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">
            Toko Melati © {{ date('Y') }} — Sistem POS Grosir
        </p>
    </div>

    <script>
    // ── Redirect jika sudah login ─────────────────────────────────────────────
    (function () {
        const token = localStorage.getItem('auth_token');
        const role  = localStorage.getItem('auth_role');
        if (token && role) {
            const destinations = {
                kasir:        '/kasir',
                admin_gudang: '/produk',
                owner:        '/laporan',
            };
            window.location.replace(destinations[role] || '/dashboard');
        }
    })();

    // ── Dark mode ─────────────────────────────────────────────────────────────
    function toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    }

    // ── Toggle password visibility ────────────────────────────────────────────
    document.getElementById('toggle-password').addEventListener('click', function () {
        const input    = document.getElementById('password');
        const eyeOpen  = document.getElementById('eye-open');
        const eyeClose = document.getElementById('eye-closed');
        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        eyeOpen.classList.toggle('hidden', isHidden);
        eyeClose.classList.toggle('hidden', !isHidden);
    });

    // ── Form submit ───────────────────────────────────────────────────────────
    document.getElementById('login-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        // Reset error state
        setError('alert-error', null);
        setFieldError('err-username', null);
        setFieldError('err-password', null);
        highlightField('username', false);
        highlightField('password', false);

        // Validasi sisi klien — hindari request tidak perlu ke server
        let hasError = false;
        if (!username) {
            setFieldError('err-username', 'Username wajib diisi');
            highlightField('username', true);
            hasError = true;
        }
        if (!password || password.length < 6) {
            setFieldError('err-password', 'Password minimal 6 karakter');
            highlightField('password', true);
            hasError = true;
        }
        if (hasError) return;

        setLoading(true);

        try {
            const response = await axios.post('/api/auth/login', { username, password });
            const data = response.data.data;

            // Simpan ke localStorage menggunakan key yang konsisten dengan app.blade.php
            localStorage.setItem('auth_token', data.token);
            localStorage.setItem('auth_user',  JSON.stringify(data.user));
            localStorage.setItem('auth_role',  data.role.nama_role);

            // Redirect berdasarkan role
            const destinations = {
                kasir:        '/kasir',
                admin_gudang: '/produk',
                owner:        '/laporan',
            };
            window.location.replace(destinations[data.role.nama_role] || '/dashboard');

        } catch (err) {
            const status  = err.response?.status;
            const message = err.response?.data?.message || 'Terjadi kesalahan, coba lagi.';

            if (status === 422) {
                // Error validasi dari server (misal: password < 6 dari FormRequest)
                const errors = err.response?.data?.errors || {};
                if (errors.username) setFieldError('err-username', errors.username[0]);
                if (errors.password) setFieldError('err-password', errors.password[0]);
            } else {
                // 401 atau error lain — tampilkan di alert
                setError('alert-error', message);
            }
        } finally {
            setLoading(false);
        }
    });

    // ── Helpers ───────────────────────────────────────────────────────────────

    function setLoading(loading) {
        const btn     = document.getElementById('btn-login');
        const spinner = document.getElementById('btn-spinner');
        const text    = document.getElementById('btn-text');

        btn.disabled = loading;
        spinner.classList.toggle('hidden', !loading);
        text.textContent = loading ? 'Memproses...' : 'Masuk';
        btn.classList.toggle('opacity-75', loading);
        btn.classList.toggle('cursor-not-allowed', loading);
    }

    function setError(elementId, message) {
        const el = document.getElementById(elementId);
        if (!el) return;
        if (message) {
            document.getElementById('alert-error-text').textContent = message;
            // Tambah `flex` saat tampil — tidak bisa disimpan di HTML bersama `hidden`
            // karena keduanya set property `display` dan akan saling override
            el.classList.remove('hidden');
            el.classList.add('flex');
        } else {
            el.classList.add('hidden');
            el.classList.remove('flex');
        }
    }

    function setFieldError(elementId, message) {
        const el = document.getElementById(elementId);
        if (!el) return;
        if (message) {
            el.textContent = message;
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }

    function highlightField(fieldId, isError) {
        const el = document.getElementById(fieldId);
        if (!el) return;
        if (isError) {
            el.classList.add('border-red-500', 'dark:border-red-400');
            el.classList.remove('border-gray-300', 'dark:border-gray-600');
        } else {
            el.classList.remove('border-red-500', 'dark:border-red-400');
            el.classList.add('border-gray-300', 'dark:border-gray-600');
        }
    }
    </script>
</body>
</html>
