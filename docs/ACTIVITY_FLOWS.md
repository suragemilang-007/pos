# ACTIVITY_FLOWS.md — Ringkasan Alur per Fitur
> **Versi 4.0** — Update: AD-09 (Cash/Kredit) + Tambah AD-19, AD-20

---

## AD-01: Login | Tidak via Kafka
- Validasi format SEBELUM query ke DB
- Verifikasi bcrypt, buat session, return token + role
- JANGAN sertakan password_hash dalam return

## AD-02: Logout | Tidak via Kafka
- UPDATE sessions SET status='revoked' dulu, baru hapus token client

## AD-03: Input Transaksi Penjualan | VIA KAFKA
- Controller: INSERT transaksi PENDING → publish Kafka
- Return HTTP 202 (bukan 200)
- Consumer: validasi stok (qty x isi_satuan) → kurangi stok → COMPLETED/FAILED
- Kafka topic: transaction-events | Event: transaction.created

## AD-04: Cetak Struk | Tidak via Kafka
- GET /transaksi/{id}/struk
- JOIN transaksi + detail + produk + info toko
- Return HTML siap cetak

## AD-05: Retur Penjualan | VIA KAFKA
- Validasi: transaksi COMPLETED dan status BUKAN VOIDED
- qty_retur <= qty beli asli
- Consumer: TAMBAH stok (qty_retur x isi_satuan)
- Kafka topic: inventory-updates | Event: sale.return.requested

## AD-06: Lihat Stok Real-Time | Tidak via Kafka + WebSocket
- HTTP GET initial load, lalu WebSocket push stock.updated
- Frontend hitung stok per satuan: floor(stokBaru / isi)

## AD-07: Cek Stok | Tidak via Kafka
- GET /produk/{kode}/stok
- Response sertakan satuan_tersedia[] lengkap dengan stok per satuan

## AD-08: Manajemen Produk CRUD | Tidak via Kafka
- Hapus: cek COUNT(detail_transaksi) dulu
- Soft delete jika pernah ada di transaksi

## AD-09: Manajemen Pembelian / Restock | VIA KAFKA ⚠️ Update v4
Swimlanes: Frontend → Backend → Kafka → Consumer+DB → WebSocket

TIPE PEMBAYARAN:
  Cash:
    - jumlah_dp = total (terisi penuh otomatis)
    - status_pembayaran = 'lunas', sisa_hutang = 0
    - tanggal_jatuh_tempo = NULL

  Kredit:
    - Input jumlah_dp (boleh 0 = full kredit)
    - Input tanggal_jatuh_tempo (WAJIB jika kredit)
    - sisa_hutang = total - jumlah_dp (otomatis)
    - dp=0 → status_pembayaran='belum_lunas'
    - dp>0 → status_pembayaran='sebagian'

ALUR:
1. Admin isi form PO + pilih tipe pembayaran
2. POST /api/gudang/pembelian → validasi → INSERT pembelian PENDING
3. Publish Kafka event purchase.created
4. HTTP 202 dikembalikan
5. Consumer:
   a. UPDATE qty_stok + harga_beli per item (qty x isi_satuan)
   b. UPDATE pembelian status='COMPLETED'
   c. Jika cash: INSERT pembayaran_pembelian (jumlah=total)
   d. Jika kredit + dp>0: INSERT pembayaran_pembelian (jumlah=dp)
   e. Jika kredit + dp=0: TIDAK INSERT (belum ada bayaran)
6. Push WebSocket: purchase.completed
   Jika kredit: sertakan info hutang (sisa_hutang, tanggal_jatuh_tempo)

## AD-10: Retur Pembelian | VIA KAFKA
- Consumer: KURANGI stok (qty_retur x isi_satuan)
- Kafka topic: inventory-updates | Event: purchase.return.requested

## AD-11: Manajemen Customer CRUD | Tidak via Kafka
- Soft delete jika ada riwayat di transaksi

## AD-12: Riwayat Belanja Customer | Tidak via Kafka
- GET /customer/{id}/riwayat?page=1&limit=20

## AD-13: Kelola Supplier CRUD | Tidak via Kafka
- Soft delete jika ada riwayat di pembelian

## AD-14: Laporan | Tidak via Kafka
- Role check: HANYA owner
- Jenis: penjualan, stok, pembelian, void, hutang, penyesuaian_stok

## AD-15: Manajemen User | Tidak via Kafka
- Nonaktifkan: UPDATE status='nonaktif' DULU, baru revoke sessions

## AD-16: Manajemen Kategori Produk | Tidak via Kafka
- Cek COUNT(produk) sebelum DELETE
- Race condition guard di handler DELETE

## AD-17: Void Transaksi | VIA KAFKA
- Syarat: COMPLETED + < 30 menit + belum void + belum retur
- Consumer: TAMBAH stok semua item, UPDATE status='VOIDED'
- Kafka topic: transaction-events | Event: transaction.void.requested

## AD-18: Manajemen Promo Produk | Tidak via Kafka (untuk CRUD)
- CRUD promo oleh Admin Gudang
- Penerapan promo: kalkulasi di Frontend JS (bukan Backend)

## AD-19: Pembayaran Hutang Pembelian | Tidak via Kafka NEW
Swimlanes: Frontend → Backend → Database

KAPAN DIGUNAKAN:
  Saat toko membayar cicilan atau melunasi hutang
  ke supplier dari PO yang tipe_pembayaran='kredit'

ALUR:
1. Admin buka menu Daftar Hutang
2. GET /api/gudang/hutang → list PO belum_lunas/sebagian
   Include: hari_tersisa sampai jatuh tempo, badge H-N
3. Admin klik [Bayar] pada baris PO
4. GET /api/gudang/pembelian/{id} → detail + riwayat bayar
5. Admin isi form: jumlah, metode, tanggal_bayar, catatan
6. POST /api/gudang/pembelian/{id}/bayar
   Validasi:
   - jumlah > 0
   - jumlah <= sisa_hutang
   - status_pembayaran != 'lunas'
7. INSERT pembayaran_pembelian
8. UPDATE pembelian:
   - sisa_hutang = sisa_hutang - jumlah
   - Jika sisa=0: status_pembayaran='lunas'
   - Jika sisa>0: status_pembayaran='sebagian'
9. HTTP 200 OK
10. UI update: jika lunas → hapus dari daftar hutang
                jika belum → update sisa

TIDAK melalui Kafka karena ini operasi keuangan sinkron
yang tidak mempengaruhi stok barang.

## AD-20: Penyesuaian Stok | VIA KAFKA NEW
Swimlanes: Frontend → Backend → Kafka → Consumer+DB → WebSocket

KAPAN DIGUNAKAN:
  - Barang rusak di gudang (bukan dari supplier)
  - Barang hilang
  - Barang expired/kadaluarsa
  - Koreksi stok setelah opname (plus atau minus)

TIPE PENYESUAIAN:
  Kurangi stok:  barang_rusak, barang_hilang,
                 barang_expired, koreksi_minus
  Tambah stok:   koreksi_plus

ALUR:
1. Admin Gudang buka menu Penyesuaian Stok
2. Lihat riwayat penyesuaian + tombol [+ Penyesuaian Baru]
3. Cari produk (scan/ketik) → tampilkan stok saat ini
4. Pilih tipe + input qty_satuan_dasar + keterangan (wajib)
5. Validasi Frontend: qty tidak boleh > stok jika tipe kurang
6. POST /api/gudang/penyesuaian-stok
   Validasi Backend:
   - produk ada dan aktif
   - qty > 0
   - Jika tipe kurang: qty <= qty_stok (cegah stok negatif)
7. INSERT penyesuaian_stok status='PENDING'
8. Publish Kafka: stock.adjustment.requested
9. HTTP 202 dikembalikan
10. Consumer:
    - Jika tambah: UPDATE qty_stok + qty_satuan_dasar
    - Jika kurang:  UPDATE qty_stok - qty_satuan_dasar
    - UPDATE penyesuaian_stok status='COMPLETED'
11. Push WebSocket: adjustment.completed + stock.updated

PERBEDAAN dengan Retur Pembelian:
  Retur Pembelian → barang kembali ke supplier → ada kredit nota
  Penyesuaian Stok → barang dibuang/hilang → kerugian toko
                   → tidak ada transaksi keuangan ke supplier
