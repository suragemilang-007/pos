<?php

namespace App\Http\Requests\Produk;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProdukSatuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $produkId = $this->route('produk');

        return [
            // Unique composite (produk_id, nama_satuan) — dicek via where clause tambahan
            'nama_satuan' => [
                'required', 'string', 'max:30',
                // Cegah nama satuan duplikat dalam satu produk
                \Illuminate\Validation\Rule::unique('produk_satuan', 'nama_satuan')
                    ->where('produk_id', $produkId),
            ],
            'isi'         => ['required', 'integer', 'min:1'],
            'harga_beli'  => ['required', 'numeric', 'min:0'],
            'harga_jual'  => ['required', 'numeric', 'min:0'],
            'is_default'  => ['required', 'boolean'],
            'barcode'     => ['nullable', 'string', 'max:50', 'unique:produk_satuan,barcode'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_satuan.unique' => 'Nama satuan sudah ada untuk produk ini',
            'isi.min'            => 'Isi satuan harus lebih dari 0',
            'barcode.unique'     => 'Barcode sudah digunakan oleh satuan lain',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data satuan tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
