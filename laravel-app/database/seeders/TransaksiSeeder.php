<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        // ── Ambil referensi awal ─────────────────────────────────────────────

        // Hanya kasir yang bertugas membuat transaksi penjualan
        $kasirIds = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.nama_role', 'kasir')
            ->where('users.status', 'aktif')
            ->pluck('users.id')
            ->toArray();

        $customerIds = DB::table('customers')
            ->where('status', 'aktif')
            ->pluck('id')
            ->toArray();

        // Ambil semua produk aktif beserta satuan default-nya (is_default=1)
        // Satuan default = satuan terkecil yang dijual eceran (pcs, renteng, dll.)
        $produkRows = DB::table('produk')
            ->join('produk_satuan', function ($join) {
                $join->on('produk.id', '=', 'produk_satuan.produk_id')
                     ->where('produk_satuan.is_default', 1);
            })
            ->where('produk.status', 'aktif')
            ->where('produk.qty_stok', '>', 0)
            ->select(
                'produk.id          as produk_id',
                'produk.qty_stok    as qty_stok',
                'produk_satuan.id   as produk_satuan_id',
                'produk_satuan.nama_satuan',
                'produk_satuan.isi',
                'produk_satuan.harga_jual'
            )
            ->get();

        if ($produkRows->isEmpty()) {
            $this->command->warn('Tidak ada produk dengan stok. TransaksiSeeder dilewati.');
            return;
        }

        // Indeks produk berdasarkan produk_id untuk akses cepat
        $produkMap = [];
        foreach ($produkRows as $row) {
            $produkMap[$row->produk_id] = (array) $row;
        }

        // Lacak sisa stok di memory — agar tidak oversell lintas transaksi
        $stokSisa = array_column($produkMap, 'qty_stok', 'produk_id');

        // ── Generate 7 hari ke belakang (hari ini = hari ke-0) ──────────────

        for ($hariLalu = 6; $hariLalu >= 0; $hariLalu--) {
            $tanggal  = Carbon::today()->subDays($hariLalu);
            $dateKey  = $tanggal->format('Ymd');
            $jumlah   = rand(12, 25);

            for ($seq = 1; $seq <= $jumlah; $seq++) {
                // Produk yang masih punya stok tersedia hari ini
                $tersedia = array_filter(
                    $produkMap,
                    fn($p) => ($stokSisa[$p['produk_id']] ?? 0) > 0
                );

                if (empty($tersedia)) {
                    $this->command->warn("Stok habis total pada hari ke-{$hariLalu}, seq={$seq}. Transaksi selanjutnya dilewati.");
                    break;
                }

                $noTrx = 'TRX-' . $dateKey . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);

                // Jam transaksi acak antara 07:00 – 20:00 (jam operasional toko)
                $waktu = $tanggal->copy()
                    ->setHour(7)->setMinute(0)->setSecond(0)
                    ->addMinutes(rand(0, 780));

                // Pilih 1–5 produk berbeda untuk transaksi ini
                $jumlahItem = rand(1, min(5, count($tersedia)));
                $dipilih    = $this->ambilAcak($tersedia, $jumlahItem);

                // ── Hitung item ──────────────────────────────────────────────
                $items    = [];
                $subtotal = 0.0;

                foreach ($dipilih as $p) {
                    $stokAda = $stokSisa[$p['produk_id']] ?? 0;
                    $maxQty  = (int) floor($stokAda / max(1, $p['isi']));
                    if ($maxQty <= 0) continue;

                    // Qty wajar untuk toko grosir: 1–5 satuan eceran per item
                    $qty      = rand(1, min(5, $maxQty));
                    $qtyDasar = $qty * $p['isi']; // konversi ke satuan_dasar

                    $subtotalItem = round($p['harga_jual'] * $qty, 2);
                    $items[]      = [
                        'produk_id'        => $p['produk_id'],
                        'produk_satuan_id' => $p['produk_satuan_id'],
                        'nama_satuan'      => $p['nama_satuan'],
                        'isi_satuan'       => $p['isi'],
                        'qty'              => $qty,
                        'harga_satuan'     => $p['harga_jual'],
                        'subtotal'         => $subtotalItem,
                        'qty_dasar'        => $qtyDasar,
                    ];

                    $subtotal += $subtotalItem;

                    // Kurangi stok di memory sekarang agar transaksi berikutnya tidak oversell
                    $stokSisa[$p['produk_id']] -= $qtyDasar;
                }

                if (empty($items)) continue;

                // ── Kalkulasi total ──────────────────────────────────────────

                // Tidak ada diskon, sehingga total = subtotal
                $total = round($subtotal, 2);

                // Metode bayar: 70% tunai, 20% transfer, 10% qris
                $metodeBayar = $this->metodeBayarAcak();

                // Uang yang dibayarkan — tunai dibulatkan ke atas kelipatan 1.000
                $bayar     = ($metodeBayar === 'tunai')
                    ? (float) (ceil($total / 1000) * 1000)
                    : $total;
                $kembalian = round($bayar - $total, 2);

                $kasirId    = $kasirIds[array_rand($kasirIds)];
                $customerId = (rand(1, 100) <= 65)
                    ? $customerIds[array_rand($customerIds)]
                    : null;

                // ── Simpan ke DB ─────────────────────────────────────────────

                DB::transaction(function () use (
                    $noTrx, $kasirId, $customerId,
                    $subtotal, $total, $bayar, $kembalian,
                    $metodeBayar, $waktu, $items
                ) {
                    $transaksiId = DB::table('transaksi')->insertGetId([
                        'user_id'           => $kasirId,
                        'customer_id'       => $customerId,
                        'no_transaksi'      => $noTrx,
                        'subtotal'          => number_format($subtotal, 2, '.', ''),
                        'total'             => number_format($total,    2, '.', ''),
                        'bayar'             => number_format($bayar,    2, '.', ''),
                        'kembalian'         => number_format($kembalian, 2, '.', ''),
                        'metode_bayar'      => $metodeBayar,
                        'status'            => 'COMPLETED',
                        'tanggal_transaksi' => $waktu->toDateTimeString(),
                        'created_at'        => $waktu->toDateTimeString(),
                    ]);

                    foreach ($items as $item) {
                        DB::table('detail_transaksi')->insert([
                            'transaksi_id'    => $transaksiId,
                            'produk_id'       => $item['produk_id'],
                            'produk_satuan_id'=> $item['produk_satuan_id'],
                            'nama_satuan'     => $item['nama_satuan'],
                            'isi_satuan'      => $item['isi_satuan'],
                            'qty'             => $item['qty'],
                            'harga_satuan'    => number_format($item['harga_satuan'], 2, '.', ''),
                            'subtotal'        => number_format($item['subtotal'],     2, '.', ''),
                        ]);

                        // Kurangi stok produk di DB — bypass Kafka karena ini data historis seeder
                        DB::table('produk')
                            ->where('id', $item['produk_id'])
                            ->decrement('qty_stok', $item['qty_dasar']);
                    }
                });
            }
        }

        // ── Ringkasan ────────────────────────────────────────────────────────
        $totalTrx    = DB::table('transaksi')->where('status', 'COMPLETED')->count();
        $totalDetail = DB::table('detail_transaksi')->count();
        $this->command->info("TransaksiSeeder selesai: {$totalTrx} transaksi, {$totalDetail} item detail.");
    }

    // Ambil $n item acak dari array asosiatif (kunci dipertahankan)
    private function ambilAcak(array $arr, int $n): array
    {
        $n    = min($n, count($arr));
        $keys = (array) array_rand($arr, $n);
        return array_map(fn($k) => $arr[$k], $keys);
    }

    private function metodeBayarAcak(): string
    {
        $r = rand(1, 10);
        return match (true) {
            $r <= 7 => 'tunai',
            $r <= 9 => 'transfer',
            default => 'qris',
        };
    }
}
