<?php

namespace App\Http\Requests\Kategori;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_kategori' => ['required', 'string', 'max:100', 'unique:kategori_produk,nama_kategori'],
            'deskripsi'     => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi',
            'nama_kategori.max'      => 'Nama kategori maksimal 100 karakter',
            'nama_kategori.unique'   => 'Nama kategori sudah ada',
        ];
    }

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
