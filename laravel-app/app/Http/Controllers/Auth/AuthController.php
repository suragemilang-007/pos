<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * POST /api/auth/login
     *
     * Controller hanya orchestrate: validasi (ditangani LoginRequest) → service → response.
     * Tidak ada logika bisnis di sini — semua ada di UserService.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->userService->login(
                $request->validated('username'),
                $request->validated('password')
            );

            return response()->json(['data' => $result], 200);

        } catch (\RuntimeException $e) {
            // RuntimeException dari UserService selalu bermakna kredensial salah atau akun nonaktif
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    /**
     * POST /api/auth/logout
     *
     * Token diambil dari request attributes yang sudah di-set middleware AuthenticateJWT.
     * Middleware sudah memvalidasi token sebelum request sampai di sini,
     * sehingga Controller tidak perlu validasi ulang.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->input('auth_token');

        $this->userService->logout($token);

        return response()->json(['message' => 'Logout berhasil'], 200);
    }
}
