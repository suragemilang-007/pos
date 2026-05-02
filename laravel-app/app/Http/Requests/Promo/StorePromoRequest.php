<?php

namespace App\Http\Requests\Promo;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi role ditangani middleware 'role:admin_gudang,owner' di routes
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Produk & Satuan ───────────────────────────────────────────────
            'produk_id'        => ['required', 'integer', 'exists:produk,id'],
            'produk_satuan_id' => ['required', 'integer', 'exists:produk_satuan,id'],

            // ── Info Promo ────────────────────────────────────────────────────
            'nama_promo' => ['required', 'string', 'max:200'],
            'tipe_promo' => ['required', 'string', 'in:bonus_produk_sama,bonus_produk_lain,diskon_persen,diskon_nominal'],
            'min_qty'    => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:500'],

            // ── Kolom Bonus ───────────────────────────────────────────────────
            // required_if menerima multiple values: wajib jika tipe_promo = salah satu nilai berikut
            'bonus_qty'       => ['required_if:tipe_promo,bonus_produk_sama,bonus_produk_lain', 'nullable', 'integer', 'min:1'],
            'bonus_produk_id' => ['required_if:tipe_promo,bonus_produk_lain', 'nullable', 'integer', 'exists:produk,id'],
            'bonus_satuan_id' => ['required_if:tipe_promo,bonus_produk_lain', 'nullable', 'integer', 'exists:produk_satuan,id'],

            // ── Kolom Diskon ──────────────────────────────────────────────────
            'diskon_nilai' => ['required_if:tipe_promo,diskon_persen,diskon_nominal', 'nullable', 'numeric', 'min:0.01'],

            // ── Periode Berlaku ───────────────────────────────────────────────
            'is_active'      => ['nullable', 'boolean'],
            'berlaku_dari'   => ['nullable', 'date'],
            // after_or_equal merujuk ke nilai field berlaku_dari, bukan string literal
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:berlaku_dari'],
        ];
    }

    public function messages(): array
    {
        return [
            'produk_id.exists'        => 'Produk tidak ditemukan',
            'produk_satuan_id.exists'  => 'Satuan produk tidak ditemukan',
            'nama_promo.max'           => 'Nama promo maksimal 200 karakter',
            'tipe_promo.in'            => 'Tipe promo tidak valid. Pilih: bonus_produk_sama, bonus_produk_lain, diskon_persen, atau diskon_nominal',
            'min_qty.min'              => 'Minimal qty beli harus >= 1',

            'bonus_qty.required_if'       => 'bonus_qty wajib diisi untuk tipe bonus',
            'bonus_qty.min'               => 'bonus_qty harus >= 1',
            'bonus_produk_id.required_if' => 'bonus_produk_id wajib diisi untuk tipe bonus_produk_lain',
            'bonus_produk_id.exists'      => 'Produk bonus tidak ditemukan',
            'bonus_satuan_id.required_if' => 'bonus_satuan_id wajib diisi untuk tipe bonus_produk_lain',
            'bonus_satuan_id.exists'      => 'Satuan produk bonus tidak ditemukan',

            'diskon_nilai.required_if' => 'diskon_nilai wajib diisi untuk tipe diskon',
            'diskon_nilai.min'         => 'diskon_nilai harus lebih dari 0',

            'berlaku_sampai.after_or_equal' => 'berlaku_sampai tidak boleh sebelum berlaku_dari',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data promo tidak valid',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
