<?php

namespace App\Http\Middleware;

use App\Helpers\JwtHelper;
use App\Services\Auth\SessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJWT
{
    public function __construct(private readonly SessionService $sessionService) {}

    /**
     * Validasi JWT di setiap request yang membutuhkan autentikasi.
     *
     * Urutan validasi disengaja:
     * 1. JwtHelper::verify() dulu — operasi lokal (tidak hit DB), memfilter
     *    token yang cacat/kedaluwarsa sebelum sempat query ke database.
     * 2. SessionService::isValid() kemudian — query DB hanya untuk token yang
     *    lolos verifikasi kriptografis, menangkap token yang sudah di-revoke.
     *
     * Jika dibalik (DB dulu), setiap request palsu akan membebani database.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        // Format wajib: "Bearer <token>" — tolak header lain
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Token tidak ditemukan');
        }

        $token = substr($authHeader, 7); // Buang prefix "Bearer "

        // Langkah 1: verifikasi kriptografis (signature + exp) — tanpa DB
        $payload = JwtHelper::verify($token);

        if ($payload === false) {
            return $this->unauthorized('Token tidak valid atau sudah kedaluwarsa');
        }

        // Langkah 2: cek apakah session masih aktif di DB (belum di-revoke)
        if (!$this->sessionService->isValid($token)) {
            return $this->unauthorized('Sesi sudah berakhir, silakan login kembali');
        }

        // Sisipkan data user ke request agar Controller/Service bisa akses
        // tanpa perlu query DB ulang — payload sudah berisi semua yang dibutuhkan
        $request->merge([
            'auth_user_id'   => $payload['user_id'],
            'auth_role_id'   => $payload['role_id'],
            'auth_role_name' => $payload['role_name'],
            'auth_token'     => $token,
        ]);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json(['message' => $message], 401);
    }
}
