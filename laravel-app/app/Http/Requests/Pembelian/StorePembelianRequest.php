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

            // ── Hutang dagang v4 ──────────────────────────────────────────────
            'tipe_pembayaran'     => ['required', 'string', 'in:cash,kredit'],
            // jumlah_dp: wajib jika kredit, opsional jika cash (nilai default 0)
            'jumlah_dp'           => ['required_if:tipe_pembayaran,kredit', 'numeric', 'min:0'],
            // tanggal_jatuh_tempo: wajib jika kredit, harus setelah hari ini
            'tanggal_jatuh_tempo' => [
                'required_if:tipe_pembayaran,kredit',
                'nullable',
                'date',
                'after:today',
            ],
            'catatan_pembayaran'  => ['nullable', 'string'],

            // ── Items ─────────────────────────────────────────────────────────
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
            'supplier_id.exists'              => 'Supplier tidak ditemukan',
            'tanggal_pembelian.date'          => 'Format tanggal tidak valid (gunakan YYYY-MM-DD)',
            'tipe_pembayaran.required'        => 'Tipe pembayaran wajib dipilih (cash atau kredit)',
            'tipe_pembayaran.in'              => 'Tipe pembayaran harus: cash atau kredit',
            'jumlah_dp.required_if'           => 'Jumlah DP wajib diisi untuk pembelian kredit',
            'jumlah_dp.min'                   => 'Jumlah DP tidak boleh negatif',
            'tanggal_jatuh_tempo.required_if' => 'Tanggal jatuh tempo wajib diisi untuk pembelian kredit',
            'tanggal_jatuh_tempo.after'       => 'Tanggal jatuh tempo harus setelah hari ini',
            'items.min'                       => 'Minimal 1 item harus diisi',
            'items.*.produk_id.exists'        => 'Produk tidak ditemukan',
            'items.*.isi_satuan.required'     => 'isi_satuan wajib ada (digunakan Consumer untuk konversi stok)',
            'items.*.qty.min'                 => 'Qty harus lebih dari 0',
            'items.*.harga_beli_satuan.min'   => 'Harga beli tidak boleh negatif',
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
