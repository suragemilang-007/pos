<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'nama'    => ['sometimes', 'string', 'max:100'],
            'username'=> [
                'sometimes', 'string', 'max:50',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            // View mengirim slug role (kasir/admin_gudang/owner), bukan role_id integer
            'role'    => ['sometimes', 'string', 'in:kasir,admin_gudang,owner'],
            'status'  => ['sometimes', 'in:aktif,nonaktif'],
            // password tidak diterima di sini — ada endpoint /reset-password khusus
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Username sudah digunakan oleh user lain',
            'role.in'         => 'Role tidak valid. Pilih: kasir, admin_gudang, atau owner',
            'status.in'       => 'Status harus: aktif atau nonaktif',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data user tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
