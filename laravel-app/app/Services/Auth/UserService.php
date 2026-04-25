<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(private readonly SessionService $sessionService) {}

    // ─────────────────────────────────────────────────────────────────────────
    // MANAJEMEN USER (hanya owner)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Buat user baru.
     * password_hash di-set secara eksplisit (tidak via mass assignment) karena
     * kolom ini sengaja dikecualikan dari $fillable sebagai lapisan perlindungan.
     */
    public function create(array $data): User
    {
        $user                = new User();
        $user->role_id       = $data['role_id'];
        $user->nama          = $data['nama'];
        $user->username      = $data['username'];
        $user->password_hash = Hash::make($data['password']);
        $user->status        = 'aktif';
        $user->save();

        return $user->load('role');
    }

    /**
     * Update data user — tidak termasuk password (ada endpoint khusus).
     */
    public function update(int $userId, array $data): bool
    {
        // Buang password jika ada — harus lewat resetPassword()
        unset($data['password'], $data['password_hash']);

        return (bool) User::where('id', $userId)->update($data);
    }

    /**
     * Proses login: verifikasi kredensial → buat session → kembalikan token + info user.
     *
     * Menggunakan Hash::check() (bukan strcmp) karena bcrypt menghasilkan hash berbeda
     * setiap kali meski password sama — perbandingan harus lewat fungsi bcrypt verify.
     *
     * @throws \RuntimeException dengan kode 401 jika login gagal
     * @return array {token, role: {id, nama_role}, user: {id, nama, username}}
     */
    public function login(string $username, string $password): array
    {
        $user = User::with('role')
            ->where('username', $username)
            ->first();

        // Pesan error sengaja digabung (tidak membedakan "user tidak ada" vs "password salah")
        // untuk mencegah user enumeration attack — penyerang tidak tahu mana yang benar
        if (!$user || !Hash::check($password, $user->password_hash)) {
            throw new \RuntimeException('Username atau password salah', 401);
        }

        if ($user->status === 'nonaktif') {
            throw new \RuntimeException('Akun Anda telah dinonaktifkan oleh administrator', 401);
        }

        // Update timestamp login terakhir — berguna untuk laporan aktivitas user
        $user->last_login = now();
        $user->save();

        $token = $this->sessionService->create($user->id);

        return [
            'token' => $token,
            'role'  => [
                'id'        => $user->role->id,
                'nama_role' => $user->role->nama_role,
            ],
            'user'  => [
                'id'       => $user->id,
                'nama'     => $user->nama,
                'username' => $user->username,
                // password_hash TIDAK boleh ada di return value — aturan 2.6 CLAUDE.md
            ],
        ];
    }

    /**
     * Logout: revoke token di database.
     * Token yang di-revoke tidak akan lolos SessionService::isValid() meski JWT-nya masih berlaku.
     */
    public function logout(string $token): bool
    {
        return $this->sessionService->revoke($token);
    }

    /**
     * Validasi token: cek JWT + status session di DB.
     * Dipakai untuk endpoint yang butuh cek token tanpa full middleware (misal: WebSocket handshake).
     */
    public function validateToken(string $token): bool
    {
        return $this->sessionService->isValid($token);
    }

    /**
     * Reset password user — hanya dipanggil oleh owner.
     * Hash bcrypt dilakukan di sini, bukan di Controller, agar enkripsi terpusat di Service.
     */
    public function resetPassword(int $userId, string $newPassword): bool
    {
        $user = User::findOrFail($userId);
        $user->password_hash = Hash::make($newPassword);
        return $user->save();
    }

    /**
     * Ubah status aktif/nonaktif user.
     * Jika user dinonaktifkan, semua sesi aktifnya langsung di-revoke
     * agar user tidak bisa melanjutkan operasi di sesi yang sedang berjalan.
     */
    public function updateStatus(int $userId, string $status): bool
    {
        $user = User::findOrFail($userId);
        $user->status = $status;
        $saved = $user->save();

        if ($saved && $status === 'nonaktif') {
            $this->sessionService->revokeAllByUser($userId);
        }

        return $saved;
    }
}
