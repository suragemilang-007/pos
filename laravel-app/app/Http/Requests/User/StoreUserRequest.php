<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            // View mengirim slug role (kasir/admin_gudang/owner), bukan role_id integer
            'role'     => ['required', 'string', 'in:kasir,admin_gudang,owner'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Username sudah digunakan',
            'password.min'    => 'Password minimal 8 karakter',
            'role.in'         => 'Role tidak valid. Pilih: kasir, admin_gudang, atau owner',
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
