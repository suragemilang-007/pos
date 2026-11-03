<?php

namespace App\Http\Requests\Transaksi;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTransaksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi role sudah ditangani middleware 'role:kasir,owner' di routes
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Item ──────────────────────────────────────────────────────────
            'items'                    => ['required', 'array', 'min:1'],

            'items.*.produk_id'        => ['required', 'integer', 'exists:produk,id'],

            // produk_satuan_id: WAJIB ada untuk snapshot isi_satuan di Consumer
            // nullable karena bisa null jika kasir input manual tanpa memilih satuan
            'items.*.produk_satuan_id' => ['nullable', 'integer', 'exists:produk_satuan,id'],

            // nama_satuan dan isi_satuan: snapshot — disimpan di detail_transaksi
            // agar riwayat tidak berubah jika admin edit satuan di kemudian hari
            'items.*.nama_satuan'      => ['required', 'string', 'max:30'],
            'items.*.isi_satuan'       => ['required', 'integer', 'min:1'],

            'items.*.qty'              => ['required', 'integer', 'min:1'],
            'items.*.harga_satuan'     => ['required', 'numeric', 'min:0'],

            // ── Header transaksi ──────────────────────────────────────────────
            'customer_id'  => ['nullable', 'integer', 'exists:customers,id'],
            'metode_bayar' => ['required', 'in:tunai,transfer,qris'],

            // total, bayar, kembalian dikirim client sebagai konfirmasi — service
            // akan menghitung ulang di sisi server; nilai ini dipakai untuk INSERT bayar
            'total'        => ['required', 'numeric', 'min:0'],
            'bayar'        => ['required', 'numeric', 'min:0'],
            'kembalian'    => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'               => 'Item transaksi wajib diisi',
            'items.min'                    => 'Transaksi harus memiliki minimal 1 item',
            'items.*.produk_id.required'   => 'Produk wajib dipilih untuk setiap item',
            'items.*.produk_id.exists'     => 'Produk tidak ditemukan',
            'items.*.isi_satuan.required'  => 'isi_satuan wajib ada (digunakan untuk konversi stok)',
            'items.*.qty.min'              => 'Qty harus lebih dari 0',
            'items.*.harga_satuan.min'     => 'Harga satuan tidak boleh negatif',
            'metode_bayar.in'              => 'Metode bayar harus: tunai, transfer, atau qris',
            'bayar.min'                    => 'Nominal bayar tidak boleh negatif',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data transaksi tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
