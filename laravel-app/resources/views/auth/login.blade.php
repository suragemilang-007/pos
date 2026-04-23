<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem POS Toko Melati</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-blue-600">Toko Melati</h1>
            <p class="text-gray-500 mt-2">Sistem Kasir Grosir</p>
        </div>

        <form id="login-form">
            <div id="error-message" class="hidden mb-4 p-3 bg-red-100 text-red-700 rounded text-sm"></div>

            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="username" name="username" required
                    class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required
                    class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <button type="submit" id="login-btn"
                class="w-full flex justify-center py-2 px-4 shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Login
            </button>
        </form>
    </div>

    <script>
        // Check if already logged in
        if (localStorage.getItem('token')) {
            window.location.href = '/dashboard';
        }

        const loginForm = document.getElementById('login-form');
        const errorDiv = document.getElementById('error-message');
        const loginBtn = document.getElementById('login-btn');

        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            errorDiv.classList.add('hidden');
            loginBtn.textContent = 'Memproses...';
            loginBtn.disabled = true;

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            try {
                const response = await axios.post('/api/auth/login', {
                    username: username,
                    password: password
                });

                if (response.data && response.data.data && response.data.data.token) {
                    const data = response.data.data;
                    // Save token to localStorage
                    localStorage.setItem('token', data.token);
                    
                    // Save user data (assuming the API returns user object nested or directly)
                    const userData = data.user || {};
                    userData.role = data.role ? data.role.nama_role : '';
                    localStorage.setItem('user', JSON.stringify(userData));

                    // Setup default headers for following requests
                    axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;

                    // Redirect based on role
                    window.location.href = '/dashboard';
                } else {
                    throw new Error('Respon tidak valid');
                }
            } catch (error) {
                // Determine error message
                let message = 'Terjadi kesalahan saat login';
                if (error.response) {
                    message = error.response.data.message || 'Username atau password salah';
                }
                
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
            } finally {
                loginBtn.textContent = 'Login';
                loginBtn.disabled = false;
            }
        });
    </script>
</body>
</html>