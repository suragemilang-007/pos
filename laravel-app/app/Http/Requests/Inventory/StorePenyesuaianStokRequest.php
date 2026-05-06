<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePenyesuaianStokRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi role ditangani middleware 'role:admin_gudang,owner' di routes
        return true;
    }

    public function rules(): array
    {
        return [
            'produk_id'        => ['required', 'integer', 'exists:produk,id'],
            'tipe'             => [
                'required',
                'string',
                'in:barang_rusak,barang_hilang,barang_expired,koreksi_plus,koreksi_minus',
            ],
            // Selalu positif — arah stok ditentukan oleh tipe, bukan tanda
            'qty_satuan_dasar' => ['required', 'integer', 'min:1'],
            // Keterangan wajib minimal 5 karakter — admin harus jelaskan alasan
            'keterangan'       => ['required', 'string', 'min:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'produk_id.required'        => 'Produk wajib dipilih',
            'produk_id.exists'          => 'Produk tidak ditemukan',
            'tipe.required'             => 'Tipe penyesuaian wajib dipilih',
            'tipe.in'                   => 'Tipe tidak valid. Pilih: barang_rusak, barang_hilang, barang_expired, koreksi_plus, atau koreksi_minus',
            'qty_satuan_dasar.required' => 'Qty wajib diisi',
            'qty_satuan_dasar.min'      => 'Qty harus minimal 1 (selalu positif, arah ditentukan tipe)',
            'keterangan.required'       => 'Keterangan wajib diisi (alasan penyesuaian)',
            'keterangan.min'            => 'Keterangan minimal 5 karakter',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data penyesuaian stok tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
