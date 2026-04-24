<?php

namespace App\Http\Requests\Produk;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateProdukSatuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $produkId  = $this->route('produk');
        $satuanId  = $this->route('satuan');

        return [
            'nama_satuan' => [
                'sometimes', 'string', 'max:30',
                Rule::unique('produk_satuan', 'nama_satuan')
                    ->where('produk_id', $produkId)
                    ->ignore($satuanId),
            ],
            'isi'         => ['sometimes', 'integer', 'min:1'],
            'harga_beli'  => ['sometimes', 'numeric', 'min:0'],
            'harga_jual'  => ['sometimes', 'numeric', 'min:0'],
            'is_default'  => ['sometimes', 'boolean'],
            'barcode'     => [
                'nullable', 'string', 'max:50',
                Rule::unique('produk_satuan', 'barcode')->ignore($satuanId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_satuan.unique' => 'Nama satuan sudah ada untuk produk ini',
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
