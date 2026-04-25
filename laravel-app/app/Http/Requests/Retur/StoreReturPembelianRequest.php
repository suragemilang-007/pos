<?php

namespace App\Http\Requests\Retur;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReturPembelianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pembelian_id'              => ['required', 'integer', 'exists:pembelian,id'],
            'alasan'                    => ['required', 'string', 'max:1000'],

            'items_retur'               => ['required', 'array', 'min:1'],
            'items_retur.*.produk_id'   => ['required', 'integer', 'exists:produk,id'],
            'items_retur.*.nama_satuan' => ['required', 'string', 'max:30'],
            // isi_satuan wajib ada — Consumer pakai untuk: qty_stok -= qty_retur × isi_satuan
            'items_retur.*.isi_satuan'  => ['required', 'integer', 'min:1'],
            'items_retur.*.qty_retur'   => ['required', 'integer', 'min:1'],
            'items_retur.*.harga_satuan'=> ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'pembelian_id.exists'             => 'Pembelian tidak ditemukan',
            'items_retur.min'                 => 'Minimal 1 item retur harus diisi',
            'items_retur.*.isi_satuan.required'=> 'isi_satuan wajib ada (digunakan Consumer untuk konversi stok)',
            'items_retur.*.qty_retur.min'     => 'Qty retur harus lebih dari 0',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data retur pembelian tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
