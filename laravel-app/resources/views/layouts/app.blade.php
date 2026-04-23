<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem POS Toko Melati</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-100 flex h-screen">

    <!-- Sidebar -->
    <aside id="sidebar" class="bg-white w-64 border-r flex flex-col hidden lg:flex">
        <div class="h-16 flex items-center justify-center border-b">
            <h1 class="text-xl font-bold text-blue-600">Toko Melati</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1" id="sidebar-nav">
            <!-- Sidebar items will be injected by JS based on role -->
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Navbar -->
        <header class="h-16 bg-white border-b flex items-center justify-between px-6">
            <div class="flex items-center">
                <button id="mobile-menu-btn" class="lg:hidden mr-4 text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-600">
                    <span id="user-name" class="font-medium">User</span>
                    <span id="user-role" class="ml-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Role</span>
                </div>
                <button id="logout-btn" class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 focus:outline-none">
                    Keluar
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script>
        // Init Axios & Token
        const token = localStorage.getItem('token');
        const userStr = localStorage.getItem('user');
        
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        } else {
            // Redirect to login if not in login page
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }

        // Populate User Info & Sidebar
        if (userStr) {
            try {
                const user = JSON.parse(userStr);
                document.getElementById('user-name').textContent = user.nama || user.username;
                document.getElementById('user-role').textContent = user.role;

                // Render Sidebar Menu based on role
                const nav = document.getElementById('sidebar-nav');
                let menuHtml = `<a href="/dashboard" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Dashboard</a>`;
                
                if (user.role === 'kasir') {
                    menuHtml += `
                        <a href="/transaksi" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Penjualan</a>
                        <a href="/retur-penjualan" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Retur Penjualan</a>
                    `;
                } else if (user.role === 'admin_gudang') {
                    menuHtml += `
                        <a href="/produk" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Kelola Produk</a>
                        <a href="/pembelian" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Pembelian Restock</a>
                        <a href="/retur-pembelian" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Retur Pembelian</a>
                        <a href="/supplier" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Data Supplier</a>
                    `;
                } else if (user.role === 'owner') {
                    menuHtml += `
                        <a href="/laporan/penjualan" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Laporan Penjualan</a>
                        <a href="/laporan/stok" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Laporan Stok</a>
                        <a href="/users" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">Kelola Karyawan</a>
                    `;
                }

                nav.innerHTML = menuHtml;
            } catch (e) {
                console.error('Failed to parse user info', e);
            }
        }

        // Logout logic
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function() {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            });
        }

        // WebSocket Client Initialization
        let wss;
        function connectWebSocket() {
            const wsUrl = `ws://localhost/ws`;
            
            wss = new WebSocket(wsUrl);
            
            wss.onopen = () => {
                console.log('WebSocket Connected to ' + wsUrl);
            };

            wss.onmessage = (event) => {
                console.log('Message from server: ', event.data);
                // Handle realtime events here (update stock, new transaction notification, etc.)
            };

            wss.onclose = () => {
                console.log('WebSocket Disconnected, attempting to reconnect...');
                setTimeout(connectWebSocket, 5000);
            };
            
            wss.onerror = (error) => {
                console.error('WebSocket Error', error);
            };
        }

        // Initialize WebSocket if a user is logged in
        if (token) {
            connectWebSocket();
        }

        // Basic mobile menu toggle
        const mobileBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        if (mobileBtn && sidebar) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('absolute');
                sidebar.classList.toggle('z-10');
                sidebar.classList.toggle('h-full');
            });
        }
    </script>
    
    @stack('scripts')
</body>
</html>