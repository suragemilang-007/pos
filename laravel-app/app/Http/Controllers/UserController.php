<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * GET /api/owner/user — SD-15
     * Filter: ?role=kasir|admin_gudang|owner  &status=aktif|nonaktif
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('role:id,nama_role');

        if ($request->filled('search')) {
            $keyword = $request->string('search');
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$keyword}%")
                ->orWhere('username', 'like', "%{$keyword}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter berdasarkan slug role (kasir/admin_gudang/owner)
        if ($request->filled('role')) {
            $query->whereHas('role', fn ($q) => $q->where('nama_role', $request->input('role')));
        }

        $users = $query->orderBy('nama')->paginate((int) $request->input('limit', 20));

        // Seleksi field — password_hash TIDAK boleh dikembalikan dalam response apapun (aturan 2.6)
        // Field names disesuaikan dengan konvensi view: login_terakhir, role sebagai string slug
        $data = $users->getCollection()->map(fn ($u) => [
            'id'             => $u->id,
            'nama'           => $u->nama,
            'username'       => $u->username,
            'status'         => $u->status,
            'login_terakhir' => $u->last_login?->toIso8601String(),
            'role'           => $u->role?->nama_role,
        ]);

        return response()->json([
            'data'       => $data,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ], 200);
    }

    /**
     * GET /api/owner/user/cek-username?username=xxx — validasi unik realtime dari view
     * Response: { tersedia: true|false }
     */
    public function cekUsername(Request $request): JsonResponse
    {
        $username = $request->input('username', '');

        if (! $username) {
            return response()->json(['tersedia' => false]);
        }

        $tersedia = ! User::where('username', $username)->exists();

        return response()->json(['tersedia' => $tersedia]);
    }

    /**
     * POST /api/owner/user — SD-15
     * View mengirim 'role' sebagai slug string → di-resolve ke role_id sebelum ke Service.
     * Password di-hash di UserService::create().
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Resolve slug role → role_id (kasir/admin_gudang/owner → ID di tabel roles)
        $role = Role::where('nama_role', $validated['role'])->first();
        if (! $role) {
            return response()->json(['message' => 'Role tidak ditemukan di database'], 422);
        }
        $validated['role_id'] = $role->id;
        unset($validated['role']);

        $user = $this->userService->create($validated);

        return response()->json([
            'data' => [
                'id'       => $user->id,
                'nama'     => $user->nama,
                'username' => $user->username,
                'status'   => $user->status,
                'role'     => $user->role?->nama_role,
                // password_hash TIDAK dikembalikan — aturan 2.6 CLAUDE.md
            ],
            'message' => 'User berhasil dibuat',
        ], 201);
    }

    /**
     * PUT /api/owner/user/{id} — SD-15
     * Update nama, username, role. Tidak menerima password (ada endpoint /reset-password khusus).
     * View mengirim 'role' sebagai slug → di-resolve ke role_id.
     */
    public function update(UpdateUserRequest $request, int $user): JsonResponse
    {
        $u = User::find($user);

        if (! $u) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $validated = $request->validated();

        // Resolve slug role → role_id jika dikirim
        if (isset($validated['role'])) {
            $role = Role::where('nama_role', $validated['role'])->first();
            if (! $role) {
                return response()->json(['message' => 'Role tidak ditemukan di database'], 422);
            }
            $validated['role_id'] = $role->id;
            unset($validated['role']);
        }

        $this->userService->update($user, $validated);

        return response()->json([
            'message' => 'User berhasil diperbarui',
        ], 200);
    }

    /**
     * PATCH /api/owner/user/{id}/reset-password — SD-15
     */
    public function resetPassword(Request $request, int $user): JsonResponse
    {
        $passwordBaru = $request->input('password_baru');

        if (! $passwordBaru || strlen($passwordBaru) < 6) {
            return response()->json([
                'message' => 'Data tidak valid',
                'errors'  => ['password_baru' => ['Password baru minimal 6 karakter']],
            ], 422);
        }

        $u = User::find($user);
        if (! $u) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $this->userService->resetPassword($user, $passwordBaru);

        return response()->json(['message' => 'Password berhasil direset'], 200);
    }

    /**
     * PATCH /api/owner/user/{id}/nonaktifkan — SD-15, AD-15
     *
     * URUTAN WAJIB (sesuai AD-15):
     * 1. UPDATE users SET status='nonaktif' DULU
     * 2. BARU revoke semua session aktif milik user ini
     *
     * Mengapa urutan ini penting untuk keamanan?
     * Jika session di-revoke SEBELUM status di-update:
     *   - Ada window waktu di mana user masih bertatus 'aktif' tapi semua tokennya sudah expired
     *   - Jika user login ulang di window ini, mereka bisa dapat token baru yang valid
     *   - Token baru ini tidak akan di-revoke karena revokeAllByUser() sudah selesai
     *   - Hasilnya: user yang seharusnya nonaktif bisa terus mengakses sistem
     *
     * Dengan urutan benar (update status DULU):
     *   - Jika user mencoba login ulang setelah status='nonaktif',
     *     UserService::login() akan tolak dengan 401 "Akun Anda telah dinonaktifkan"
     *   - Race condition tidak mungkin meloloskan user nonaktif
     */
    public function nonaktifkan(int $user): JsonResponse
    {
        $u = User::find($user);

        if (! $u) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        // Cegah owner menonaktifkan dirinya sendiri
        if ($u->id === request()->input('auth_user_id')) {
            return response()->json([
                'message' => 'Tidak dapat menonaktifkan akun sendiri',
            ], 400);
        }

        if ($u->status === 'nonaktif') {
            return response()->json(['message' => 'User sudah nonaktif'], 400);
        }

        // UserService::updateStatus() melakukan kedua operasi dengan urutan yang benar:
        // 1. UPDATE users SET status='nonaktif' ($user->save())
        // 2. SessionService::revokeAllByUser($userId) — setelah status tersimpan
        $this->userService->updateStatus($user, 'nonaktif');

        return response()->json([
            'message' => "User '{$u->nama}' berhasil dinonaktifkan dan semua sesinya telah dicabut",
        ], 200);
    }
}
