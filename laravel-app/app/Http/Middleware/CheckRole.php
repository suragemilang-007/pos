<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Cek apakah role user termasuk dalam daftar role yang diizinkan.
     *
     * Middleware ini wajib dijalankan SETELAH AuthenticateJWT dalam chain,
     * karena bergantung pada 'auth_role_name' yang di-set oleh AuthenticateJWT.
     * Contoh penggunaan di route: middleware(['auth.jwt', 'role:kasir,owner'])
     *
     * @param  string  ...$allowedRoles  Role yang boleh mengakses, dari parameter route middleware
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        // 'auth_role_name' di-set oleh AuthenticateJWT::handle() dari payload JWT.
        // Tidak perlu query DB karena role sudah ada di token — satu round trip hemat.
        $userRole = $request->input('auth_role_name');

        if (!$userRole || !in_array($userRole, $allowedRoles, strict: true)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke fitur ini',
            ], 403);
        }

        return $next($request);
    }
}
