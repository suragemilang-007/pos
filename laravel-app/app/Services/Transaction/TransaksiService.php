<?php

namespace App\Services\Transaction;

use App\Models\DetailTransaksi;
use App\Models\Transaksi;
use App\Services\Kafka\KafkaProducerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransaksiService
{
    public function __construct(
        private readonly KafkaProducerService $kafkaProducer
    ) {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Buat transaksi baru, simpan ke DB sebagai PENDING, lalu publish ke Kafka.
     *
     * Parameter `$bayar` dan `$metodeBayar` ditambahkan di luar CLASS_DIAGRAM
     * karena dibutuhkan untuk INSERT ke tabel transaksi. CLASS_DIAGRAM mendefinisikan
     * signature minimal — implementasi boleh menambah parameter yang dibutuhkan.
     *
     * @param  array   $items       Item transaksi dengan snapshot harga dan satuan
     * @param  int|null $customerId  Nullable — transaksi walk-in tidak butuh customer
     * @param  int     $userId      ID kasir yang memproses
     * @param  float   $bayar       Uang diterima dari customer
     * @param  string  $metodeBayar tunai | transfer | qris
     * @return Transaksi             Status masih PENDING — Consumer yang update ke COMPLETED/FAILED
     */
    public function create(
        array $items,
        ?int $customerId,
        int $userId,
        float $bayar,
        string $metodeBayar
    ): Transaksi {
        // ── a. Hitung nilai keuangan dari items ───────────────────────────────
        $subtotal = (float) collect($items)->sum(
            fn($item) => $item['harga_satuan'] * $item['qty']
        );
        $diskon = (float) collect($items)->sum(
            fn($item) => $item['diskon_item'] ?? 0
        );
        $total = $this->calculateTotal($items);
        $kembalian = $bayar - $total;

        // ── b. Generate nomor dokumen transaksi ───────────────────────────────
        $noTransaksi = $this->generateNoTransaksi();

        // ── c & d. INSERT transaksi + detail dalam satu DB transaction ────────
        /**
         * Mengapa Kafka publish di LUAR DB::transaction?
         *
         * Jika publish dilakukan di DALAM DB::transaction dan kemudian terjadi
         * exception (apapun alasannya) → DB rollback.
         * Akibatnya: Kafka sudah menerima event, tapi baris transaksi tidak ada di DB.
         * Ketika Consumer memproses event, ia akan mencari `transaksiId` yang tidak ada
         * di tabel transaksi → error, deadlock consumer, atau data corrupt.
         *
         * Dengan publish SETELAH commit, data transaksi dijamin sudah ada di DB
         * saat Consumer memproses event dari Kafka.
         * Trade-off: jika publish gagal SETELAH commit, transaksi tetap PENDING selamanya.
         * Ini ditangani di sistem monitoring terpisah (cek transaksi PENDING > threshold waktu).
         */
        $transaksi = DB::transaction(function () use ($items, $customerId, $userId, $bayar, $metodeBayar, $subtotal, $diskon, $total, $kembalian, $noTransaksi) {
            $trx = Transaksi::create([
                'user_id' => $userId,
                'customer_id' => $customerId,
                'no_transaksi' => $noTransaksi,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total' => $total,
                'bayar' => $bayar,
                'kembalian' => $kembalian,
                'metode_bayar' => $metodeBayar,
                'status' => 'PENDING',
                // tanggal_transaksi default CURRENT_TIMESTAMP di DB
            ]);

            // d. INSERT detail — nama_satuan, isi_satuan, harga_satuan adalah SNAPSHOT
            // Snapshot disimpan agar riwayat transaksi tidak berubah jika admin
            // mengedit harga atau satuan produk di kemudian hari
            foreach ($items as $item) {
                DetailTransaksi::create([
                    'transaksi_id' => $trx->id,
                    'produk_id' => $item['produk_id'],
                    'produk_satuan_id' => $item['produk_satuan_id'],
                    'nama_satuan' => $item['nama_satuan'],
                    'isi_satuan' => $item['isi_satuan'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'diskon_item' => $item['diskon_item'] ?? 0,
                    // subtotal per baris dihitung saat INSERT — tidak ada kolom kalkulasi di detail
                    'subtotal' => $item['harga_satuan'] * $item['qty']
                        - ($item['diskon_item'] ?? 0),
                ]);
            }

            return $trx;
        });

        // ── e. Publish ke Kafka SETELAH commit ───────────────────────────────
        // loadMissing agar tidak query ulang jika relasi sudah termuat
        $transaksi->loadMissing('details');

        $this->kafkaProducer->publishTransactionCreated($transaksi);

        Log::info('[TRANSAKSI] Created', [
            'no_transaksi' => $noTransaksi,
            'user_id' => $userId,
            'total' => $total,
            'items' => count($items),
        ]);

        // ── f. Return PENDING — Consumer yang akan mengubah ke COMPLETED/FAILED
        return $transaksi;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KALKULASI
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hitung total transaksi dari daftar item.
     * Total = Σ (harga_satuan × qty − diskon_item)
     */
    public function calculateTotal(array $items): float
    {
        return (float) collect($items)->sum(
            fn($item) => ($item['harga_satuan'] * $item['qty'])
            - ($item['diskon_item'] ?? 0)
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STATUS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Update status transaksi — dipanggil oleh Kafka Consumer setelah proses stok.
     * Tidak dipanggil dari Controller karena update stok dan status harus atomik di Consumer.
     */
    public function updateStatus(int $id, string $status): bool
    {
        return (bool) Transaksi::where('id', $id)->update(['status' => $status]);
    }

    /**
     * Cek apakah transaksi bisa diretur.
     * Hanya transaksi COMPLETED yang bisa diretur — PENDING/FAILED tidak boleh.
     */
    public function canReturn(int $id): bool
    {
        return Transaksi::where('id', $id)
            ->where('status', 'COMPLETED')
            ->exists();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STRUK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate HTML struk siap cetak.
     *
     * nama_satuan, isi_satuan, harga_satuan diambil dari detail_transaksi (snapshot),
     * BUKAN dari produk_satuan — agar struk lama tetap akurat meski satuan/harga berubah.
     * nama_produk diambil dari produk via JOIN karena nama produk jarang berubah.
     *
     * @return string HTML string
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function generateStruk(int $id): string
    {
        $trx = Transaksi::with([
            'details.produk:id,nama_produk,kode_produk',
            'user:id,nama',
            'customer:id,nama',
        ])->findOrFail($id);

        $namaKasir = $trx->user?->nama ?? '-';
        $namaCustomer = $trx->customer?->nama ?? 'Umum';
        $tanggal = $trx->tanggal_transaksi
            ? $trx->tanggal_transaksi->format('d/m/Y H:i')
            : now()->format('d/m/Y H:i');

        // ── Baris item ────────────────────────────────────────────────────────
        $barisItem = '';
        foreach ($trx->details as $detail) {
            $namaProduk = $detail->produk?->nama_produk ?? 'Produk';
            $harga = number_format($detail->harga_satuan, 0, ',', '.');
            $subtotal = number_format($detail->subtotal, 0, ',', '.');

            $barisItem .= "
            <tr>
                <td>{$namaProduk}</td>
                <td style='text-align:center'>{$detail->qty} {$detail->nama_satuan}</td>
                <td style='text-align:right'>Rp {$harga}</td>
                <td style='text-align:right'>Rp {$subtotal}</td>
            </tr>";

            if ($detail->diskon_item > 0) {
                $diskonItem = number_format($detail->diskon_item, 0, ',', '.');
                $barisItem .= "
            <tr>
                <td colspan='3' style='font-size:0.85em;color:#666'>  Diskon item</td>
                <td style='text-align:right;color:#e53e3e'>-Rp {$diskonItem}</td>
            </tr>";
            }
        }

        $subtotalFmt = number_format($trx->subtotal, 0, ',', '.');
        $diskonFmt = number_format($trx->diskon, 0, ',', '.');
        $totalFmt = number_format($trx->total, 0, ',', '.');
        $bayarFmt = number_format($trx->bayar, 0, ',', '.');
        $kembalianFmt = number_format($trx->kembalian, 0, ',', '.');
        $metodeFmt = strtoupper($trx->metode_bayar);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Struk #{$trx->no_transaksi}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Courier New', monospace; font-size: 13px; width: 300px; }
                .center { text-align: center; }
                .bold { font-weight: bold; }
                .divider { border-top: 1px dashed #000; margin: 6px 0; }
                table { width: 100%; border-collapse: collapse; }
                td { padding: 2px 0; vertical-align: top; }
                .total-row td { font-weight: bold; font-size: 14px; border-top: 1px solid #000; }
            </style>
        </head>
        <body>
            <div class="center bold" style="font-size:16px;">TOKO MELATI</div>
            <div class="center" style="font-size:11px;">Jl. Pahlawan No. 330,Purwokerto Selatan</div>
            <div class="divider"></div>

            <table>
                <tr><td>No</td><td>: {$trx->no_transaksi}</td></tr>
                <tr><td>Tanggal</td><td>: {$tanggal}</td></tr>
                <tr><td>Kasir</td><td>: {$namaKasir}</td></tr>
                <tr><td>Customer</td><td>: {$namaCustomer}</td></tr>
            </table>

            <div class="divider"></div>

            <table>
                <thead>
                    <tr>
                        <th style="text-align:left">Produk</th>
                        <th>Qty</th>
                        <th style="text-align:right">Harga</th>
                        <th style="text-align:right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    {$barisItem}
                </tbody>
            </table>

            <div class="divider"></div>

            <table>
                <tr>
                    <td>Subtotal</td>
                    <td style="text-align:right">Rp {$subtotalFmt}</td>
                </tr>
                <tr>
                    <td>Diskon</td>
                    <td style="text-align:right;color:#e53e3e">-Rp {$diskonFmt}</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td style="text-align:right">Rp {$totalFmt}</td>
                </tr>
                <tr>
                    <td>Bayar ({$metodeFmt})</td>
                    <td style="text-align:right">Rp {$bayarFmt}</td>
                </tr>
                <tr>
                    <td>Kembalian</td>
                    <td style="text-align:right">Rp {$kembalianFmt}</td>
                </tr>
            </table>

            <div class="divider"></div>
            <div class="center" style="font-size:11px;">Terima kasih atas kunjungan Anda</div>
            <div class="center" style="font-size:10px;color:#666;">* Barang yang sudah dibeli tidak dapat ditukar *</div>
        </body>
        </html>
        HTML;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate nomor transaksi unik per hari: TRX-YYYYMMDD-XXXXX
     *
     * Mengambil no_transaksi terbesar hari ini (ORDER BY DESC LIMIT 1)
     * dan increment suffix-nya. Lebih aman dari COUNT karena tetap konsisten
     * meski ada baris yang dihapus atau di-rollback.
     *
     * Race condition kecil masih mungkin di concurrency tinggi, tapi
     * UNIQUE constraint di kolom no_transaksi akan menangkap duplikat
     * dan menyebabkan DB::transaction throw exception → retry di Controller.
     */
    private function generateNoTransaksi(): string
    {
        $today = now()->format('Ymd');
        $prefix = "TRX-{$today}-";

        $latest = Transaksi::where('no_transaksi', 'like', $prefix . '%')
            ->orderByDesc('no_transaksi')
            ->value('no_transaksi');

        $nextSeq = $latest
            ? ((int) substr($latest, -5)) + 1
            : 1;

        return $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }
}