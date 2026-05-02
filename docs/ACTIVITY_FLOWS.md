# ACTIVITY_FLOWS.md — Ringkasan Alur per Fitur
> **Versi 3.0** — Tambah: Void Transaksi (AD-17) + Promo Produk (AD-18)

Baca file ini sebelum mengimplementasi fitur apapun.

---

## AD-01: Login | Tidak via Kafka
Swimlanes: Frontend → Backend → Database
- Validasi format SEBELUM query ke DB
- Verifikasi bcrypt setelah dapat data user
- Buat record di tabel sessions saat sukses
- Return: {token, role, user_data} — JANGAN sertakan password_hash

## AD-02: Logout | Tidak via Kafka
Swimlanes: Frontend → Backend → Database
- UPDATE sessions SET status='revoked' terlebih dahulu
- Hapus token di client setelah server konfirmasi

## AD-03: Input Transaksi Penjualan | VIA KAFKA
Swimlanes: Frontend → Backend → Kafka → Consumer+DB → WebSocket
- Controller: INSERT transaksi status=PENDING, lalu publish Kafka
- Return ke client: HTTP 202 (bukan 200)
- Consumer: validasi stok aktual (qty x isi_satuan) dulu sebelum kurangi
- Jika stok tidak cukup: status=FAILED, push WebSocket notif gagal
- Payload item WAJIB sertakan: produk_satuan_id, isi_satuan, promo_id (nullable)
- Kafka topic: transaction-events | Event: transaction.created

## AD-04: Cetak Struk | Tidak via Kafka
- GET /transaksi/{id}/struk
- JOIN: transaksi + detail_transaksi + produk + info toko
- Tampilkan nama_satuan dan keterangan promo per baris item
- Return HTML siap cetak

## AD-05: Retur Penjualan | VIA KAFKA
- Validasi: transaksi harus COMPLETED dan status BUKAN VOIDED
- qty_retur <= qty beli asli
- Consumer: TAMBAH stok (qty_retur x isi_satuan)
- Kafka topic: inventory-updates | Event: sale.return.requested

## AD-06: Lihat Stok Real-Time | Tidak via Kafka, ada WebSocket
- Initial load: HTTP GET /stok
- WebSocket push event stock.updated {produkId, stokBaru}
- Frontend hitung stok per satuan: floor(stokBaru / isi)

## AD-07: Cek Stok | Tidak via Kafka
- GET /produk/{kode}/stok
- Response WAJIB sertakan promo_aktif[] (promo yang berlaku hari ini)
- Frontend handle logika warning stok dan tampilan badge promo

## AD-08: Manajemen Produk CRUD | Tidak via Kafka
- Hapus: cek COUNT(detail_transaksi) dulu
- Soft delete jika pernah ada di transaksi

## AD-09: Pembelian/Restock | VIA KAFKA
- Consumer: TAMBAH stok (qty x isi_satuan) + UPDATE harga_beli
- Kafka topic: inventory-updates | Event: purchase.created

## AD-10: Retur Pembelian | VIA KAFKA
- Consumer: KURANGI stok (qty_retur x isi_satuan)
- Kafka topic: inventory-updates | Event: purchase.return.requested

## AD-11: Manajemen Customer CRUD | Tidak via Kafka
- Soft delete jika ada riwayat di tabel transaksi

## AD-12: Riwayat Belanja Customer | Tidak via Kafka
- GET /customer/{id}/riwayat?page=1&limit=20
- Include: total_belanja (SUM), frekuensi (COUNT)

## AD-13: Kelola Supplier CRUD | Tidak via Kafka
- Soft delete jika ada riwayat di tabel pembelian

## AD-14: Laporan | Tidak via Kafka
- Role check: HANYA owner
- Jenis: penjualan, stok, pembelian, void, promo

## AD-15: Manajemen User | Tidak via Kafka
- Nonaktifkan: UPDATE status='nonaktif' DULU, baru revoke sessions

## AD-16: Manajemen Kategori Produk | Tidak via Kafka
Swimlanes: Frontend → Backend → Database
- Tidak ada soft delete untuk kategori
- Cek COUNT(produk) WHERE kategori_id=? sebelum DELETE
- Jika masih ada produk: TOLAK hapus dengan pesan informatif
- Cek ulang hasProduk di handler DELETE (race condition guard)

## AD-17: Void Transaksi | VIA KAFKA
Swimlanes: Frontend → Backend → Kafka → Consumer+DB → WebSocket

ATURAN BISNIS VOID (semua harus terpenuhi):
- Transaksi status = 'COMPLETED' (bukan FAILED, CANCELLED, atau VOIDED)
- Dibuat < 30 menit yang lalu (konfigurabel via env VOID_TIME_LIMIT_MINUTES)
- Belum pernah di-void (cek tabel void_transaksi)
- Belum ada retur penjualan untuk transaksi ini

ALUR:
1. Kasir input nomor transaksi → GET /transaksi/{id}
2. Backend validasi 4 kondisi di atas berurutan
3. Jika gagal: return HTTP 400 dengan pesan spesifik per kondisi
4. Kasir pilih alasan void + keterangan opsional
5. POST /transaksi/{id}/void → INSERT void_transaksi PENDING
6. Publish event transaction.void.requested ke Kafka
7. HTTP 202 Accepted dikembalikan
8. Consumer: TAMBAH semua stok (qty x isi_satuan) dalam satu DB transaction
9. Consumer: UPDATE transaksi status='VOIDED'
10. Consumer: UPDATE void_transaksi status='COMPLETED'
11. WebSocket push: void.completed + stock.updated

VOID = batalkan SEMUA item sekaligus (tidak bisa parsial)
Jika ingin ganti sebagian: void dulu, buat transaksi baru

## AD-18: Manajemen Promo Produk | Tidak via Kafka
Swimlanes: Frontend → Backend → Database (untuk CRUD promo)
Swimlanes: Frontend → (kalkulasi lokal JS) (untuk penerapan di transaksi)

BAGIAN A — CRUD Promo (Admin Gudang):
- GET /gudang/promo → daftar semua promo
- POST /gudang/promo → tambah promo baru
- PUT /gudang/promo/{id} → edit promo
- PATCH /gudang/promo/{id}/toggle → aktifkan/nonaktifkan
- DELETE /gudang/promo/{id} → hapus (hanya jika belum pernah dipakai)

4 Tipe Promo:
  bonus_produk_sama  : Beli 5 pcs gratis 1 pcs (produk yang sama)
  bonus_produk_lain  : Beli 4 renteng gratis 1 piring (produk berbeda)
  diskon_persen      : Beli min 1 karton diskon 10%
  diskon_nominal     : Beli min 3 lusin potongan Rp 5.000

BAGIAN B — Penerapan di Transaksi (Frontend kalkulasi lokal):
- Promo aktif dimuat bersamaan dengan GET /produk/{kode}/stok
- Kalkulasi promo dilakukan di JavaScript Frontend (BUKAN di Backend)
- Backend TIDAK menghitung promo — Frontend yang memasukkan item bonus
  dan diskon ke dalam payload POST /transaksi
- promo_id disertakan di setiap item detail_transaksi untuk audit

ATURAN PROMO AKTIF:
  is_active = 1
  AND (berlaku_dari IS NULL OR berlaku_dari <= CURDATE())
  AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
