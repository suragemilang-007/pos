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
            'role_id' => ['sometimes', 'integer', 'exists:roles,id'],
            'status'  => ['sometimes', 'in:aktif,nonaktif'],
            // password tidak diterima di sini — ada endpoint /reset-password khusus
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Username sudah digunakan oleh user lain',
            'role_id.exists'  => 'Role tidak ditemukan',
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
