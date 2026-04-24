<?php

namespace App\Http\Requests\Pembelian;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePembelianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'       => ['required', 'integer', 'exists:suppliers,id'],
            'tanggal_pembelian' => ['required', 'date'],

            'items'                          => ['required', 'array', 'min:1'],
            'items.*.produk_id'              => ['required', 'integer', 'exists:produk,id'],
            'items.*.produk_satuan_id'       => ['nullable', 'integer', 'exists:produk_satuan,id'],
            'items.*.nama_satuan'            => ['required', 'string', 'max:30'],
            // isi_satuan wajib ada — Consumer pakai untuk: qty_stok += qty × isi_satuan
            'items.*.isi_satuan'             => ['required', 'integer', 'min:1'],
            'items.*.qty'                    => ['required', 'integer', 'min:1'],
            'items.*.harga_beli_satuan'      => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.exists'           => 'Supplier tidak ditemukan',
            'tanggal_pembelian.date'        => 'Format tanggal tidak valid (gunakan YYYY-MM-DD)',
            'items.min'                     => 'Minimal 1 item harus diisi',
            'items.*.produk_id.exists'      => 'Produk tidak ditemukan',
            'items.*.isi_satuan.required'   => 'isi_satuan wajib ada (digunakan Consumer untuk konversi stok)',
            'items.*.qty.min'               => 'Qty harus lebih dari 0',
            'items.*.harga_beli_satuan.min' => 'Harga beli tidak boleh negatif',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data pembelian tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
