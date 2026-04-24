<?php

namespace App\Http\Requests\Produk;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // ID produk dari route parameter
        $produkId = $this->route('produk');

        return [
            // sometimes: semua field opsional untuk PUT partial update
            'kode_produk'      => ['sometimes', 'string', 'max:20',
                                    Rule::unique('produk', 'kode_produk')->ignore($produkId)],
            'nama_produk'      => ['sometimes', 'string', 'max:200'],
            'kategori_id'      => ['sometimes', 'integer', 'exists:kategori_produk,id'],
            'deskripsi'        => ['nullable', 'string'],
            'harga_beli_dasar' => ['sometimes', 'numeric', 'min:0'],
            'qty_stok'         => ['sometimes', 'integer', 'min:0'],
            'stok_minimum'     => ['sometimes', 'integer', 'min:0'],
            'satuan_dasar'     => ['sometimes', 'string', 'max:30'],
            'status'           => ['sometimes', 'in:aktif,nonaktif'],
            // satuan_list TIDAK diterima di endpoint ini — pakai sub-resource /satuan
        ];
    }

    public function messages(): array
    {
        return [
            'kode_produk.unique'  => 'Kode produk sudah digunakan oleh produk lain',
            'kategori_id.exists'  => 'Kategori tidak ditemukan',
            'status.in'           => 'Status harus: aktif atau nonaktif',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data produk tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
