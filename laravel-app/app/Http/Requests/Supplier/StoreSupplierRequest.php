<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_supplier'  => ['required', 'string', 'max:150'],
            'kontak_person'  => ['nullable', 'string', 'max:100'],
            'no_telp'        => ['required', 'string', 'max:20', 'unique:suppliers,no_telp'],
            'alamat'         => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_telp.unique' => 'Nomor telepon supplier sudah terdaftar',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data supplier tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
