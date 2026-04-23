<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Wajib karena JwtHelper akan throw kalau JWT_SECRET kosong
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
        putenv('JWT_SECRET=test-secret-login');
        $_ENV['JWT_SECRET'] = 'test-secret-login';
        $_SERVER['JWT_SECRET'] = 'test-secret-login';
    }

    public function test_login_berhasil(): void
    {
        $role = Role::create([
            'nama_role' => 'kasir',
            'permissions' => json_encode(['transaksi.create']),
        ]);

        $user = User::create([
            'role_id' => $role->id,
            'nama' => 'Kasir Test',
            'username' => 'kasir1',
            'password_hash' => Hash::make('kasir123'),
            'status' => 'aktif',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'kasir1',
            'password' => 'kasir123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'role' => ['id', 'nama_role'],
                    'user' => ['id', 'nama', 'username'],
                ],
            ])
            ->assertJsonMissingPath('data.user.password_hash');

        $this->assertDatabaseHas('sessions', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $this->assertNotNull($user->fresh()->last_login);
    }

    public function test_login_gagal_password_salah(): void
    {
        $role = Role::create([
            'nama_role' => 'kasir',
            'permissions' => json_encode([]),
        ]);

        User::create([
            'role_id' => $role->id,
            'nama' => 'Kasir Test',
            'username' => 'kasir01',
            'password_hash' => Hash::make('rahasia123'),
            'status' => 'aktif',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'kasir01',
            'password' => 'salah123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Username atau password salah',
            ]);
    }

    public function test_login_gagal_jika_user_nonaktif(): void
    {
        $role = Role::create([
            'nama_role' => 'kasir',
            'permissions' => json_encode([]),
        ]);

        User::create([
            'role_id' => $role->id,
            'nama' => 'Kasir Test',
            'username' => 'kasir-nonaktif',
            'password_hash' => Hash::make('rahasia123'),
            'status' => 'nonaktif',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'kasir-nonaktif',
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Akun Anda telah dinonaktifkan oleh administrator',
            ]);
    }

    public function test_login_gagal_validasi_input(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => '',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['username', 'password']);
    }
}