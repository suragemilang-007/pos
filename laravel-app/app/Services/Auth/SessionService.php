<?php

namespace App\Services\Auth;

use App\Helpers\JwtHelper;
use App\Models\Session;
use Illuminate\Support\Carbon;

class SessionService
{
    // Harus sama dengan JwtHelper::TTL_SECONDS agar expired_at di DB
    // konsisten dengan klaim 'exp' di dalam token JWT
    private const TTL_SECONDS = 8 * 3600;

    /**
     * Buat session baru dan kembalikan JWT token.
     *
     * Alur: generate token via JwtHelper → INSERT ke tabel sessions → return token.
     * Token disimpan di DB bukan hanya di JWT supaya bisa di-revoke kapan saja
     * (JWT stateless by nature, tidak bisa di-invalidate tanpa blacklist).
     * Tabel sessions adalah blacklist-complement: hanya token yang ADA di DB
     * dengan status='active' yang diterima.
     */
    public function create(int $userId): string
    {
        // Ambil role dari user untuk payload JWT
        $user = \App\Models\User::with('role')->findOrFail($userId);

        $token = JwtHelper::generate(
            $user->id,
            $user->role_id,
            $user->role->nama_role
        );

        Session::create([
            'user_id'    => $userId,
            'token'      => $token,
            'status'     => 'active',
            // expired_at sinkron dengan klaim 'exp' di JWT
            'expired_at' => Carbon::now()->addSeconds(self::TTL_SECONDS),
        ]);

        return $token;
    }

    /**
     * Cek apakah token masih valid di sisi database.
     *
     * Validasi dua lapis:
     * 1. Status harus 'active' — menangkap token yang sudah di-revoke via logout
     * 2. expired_at > now() — redundan dengan JWT 'exp', tapi defense-in-depth:
     *    jika admin hapus baris dari DB atau ada bug di JwtHelper, sisi DB tetap catch
     */
    public function isValid(string $token): bool
    {
        return Session::where('token', $token)
            ->where('status', 'active')
            ->where('expired_at', '>', Carbon::now())
            ->exists();
    }

    /**
     * Revoke token spesifik — dipanggil saat user logout.
     * UPDATE status='revoked' lebih aman dari DELETE karena audit trail tetap ada.
     */
    public function revoke(string $token): bool
    {
        return (bool) Session::where('token', $token)
            ->update(['status' => 'revoked']);
    }

    /**
     * Revoke semua session milik user — dipanggil saat owner nonaktifkan user.
     * Memastikan user yang di-suspend tidak bisa melanjutkan sesi yang sedang berjalan
     * di device manapun.
     */
    public function revokeAllByUser(int $userId): bool
    {
        Session::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'revoked']);

        return true;
    }
}
