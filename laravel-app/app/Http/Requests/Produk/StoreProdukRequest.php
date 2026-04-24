<?php

namespace App\Http\Requests\Produk;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi role sudah di middleware
    }

    public function rules(): array
    {
        return [
            'kode_produk'      => ['required', 'string', 'max:20', 'unique:produk,kode_produk'],
            'nama_produk'      => ['required', 'string', 'max:200'],
            'kategori_id'      => ['required', 'integer', 'exists:kategori_produk,id'],
            'deskripsi'        => ['nullable', 'string'],
            'harga_beli_dasar' => ['required', 'numeric', 'min:0'],
            'qty_stok'         => ['required', 'integer', 'min:0'],
            'stok_minimum'     => ['required', 'integer', 'min:0'],
            'satuan_dasar'     => ['required', 'string', 'max:30'],

            'satuan_list'                 => ['required', 'array', 'min:1'],
            // distinct: cegah nama satuan duplikat dalam satu request
            'satuan_list.*.nama_satuan'   => ['required', 'string', 'max:30', 'distinct'],
            'satuan_list.*.isi'           => ['required', 'integer', 'min:1'],
            'satuan_list.*.harga_beli'    => ['required', 'numeric', 'min:0'],
            'satuan_list.*.harga_jual'    => ['required', 'numeric', 'min:0'],
            'satuan_list.*.is_default'    => ['required', 'boolean'],
            'satuan_list.*.barcode'       => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Custom cross-field validation yang tidak bisa diekspresikan via rules biasa.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $satuanList  = $this->input('satuan_list', []);
            $satuanDasar = $this->input('satuan_dasar');

            // 1. Tepat 1 satuan harus is_default=true
            $defaultCount = collect($satuanList)->filter(fn ($s) => ! empty($s['is_default']))->count();
            if ($defaultCount !== 1) {
                $v->errors()->add('satuan_list', 'Harus ada tepat 1 satuan dengan is_default=true');
            }

            // 2. Satuan yang nama_satuannya sama dengan satuan_dasar harus isi=1
            foreach ($satuanList as $i => $s) {
                if (isset($s['nama_satuan'], $s['isi'])
                    && $s['nama_satuan'] === $satuanDasar
                    && (int) $s['isi'] !== 1
                ) {
                    $v->errors()->add(
                        "satuan_list.{$i}.isi",
                        "Satuan '{$satuanDasar}' (sama dengan satuan_dasar) harus memiliki isi=1"
                    );
                }
            }

            // 3. Barcode tidak boleh duplikat antar item dalam satu request
            $barcodes = collect($satuanList)
                ->pluck('barcode')
                ->filter()
                ->values();

            if ($barcodes->count() !== $barcodes->unique()->count()) {
                $v->errors()->add('satuan_list', 'Barcode dalam satuan_list tidak boleh duplikat');
            }
        });
    }

    public function messages(): array
    {
        return [
            'kode_produk.unique'              => 'Kode produk sudah digunakan',
            'kategori_id.exists'              => 'Kategori tidak ditemukan',
            'satuan_list.min'                 => 'Minimal 1 satuan harus diisi',
            'satuan_list.*.nama_satuan.distinct' => 'Nama satuan tidak boleh duplikat',
            'satuan_list.*.isi.min'           => 'Isi satuan harus lebih dari 0',
            'satuan_list.*.is_default.required' => 'Field is_default wajib ada di setiap satuan',
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
