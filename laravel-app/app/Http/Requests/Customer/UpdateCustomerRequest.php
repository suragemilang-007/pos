<?php

namespace App\Http\Requests\Customer;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            'nama'          => ['sometimes', 'string', 'max:100'],
            'no_telp'       => [
                'sometimes', 'string', 'max:20',
                Rule::unique('customers', 'no_telp')->ignore($customerId),
            ],
            'alamat'        => ['nullable', 'string'],
            'tipe_customer' => ['sometimes', 'in:reguler,grosir'],
            'status'        => ['sometimes', 'in:aktif,nonaktif'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_telp.unique'   => 'Nomor telepon sudah digunakan customer lain',
            'tipe_customer.in' => 'Tipe customer harus: reguler atau grosir',
            'status.in'        => 'Status harus: aktif atau nonaktif',
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
