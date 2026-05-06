<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi role ditangani middleware 'role:admin_gudang,owner' di routes
        return true;
    }

    public function rules(): array
    {
        return [
            // Jumlah minimal 0.01 — validasi tambahan di Service (tidak boleh melebihi sisa_hutang)
            'jumlah'       => ['required', 'numeric', 'min:0.01'],
            'metode'       => ['required', 'string', 'in:cash,transfer,cek,giro'],
            'tanggal_bayar'=> ['required', 'date'],
            'catatan'      => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'jumlah.required'  => 'Jumlah pembayaran wajib diisi',
            'jumlah.numeric'   => 'Jumlah harus berupa angka',
            'jumlah.min'       => 'Jumlah pembayaran minimal Rp 0,01',
            'metode.required'  => 'Metode pembayaran wajib dipilih',
            'metode.in'        => 'Metode tidak valid. Pilih: cash, transfer, cek, atau giro',
            'tanggal_bayar.required' => 'Tanggal bayar wajib diisi',
            'tanggal_bayar.date'     => 'Format tanggal tidak valid',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data pembayaran tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
