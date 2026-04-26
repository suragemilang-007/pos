<?php

namespace App\Http\Requests\Kategori;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Ambil {id} dari route agar unique check mengabaikan row yang sedang diedit
        $id = $this->route('id');

        return [
            'nama_kategori' => [
                'required', 'string', 'max:100',
                Rule::unique('kategori_produk', 'nama_kategori')->ignore($id),
            ],
            'deskripsi' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi',
            'nama_kategori.max'      => 'Nama kategori maksimal 100 karakter',
            'nama_kategori.unique'   => 'Nama kategori sudah digunakan oleh kategori lain',
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
