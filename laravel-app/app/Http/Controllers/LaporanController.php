<?php

namespace App\Http\Controllers;

use App\Services\Reporting\LaporanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LaporanController extends Controller
{
    // Jenis laporan yang diizinkan — validasi di controller agar pesan error jelas
    private const JENIS_VALID = ['penjualan', 'stok', 'pembelian', 'void', 'promo', 'hutang', 'penyesuaian_stok'];

    public function __construct(
        private readonly LaporanService $laporanService
    ) {}

    /**
     * GET /api/owner/laporan/{jenis}?dari=&sampai=&kategori_id= — SD-14
     *
     * Jenis: penjualan | stok | pembelian
     * Otorisasi role 'owner' sudah ditangani middleware — controller tidak perlu cek ulang.
     */
    public function generate(Request $request, string $jenis): JsonResponse
    {
        if (! in_array($jenis, self::JENIS_VALID)) {
            return response()->json([
                'message' => "Jenis laporan tidak valid. Pilihan: " . implode(', ', self::JENIS_VALID),
            ], 400);
        }

        // Default rentang: bulan ini
        $dari    = $request->input('dari',   now()->startOfMonth()->format('Y-m-d'));
        $sampai  = $request->input('sampai', now()->format('Y-m-d'));

        $data   = $this->getData($jenis, $dari, $sampai, $request);

        // Metrik bisnis hanya relevan untuk laporan penjualan & pembelian
        // Void, promo, hutang, dan penyesuaian adalah laporan operasional
        $metrik = in_array($jenis, ['penjualan', 'pembelian'])
            ? $this->laporanService->getMetrikBisnis($dari, $sampai)
            : null;

        $response = ['data' => $data];
        if ($metrik) {
            $response['metrik'] = $metrik;
        }

        return response()->json($response, 200);
    }

    /**
     * GET /api/owner/laporan/{jenis}/export?format=excel|pdf&dari=&sampai= — SD-14
     *
     * format=excel → CSV download (bisa dibuka di Excel)
     * format=pdf   → HTML siap print-to-PDF
     *
     * Tidak pakai library eksternal (dompdf/maatwebsite) agar tidak perlu
     * composer install package baru. Cukup untuk keperluan skripsi/demo.
     */
    public function export(Request $request, string $jenis): Response|JsonResponse
    {
        if (! in_array($jenis, self::JENIS_VALID)) {
            return response()->json(['message' => 'Jenis laporan tidak valid'], 400);
        }

        $format  = $request->input('format', 'excel');
        $dari    = $request->input('dari',   now()->startOfMonth()->format('Y-m-d'));
        $sampai  = $request->input('sampai', now()->format('Y-m-d'));
        $data    = $this->getData($jenis, $dari, $sampai, $request);

        if ($format === 'pdf') {
            $html     = $this->laporanService->exportHtml($data, $jenis, $dari, $sampai);
            $filename = "laporan-{$jenis}-{$dari}-{$sampai}.html";

            return response($html, 200, [
                'Content-Type'        => 'text/html; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        // Default: CSV (excel-compatible)
        $csv      = $this->laporanService->exportCsv($data, $jenis);
        $filename = "laporan-{$jenis}-{$dari}-{$sampai}.csv";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPER
    // ─────────────────────────────────────────────────────────────────────────

    private function getData(string $jenis, string $dari, string $sampai, Request $request): array
    {
        return match ($jenis) {
            'penjualan'        => $this->laporanService->generateLaporanPenjualan($dari, $sampai),
            'stok'             => $this->laporanService->generateLaporanStok(
                                      $request->integer('kategori_id') ?: null
                                  ),
            'pembelian'        => $this->laporanService->generateLaporanPembelian($dari, $sampai),
            'void'             => $this->laporanService->generateLaporanVoid($dari, $sampai),
            'promo'            => $this->laporanService->generateLaporanPromo($dari, $sampai),
            'hutang'           => $this->laporanService->generateLaporanHutang(
                                      $request->only(['supplier_id', 'status'])
                                  ),
            'penyesuaian_stok' => $this->laporanService->generateLaporanPenyesuaianStok($dari, $sampai),
        };
    }
}
