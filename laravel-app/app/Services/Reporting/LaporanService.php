<?php

namespace App\Services\Reporting;

use App\Models\Produk;
use Illuminate\Support\Facades\DB;

/**
 * LaporanService — query agregasi untuk semua jenis laporan.
 *
 * Semua laporan bersifat read-only dan tidak melalui Kafka (sesuai CLAUDE.md).
 * Query menggunakan DB::select/table untuk agregasi kompleks yang tidak
 * efisien jika dilakukan via Eloquent collection.
 */
class LaporanService
{
    // ─────────────────────────────────────────────────────────────────────────
    // LAPORAN PENJUALAN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Laporan penjualan lengkap dalam rentang tanggal.
     *
     * Menggunakan raw DB queries karena melibatkan agregasi multi-dimensi
     * (per hari, per produk, per metode bayar) yang lebih efisien tanpa ORM overhead.
     * Filter status='COMPLETED' — PENDING/FAILED tidak dihitung sebagai penjualan.
     *
     * nama_satuan dan isi_satuan diambil dari detail_transaksi (snapshot),
     * bukan dari produk_satuan — sesuai aturan 3.4 CLAUDE.md.
     */
    public function generateLaporanPenjualan(string $dari, string $sampai): array
    {
        // ── Ringkasan ─────────────────────────────────────────────────────────
        $ringkasan = DB::table('transaksi')
            ->selectRaw('
                COUNT(DISTINCT transaksi.id)          AS total_transaksi,
                SUM(transaksi.total)                  AS omzet,
                SUM(dt.qty * dt.isi_satuan)           AS total_item_terjual_dasar
            ')
            ->join('detail_transaksi as dt', 'dt.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.status', 'COMPLETED')
            ->whereBetween('transaksi.tanggal_transaksi', [
                $dari . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->first();

        // ── Per hari ──────────────────────────────────────────────────────────
        $perHari = DB::table('transaksi')
            ->selectRaw('
                DATE(tanggal_transaksi)   AS tanggal,
                COUNT(*)                  AS jumlah_transaksi,
                SUM(total)                AS total_omzet
            ')
            ->where('status', 'COMPLETED')
            ->whereBetween('tanggal_transaksi', [
                $dari . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->groupBy(DB::raw('DATE(tanggal_transaksi)'))
            ->orderBy('tanggal')
            ->get();

        // ── Produk terlaris (top 10 dalam satuan dasar) ───────────────────────
        $produkTerlaris = DB::table('detail_transaksi as dt')
            ->selectRaw('
                dt.produk_id,
                p.nama_produk,
                p.satuan_dasar,
                SUM(dt.qty * dt.isi_satuan)  AS qty_terjual_dasar,
                SUM(dt.subtotal)             AS total_pendapatan
            ')
            ->join('transaksi as t', 't.id', '=', 'dt.transaksi_id')
            ->join('produk as p', 'p.id', '=', 'dt.produk_id')
            ->where('t.status', 'COMPLETED')
            ->whereBetween('t.tanggal_transaksi', [
                $dari . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->groupBy('dt.produk_id', 'p.nama_produk', 'p.satuan_dasar')
            ->orderByDesc('qty_terjual_dasar')
            ->limit(10)
            ->get();

        // ── Breakdown metode bayar ────────────────────────────────────────────
        $metodeBayar = DB::table('transaksi')
            ->selectRaw('metode_bayar, COUNT(*) AS jumlah, SUM(total) AS total')
            ->where('status', 'COMPLETED')
            ->whereBetween('tanggal_transaksi', [
                $dari . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->groupBy('metode_bayar')
            ->get()
            ->keyBy('metode_bayar');

        return [
            'periode'          => ['dari' => $dari, 'sampai' => $sampai],
            'ringkasan'        => [
                'total_transaksi'          => (int) ($ringkasan->total_transaksi ?? 0),
                'total_item_terjual_dasar' => (int) ($ringkasan->total_item_terjual_dasar ?? 0),
                'omzet'                    => (float) ($ringkasan->omzet ?? 0),
            ],
            'per_hari'         => $perHari->map(fn ($r) => [
                'tanggal'           => $r->tanggal,
                'jumlah_transaksi'  => (int) $r->jumlah_transaksi,
                'total_omzet'       => (float) $r->total_omzet,
            ])->values(),
            'produk_terlaris'  => $produkTerlaris->map(fn ($r) => [
                'produk_id'        => $r->produk_id,
                'nama_produk'      => $r->nama_produk,
                'satuan_dasar'     => $r->satuan_dasar,
                'qty_terjual'      => (int) $r->qty_terjual_dasar,
                'total_pendapatan' => (float) $r->total_pendapatan,
            ])->values(),
            'metode_bayar'     => [
                'tunai'    => (float) ($metodeBayar->get('tunai')->total ?? 0),
                'transfer' => (float) ($metodeBayar->get('transfer')->total ?? 0),
                'qris'     => (float) ($metodeBayar->get('qris')->total ?? 0),
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LAPORAN STOK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Laporan stok per produk, opsional filter per kategori.
     *
     * nilai_stok = qty_stok × harga_beli_dasar — nilai aset stok dalam modal beli.
     * pergerakan_30hari = SUM qty_terjual dalam 30 hari terakhir (dalam satuan_dasar).
     */
    public function generateLaporanStok(?int $kategoriId = null): array
    {
        $query = Produk::with(['satuanList:id,produk_id,nama_satuan,isi,harga_jual,is_default',
                               'kategori:id,nama_kategori'])
            ->where('status', 'aktif');

        if ($kategoriId) {
            $query->where('kategori_id', $kategoriId);
        }

        $produkList = $query->orderBy('nama_produk')->get();

        // Ambil pergerakan 30 hari untuk semua produk sekaligus (hindari N+1)
        $produkIds      = $produkList->pluck('id');
        $batas30Hari    = now()->subDays(30)->format('Y-m-d 00:00:00');

        $pergerakan = DB::table('detail_transaksi as dt')
            ->selectRaw('dt.produk_id, SUM(dt.qty * dt.isi_satuan) AS terjual_dasar')
            ->join('transaksi as t', 't.id', '=', 'dt.transaksi_id')
            ->where('t.status', 'COMPLETED')
            ->where('t.tanggal_transaksi', '>=', $batas30Hari)
            ->whereIn('dt.produk_id', $produkIds)
            ->groupBy('dt.produk_id')
            ->get()
            ->keyBy('produk_id');

        $data = $produkList->map(function (Produk $p) use ($pergerakan) {
            $nilaiStok     = $p->qty_stok * (float) $p->harga_beli_dasar;
            $terjual30Hari = (int) ($pergerakan->get($p->id)?->terjual_dasar ?? 0);

            return [
                'id'            => $p->id,
                'kode_produk'   => $p->kode_produk,
                'nama_produk'   => $p->nama_produk,
                'kategori'      => $p->kategori?->nama_kategori,
                'satuan_dasar'  => $p->satuan_dasar,
                'qty_stok'      => $p->qty_stok,
                'stok_minimum'  => $p->stok_minimum,
                'is_rendah'     => $p->qty_stok <= $p->stok_minimum,
                'nilai_stok'    => round($nilaiStok, 2),
                'pergerakan_30hari' => $terjual30Hari,
                'satuan_tersedia'   => $p->satuanList->map(fn ($s) => [
                    'nama_satuan'      => $s->nama_satuan,
                    'stok_dalam_satuan'=> (int) floor($p->qty_stok / $s->isi),
                ])->values(),
            ];
        });

        return [
            'total_produk'     => $data->count(),
            'total_nilai_stok' => round($data->sum('nilai_stok'), 2),
            'stok_rendah'      => $data->where('is_rendah', true)->count(),
            'produk'           => $data->values(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LAPORAN PEMBELIAN
    // ─────────────────────────────────────────────────────────────────────────

    public function generateLaporanPembelian(string $dari, string $sampai): array
    {
        $ringkasan = DB::table('pembelian')
            ->selectRaw('
                COUNT(*) AS total_po,
                SUM(total) AS total_belanja,
                SUM(CASE WHEN status="COMPLETED" THEN 1 ELSE 0 END) AS po_selesai,
                SUM(CASE WHEN status="CANCELLED" THEN 1 ELSE 0 END) AS po_batal
            ')
            ->whereBetween('tanggal_pembelian', [$dari, $sampai])
            ->first();

        $perSupplier = DB::table('pembelian as po')
            ->selectRaw('
                po.supplier_id,
                s.nama_supplier,
                COUNT(po.id) AS jumlah_po,
                SUM(po.total) AS total_belanja
            ')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->where('po.status', 'COMPLETED')
            ->whereBetween('po.tanggal_pembelian', [$dari, $sampai])
            ->groupBy('po.supplier_id', 's.nama_supplier')
            ->orderByDesc('total_belanja')
            ->get();

        $daftarPO = DB::table('pembelian as po')
            ->select('po.id', 'po.no_pembelian', 'po.tanggal_pembelian',
                     'po.total', 'po.status', 's.nama_supplier')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->whereBetween('po.tanggal_pembelian', [$dari, $sampai])
            ->orderByDesc('po.tanggal_pembelian')
            ->get();

        return [
            'periode'      => ['dari' => $dari, 'sampai' => $sampai],
            'ringkasan'    => [
                'total_po'       => (int) ($ringkasan->total_po ?? 0),
                'total_belanja'  => (float) ($ringkasan->total_belanja ?? 0),
                'po_selesai'     => (int) ($ringkasan->po_selesai ?? 0),
                'po_batal'       => (int) ($ringkasan->po_batal ?? 0),
            ],
            'per_supplier' => $perSupplier->map(fn ($r) => [
                'supplier_id'   => $r->supplier_id,
                'nama_supplier' => $r->nama_supplier,
                'jumlah_po'     => (int) $r->jumlah_po,
                'total_belanja' => (float) $r->total_belanja,
            ])->values(),
            'daftar_po'    => $daftarPO->values(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LAPORAN VOID TRANSAKSI
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Laporan void transaksi dalam rentang tanggal.
     *
     * Filter berdasarkan void_transaksi.created_at (kapan void diajukan),
     * bukan transaksi.tanggal_transaksi, agar laporan mencerminkan aktivitas void
     * yang terjadi pada periode tersebut — bukan transaksi asal yang mungkin lebih lama.
     *
     * total_nilai_void dihitung hanya dari status='COMPLETED' karena:
     * - PENDING = belum selesai diproses Consumer
     * - REJECTED = ditolak, stok tidak dikembalikan, nilai tidak berubah
     */
    public function generateLaporanVoid(string $dari, string $sampai): array
    {
        $daftar = DB::table('void_transaksi as vt')
            ->selectRaw('
                t.no_transaksi,
                vt.created_at   AS waktu_void,
                u.nama          AS nama_kasir,
                vt.alasan,
                vt.keterangan,
                t.total         AS total_nilai_void,
                vt.status
            ')
            ->join('transaksi as t', 't.id', '=', 'vt.transaksi_id')
            ->join('users as u',     'u.id', '=', 'vt.user_id')
            ->whereBetween('vt.created_at', [
                $dari   . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->orderByDesc('vt.created_at')
            ->get();

        // Summary hanya dari void yang COMPLETED — void PENDING/REJECTED belum berdampak ke stok
        $ringkasan = DB::table('void_transaksi as vt')
            ->selectRaw('
                COUNT(*)                                                  AS total_void,
                SUM(CASE WHEN vt.status = "COMPLETED" THEN t.total
                         ELSE 0 END)                                      AS total_nilai_completed
            ')
            ->join('transaksi as t', 't.id', '=', 'vt.transaksi_id')
            ->whereBetween('vt.created_at', [
                $dari   . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->first();

        // Breakdown per alasan — berguna untuk analisis pola kesalahan kasir
        $perAlasan = DB::table('void_transaksi as vt')
            ->selectRaw('vt.alasan, COUNT(*) AS jumlah')
            ->whereBetween('vt.created_at', [
                $dari   . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->groupBy('vt.alasan')
            ->orderByDesc('jumlah')
            ->get();

        return [
            'periode'    => ['dari' => $dari, 'sampai' => $sampai],
            'ringkasan'  => [
                'total_void'             => (int)   ($ringkasan->total_void ?? 0),
                'total_nilai_completed'  => (float) ($ringkasan->total_nilai_completed ?? 0),
            ],
            'per_alasan' => $perAlasan->map(fn ($r) => [
                'alasan'  => $r->alasan,
                'jumlah'  => (int) $r->jumlah,
            ])->values(),
            'daftar'     => $daftar->map(fn ($r) => [
                'no_transaksi'    => $r->no_transaksi,
                'waktu_void'      => $r->waktu_void,
                'nama_kasir'      => $r->nama_kasir,
                'alasan'          => $r->alasan,
                'keterangan'      => $r->keterangan,
                'total_nilai_void'=> (float) $r->total_nilai_void,
                'status'          => $r->status,
            ])->values(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LAPORAN PROMO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Laporan efektivitas promo produk dalam rentang tanggal.
     *
     * Hanya transaksi COMPLETED yang dihitung — PENDING/FAILED/VOIDED tidak valid
     * karena stok mungkin belum/sudah dikembalikan.
     *
     * jumlah_diterapkan = COUNT(DISTINCT transaksi_id) WHERE promo_id = ?
     *   → berapa transaksi yang menggunakan promo ini, bukan berapa baris detail.
     *
     * total_item_bonus = SUM(qty) WHERE is_bonus = 1
     *   → total unit barang yang diberikan gratis (dalam satuan masing-masing item).
     *
     * total_nilai_diskon = SUM(diskon_item)
     *   → total potongan harga yang diberikan kepada pembeli via promo ini.
     */
    public function generateLaporanPromo(string $dari, string $sampai): array
    {
        $perPromo = DB::table('detail_transaksi as dt')
            ->selectRaw('
                pp.id                                          AS promo_id,
                pp.nama_promo,
                pp.tipe_promo,
                COUNT(DISTINCT dt.transaksi_id)               AS jumlah_diterapkan,
                SUM(CASE WHEN dt.is_bonus = 1
                         THEN dt.qty ELSE 0 END)              AS total_item_bonus,
                SUM(dt.diskon_item)                           AS total_nilai_diskon
            ')
            ->join('transaksi as t',    't.id',   '=', 'dt.transaksi_id')
            ->join('produk_promo as pp', 'pp.id',  '=', 'dt.promo_id')
            ->whereNotNull('dt.promo_id')
            ->where('t.status', 'COMPLETED')
            ->whereBetween('t.tanggal_transaksi', [
                $dari   . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->groupBy('pp.id', 'pp.nama_promo', 'pp.tipe_promo')
            ->orderByDesc('jumlah_diterapkan')
            ->get();

        // Ringkasan: berapa total promo aktif yang dipakai dan total nilai yang diberikan
        $ringkasan = DB::table('detail_transaksi as dt')
            ->selectRaw('
                COUNT(DISTINCT dt.promo_id)    AS total_promo_dipakai,
                COUNT(DISTINCT dt.transaksi_id) AS total_transaksi_dengan_promo,
                SUM(dt.diskon_item)            AS total_nilai_diskon_keseluruhan
            ')
            ->join('transaksi as t', 't.id', '=', 'dt.transaksi_id')
            ->whereNotNull('dt.promo_id')
            ->where('t.status', 'COMPLETED')
            ->whereBetween('t.tanggal_transaksi', [
                $dari   . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->first();

        return [
            'periode'    => ['dari' => $dari, 'sampai' => $sampai],
            'ringkasan'  => [
                'total_promo_dipakai'             => (int)   ($ringkasan->total_promo_dipakai ?? 0),
                'total_transaksi_dengan_promo'    => (int)   ($ringkasan->total_transaksi_dengan_promo ?? 0),
                'total_nilai_diskon_keseluruhan'  => (float) ($ringkasan->total_nilai_diskon_keseluruhan ?? 0),
            ],
            'per_promo'  => $perPromo->map(fn ($r) => [
                'promo_id'          => $r->promo_id,
                'nama_promo'        => $r->nama_promo,
                'tipe'              => $r->tipe_promo,
                'jumlah_diterapkan' => (int)   $r->jumlah_diterapkan,
                'total_item_bonus'  => (int)   $r->total_item_bonus,
                'total_nilai_diskon'=> (float) $r->total_nilai_diskon,
            ])->values(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // METRIK BISNIS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Metrik bisnis agregat untuk dashboard owner.
     *
     * profit_kotor = pendapatan - modal_terjual
     * modal_terjual = SUM(qty × isi_satuan × harga_beli_dasar)
     *   — menggunakan harga_beli_dasar dari tabel produk (harga beli saat ini),
     *   bukan harga historis, karena tidak ada snapshot harga beli di detail_transaksi.
     *   Untuk laporan akademis, ini adalah pendekatan yang umum digunakan.
     *
     * perputaran_stok = qty_terjual_dasar / avg_qty_stok
     *   — inventory turnover dalam satuan_dasar, menunjukkan seberapa cepat
     *   stok berputar dalam periode laporan.
     */
    public function getMetrikBisnis(string $dari, string $sampai): array
    {
        // Omzet dari transaksi COMPLETED
        $omzet = (float) DB::table('transaksi')
            ->where('status', 'COMPLETED')
            ->whereBetween('tanggal_transaksi', [
                $dari . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->sum('total');

        // Modal terjual = SUM(qty_terjual_dasar × harga_beli_dasar produk)
        $modalData = DB::table('detail_transaksi as dt')
            ->selectRaw('SUM(dt.qty * dt.isi_satuan * p.harga_beli_dasar) AS modal_terjual,
                         SUM(dt.qty * dt.isi_satuan) AS qty_terjual_dasar')
            ->join('transaksi as t', 't.id', '=', 'dt.transaksi_id')
            ->join('produk as p', 'p.id', '=', 'dt.produk_id')
            ->where('t.status', 'COMPLETED')
            ->whereBetween('t.tanggal_transaksi', [
                $dari . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->first();

        $modalTerjual      = (float) ($modalData->modal_terjual ?? 0);
        $qtyTerjualDasar   = (int) ($modalData->qty_terjual_dasar ?? 0);
        $profitKotor       = $omzet - $modalTerjual;

        // Rata-rata stok saat ini (simplified — gunakan stok aktual sebagai denominator)
        $avgStok = (float) DB::table('produk')->where('status', 'aktif')->avg('qty_stok') ?? 0;
        $perputaranStok = $avgStok > 0
            ? round($qtyTerjualDasar / $avgStok, 2)
            : 0;

        return [
            'omzet'            => round($omzet, 2),
            'profit_kotor'     => round($profitKotor, 2),
            'margin_persen'    => $omzet > 0 ? round(($profitKotor / $omzet) * 100, 1) : 0,
            'perputaran_stok'  => $perputaranStok,
            'modal_terjual'    => round($modalTerjual, 2),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EXPORT HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Export laporan penjualan ke CSV (compatible dengan Excel).
     *
     * Tidak menggunakan package eksternal (misal maatwebsite/excel) agar tidak
     * menambah dependency. CSV cukup untuk kebutuhan skripsi dan dapat dibuka
     * langsung di Microsoft Excel / Google Sheets.
     */
    public function exportCsv(array $laporan, string $jenis): string
    {
        $rows = [];

        if ($jenis === 'penjualan') {
            $rows[] = ['Tanggal', 'Jumlah Transaksi', 'Total Omzet'];
            foreach ($laporan['per_hari'] as $hari) {
                $rows[] = [$hari['tanggal'], $hari['jumlah_transaksi'], $hari['total_omzet']];
            }
        } elseif ($jenis === 'stok') {
            $rows[] = ['Kode', 'Nama Produk', 'Kategori', 'Satuan Dasar', 'Qty Stok',
                       'Stok Minimum', 'Nilai Stok', 'Terjual 30 Hari', 'Status'];
            foreach ($laporan['produk'] as $p) {
                $rows[] = [
                    $p['kode_produk'], $p['nama_produk'], $p['kategori'] ?? '-',
                    $p['satuan_dasar'], $p['qty_stok'], $p['stok_minimum'],
                    $p['nilai_stok'], $p['pergerakan_30hari'],
                    $p['is_rendah'] ? 'RENDAH' : 'NORMAL',
                ];
            }
        } elseif ($jenis === 'pembelian') {
            $rows[] = ['No PO', 'Tanggal', 'Supplier', 'Total', 'Status'];
            foreach ($laporan['daftar_po'] as $po) {
                $rows[] = [$po->no_pembelian, $po->tanggal_pembelian,
                           $po->nama_supplier, $po->total, $po->status];
            }
        } elseif ($jenis === 'void') {
            $rows[] = ['No Transaksi', 'Waktu Void', 'Kasir', 'Alasan', 'Nilai (Rp)', 'Status'];
            foreach ($laporan['daftar'] as $v) {
                $rows[] = [
                    $v['no_transaksi'], $v['waktu_void'], $v['nama_kasir'],
                    $v['alasan'], $v['total_nilai_void'], $v['status'],
                ];
            }
        } elseif ($jenis === 'promo') {
            $rows[] = ['Nama Promo', 'Tipe', 'Diterapkan (Transaksi)', 'Item Bonus', 'Total Diskon (Rp)'];
            foreach ($laporan['per_promo'] as $p) {
                $rows[] = [
                    $p['nama_promo'], $p['tipe'], $p['jumlah_diterapkan'],
                    $p['total_item_bonus'], $p['total_nilai_diskon'],
                ];
            }
        }

        $output = '';
        foreach ($rows as $row) {
            $output .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\r\n";
        }

        return $output;
    }

    /**
     * Export laporan ke HTML siap print-to-PDF.
     *
     * Tanpa package dompdf/mpdf — browser yang handle "Print as PDF".
     * Cukup untuk keperluan skripsi; production dapat diganti dengan dompdf.
     */
    public function exportHtml(array $laporan, string $jenis, string $dari, string $sampai): string
    {
        $title = match ($jenis) {
            'penjualan' => 'Laporan Penjualan',
            'stok'      => 'Laporan Stok',
            'pembelian' => 'Laporan Pembelian',
            default     => 'Laporan',
        };

        $tableHtml = '';
        if ($jenis === 'penjualan') {
            $tableHtml = "<tr><th>Tanggal</th><th>Transaksi</th><th>Omzet</th></tr>\n";
            foreach ($laporan['per_hari'] as $r) {
                $tableHtml .= "<tr><td>{$r['tanggal']}</td><td>{$r['jumlah_transaksi']}</td>"
                    . "<td>Rp " . number_format($r['total_omzet'], 0, ',', '.') . "</td></tr>\n";
            }
        } elseif ($jenis === 'stok') {
            $tableHtml = "<tr><th>Kode</th><th>Produk</th><th>Qty Stok</th><th>Nilai</th><th>Status</th></tr>\n";
            foreach ($laporan['produk'] as $p) {
                $status = $p['is_rendah'] ? '<span style="color:red">RENDAH</span>' : 'Normal';
                $tableHtml .= "<tr><td>{$p['kode_produk']}</td><td>{$p['nama_produk']}</td>"
                    . "<td>{$p['qty_stok']}</td>"
                    . "<td>Rp " . number_format($p['nilai_stok'], 0, ',', '.') . "</td>"
                    . "<td>{$status}</td></tr>\n";
            }
        }

        return <<<HTML
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
<title>{$title}</title>
<style>
  body{font-family:Arial,sans-serif;font-size:12px}
  h2{text-align:center}table{width:100%;border-collapse:collapse}
  th,td{border:1px solid #ccc;padding:4px 8px}th{background:#f0f0f0}
  @media print{button{display:none}}
</style></head><body>
<button onclick="window.print()">Cetak / Simpan PDF</button>
<h2>TOKO MELATI</h2>
<h3 style="text-align:center">{$title} | {$dari} s/d {$sampai}</h3>
<table>{$tableHtml}</table>
</body></html>
HTML;
    }
}
