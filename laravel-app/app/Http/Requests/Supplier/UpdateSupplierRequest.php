<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier');

        return [
            'nama_supplier'  => ['sometimes', 'string', 'max:150'],
            'kontak_person'  => ['nullable', 'string', 'max:100'],
            'no_telp'        => [
                'sometimes', 'string', 'max:20',
                Rule::unique('suppliers', 'no_telp')->ignore($supplierId),
            ],
            'alamat'         => ['nullable', 'string'],
            'status'         => ['sometimes', 'in:aktif,nonaktif'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_telp.unique' => 'Nomor telepon sudah digunakan supplier lain',
            'status.in'      => 'Status harus: aktif atau nonaktif',
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
