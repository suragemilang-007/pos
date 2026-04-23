<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil role berdasarkan nama — bukan hardcode ID,
        // agar tetap benar meski urutan insert berubah
        $roleOwner       = Role::where('nama_role', 'owner')->value('id');
        $roleKasir       = Role::where('nama_role', 'kasir')->value('id');
        $roleAdminGudang = Role::where('nama_role', 'admin_gudang')->value('id');

        $users = [
            [
                'role_id'  => $roleOwner,
                'nama'     => 'Ignas',
                'username' => 'Ignas007',
                // bcrypt hash — password_hash TIDAK boleh plain text di database
                'password_hash' => Hash::make('Indonesia123'),
                'status'   => 'aktif',
            ],
            [
                'role_id'  => $roleKasir,
                'nama'     => 'Kasir 1',
                'username' => 'kasir1',
                'password_hash' => Hash::make('kasir123'),
                'status'   => 'aktif',
            ],
            [
                'role_id'  => $roleAdminGudang,
                'nama'     => 'Gudang 1',
                'username' => 'gudang1',
                'password_hash' => Hash::make('gudang123'),
                'status'   => 'aktif',
            ],
        ];

        foreach ($users as $data) {
            // Buat user lalu set password_hash secara eksplisit —
            // password_hash tidak ada di $fillable untuk mencegah mass assignment
            $user = new User();
            $user->role_id       = $data['role_id'];
            $user->nama          = $data['nama'];
            $user->username      = $data['username'];
            $user->password_hash = $data['password_hash'];
            $user->status        = $data['status'];
            $user->save();
        }
    }
}
