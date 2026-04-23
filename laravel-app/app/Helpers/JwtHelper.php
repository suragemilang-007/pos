<?php

namespace App\Helpers;

/**
 * JwtHelper — implementasi JWT HS256 tanpa library eksternal.
 *
 * Dipilih implementasi manual (bukan firebase/php-jwt atau tymon/jwt-auth) karena:
 * 1. Payload minimal — hanya butuh user_id, role_id, role_name, iat, exp
 * 2. Tidak ada dependensi tambahan yang perlu di-maintain
 * 3. Algoritma HS256 dengan hash_hmac cukup untuk sistem internal (tidak perlu RS256)
 *
 * Format token: base64url(header) . "." . base64url(payload) . "." . base64url(signature)
 */
class JwtHelper
{
    // Masa berlaku token: 8 jam — satu shift kerja kasir.
    // Jika token expired, kasir harus login ulang agar sesi tidak mengambang.
    private const TTL_SECONDS = 8 * 3600;

    private const HEADER = ['alg' => 'HS256', 'typ' => 'JWT'];

    /**
     * Buat JWT token untuk user yang berhasil login.
     *
     * @param  int    $userId    ID user dari tabel users
     * @param  int    $roleId    ID role (TINYINT) dari tabel roles
     * @param  string $roleName  Nama role (kasir|admin_gudang|owner) — disertakan
     *                           di payload agar middleware tidak perlu query DB
     *                           hanya untuk cek permission per request
     */
    public static function generate(int $userId, int $roleId, string $roleName): string
    {
        $now = time();

        $payload = [
            'user_id'   => $userId,
            'role_id'   => $roleId,
            'role_name' => $roleName,
            'iat'       => $now,                    // issued at
            'exp'       => $now + self::TTL_SECONDS, // expiry
        ];

        $headerEncoded  = self::base64UrlEncode(json_encode(self::HEADER));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = self::sign("{$headerEncoded}.{$payloadEncoded}");

        return "{$headerEncoded}.{$payloadEncoded}.{$signature}";
    }

    /**
     * Verifikasi token dan kembalikan payload jika valid.
     *
     * Urutan validasi:
     * 1. Pastikan format token 3 segmen (header.payload.signature)
     * 2. Verifikasi signature dengan HMAC-SHA256 — tolak jika tidak cocok
     * 3. Cek kolom 'exp' — tolak jika token sudah kadaluarsa
     *
     * Mengembalikan false (bukan throw Exception) agar pemanggil bisa
     * langsung return 401 tanpa try-catch yang verbose.
     *
     * @return array|false  Payload sebagai array asosiatif, atau false jika invalid
     */
    public static function verify(string $token): array|false
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        [$headerEncoded, $payloadEncoded, $signatureReceived] = $parts;

        // Hitung ulang signature dari header+payload yang diterima,
        // lalu bandingkan secara constant-time untuk mencegah timing attack
        $signatureExpected = self::sign("{$headerEncoded}.{$payloadEncoded}");

        if (!hash_equals($signatureExpected, $signatureReceived)) {
            return false;
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        if (!is_array($payload)) {
            return false;
        }

        // Tolak token yang sudah kadaluarsa
        if (!isset($payload['exp']) || time() > $payload['exp']) {
            return false;
        }

        return $payload;
    }

    /**
     * Buat HMAC-SHA256 signature dari string data.
     * Secret diambil dari env JWT_SECRET — wajib di-set di .env, tidak boleh hardcode.
     */
    private static function sign(string $data): string
    {
        $secret = env('JWT_SECRET');

        // Gagal dengan exception — JWT_SECRET kosong adalah kesalahan konfigurasi,
        // bukan kondisi runtime yang boleh di-swallow diam-diam
        if (empty($secret)) {
            throw new \RuntimeException('JWT_SECRET tidak ditemukan di environment. Set di .env sebelum menjalankan aplikasi.');
        }

        $rawSignature = hash_hmac('sha256', $data, $secret, true);

        return self::base64UrlEncode($rawSignature);
    }

    /**
     * Base64URL encoding (RFC 4648 §5) — berbeda dari base64 biasa:
     * '+' → '-', '/' → '_', dan padding '=' dibuang.
     * Diperlukan agar token aman dipakai sebagai URL parameter / HTTP header.
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        // Tambah padding kembali sebelum decode
        $padded = str_pad(strtr($data, '-_', '+/'), strlen($data) + (4 - strlen($data) % 4) % 4, '=');

        return base64_decode($padded);
    }
}
