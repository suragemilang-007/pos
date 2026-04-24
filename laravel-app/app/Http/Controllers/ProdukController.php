<?php

namespace App\Http\Controllers;

use App\Services\Inventory\ProdukSatuanService;
use App\Services\Inventory\ProdukService;
use Illuminate\Http\JsonResponse;

class ProdukController extends Controller
{
    public function __construct(
        private readonly ProdukService        $produkService,
        private readonly ProdukSatuanService  $produkSatuanService
    ) {}

    /**
     * GET /api/kasir/produk/{kode}/stok — SD-07
     *
     * Kasir scan barcode → frontend kirim kode ke endpoint ini →
     * dapat daftar satuan beserta stok per satuan.
     *
     * Logika warning (stok habis / menipis) TIDAK ada di sini —
     * frontend yang tentukan berdasarkan stok_dalam_satuan vs stok_minimum.
     * Backend hanya kirim data mentah; presentasi urusan frontend.
     */
    public function cekStok(string $kode): JsonResponse
    {
        $produk = $this->produkService->getByKode($kode);

        if (! $produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan atau tidak aktif',
            ], 404);
        }

        $satuanTersedia = $this->produkSatuanService->buildSatuanTersedia($produk);

        return response()->json([
            'data' => [
                'id'              => $produk->id,
                'kode_produk'     => $produk->kode_produk,
                'nama_produk'     => $produk->nama_produk,
                'satuan_dasar'    => $produk->satuan_dasar,
                'qty_stok'        => $produk->qty_stok,
                'stok_minimum'    => $produk->stok_minimum,
                'satuan_tersedia' => $satuanTersedia,
            ],
        ], 200);
    }
}
