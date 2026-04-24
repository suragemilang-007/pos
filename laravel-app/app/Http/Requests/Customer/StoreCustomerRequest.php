<?php

namespace App\Http\Requests\Customer;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama'          => ['required', 'string', 'max:100'],
            'no_telp'       => ['required', 'string', 'max:20', 'unique:customers,no_telp'],
            'alamat'        => ['nullable', 'string'],
            'tipe_customer' => ['required', 'in:reguler,grosir'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_telp.unique'          => 'Nomor telepon sudah terdaftar',
            'tipe_customer.in'        => 'Tipe customer harus: reguler atau grosir',
            'tipe_customer.required'  => 'Tipe customer wajib diisi',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data customer tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
