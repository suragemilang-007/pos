<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Endpoint login tidak butuh autentikasi
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            // min:6 untuk mencegah brute-force dengan password satu karakter,
            // tidak perlu validasi kompleksitas karena password sudah di-hash di DB
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi',
            'password.required' => 'Password wajib diisi',
            'password.min'      => 'Password minimal 6 karakter',
        ];
    }

    /**
     * Override failedValidation agar response error konsisten dengan format API standar.
     * Default Laravel mengembalikan redirect — kita perlu JSON karena ini API endpoint.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
