# ACTIVITY_FLOWS.md — Ringkasan Alur per Fitur

Baca file ini sebelum mengimplementasi fitur apapun.
Format: [Kode] Nama | Kafka? | Poin kritis implementasi

---

## AD-01: Login | ❌ Tidak via Kafka
**Swimlanes:** Frontend → Backend → Database
- Validasi format input SEBELUM query ke DB
- Verifikasi bcrypt setelah dapat data user dari DB
- Buat record di tabel `sessions` saat sukses
- Return: `{token, role, user_data}` — JANGAN sertakan `password_hash`
- Jika format salah: HTTP 422 tanpa query DB
- Jika kredensial salah: HTTP 401

## AD-02: Logout | ❌ Tidak via Kafka
**Swimlanes:** Frontend → Backend → Database
- UPDATE sessions SET status='revoked' terlebih dahulu
- Baru hapus token di client setelah server konfirmasi
- Urutan ini penting: server dulu, client kemudian

## AD-03: Input Transaksi Penjualan | ✅ VIA KAFKA
**Swimlanes:** Frontend → Backend → Kafka → Consumer+DB → WebSocket
- Controller: INSERT transaksi status=PENDING, lalu publish Kafka
- Return ke client: HTTP **202** (bukan 200) — operasi asinkron
- Consumer: validasi stok aktual dulu sebelum kurangi
- Jika stok tidak cukup: status=FAILED, push WebSocket notif gagal
- Jika stok cukup: kurangi stok, status=COMPLETED, push WebSocket sukses + data struk
- Kafka topic: `transaction-events`
- Event name: `transaction.created`

## AD-04: Cetak Struk | ❌ Tidak via Kafka
**Swimlanes:** Frontend → Backend → Database
- GET endpoint: `/transaksi/{id}/struk`
- Validasi: token valid + transaksi milik sesi ini
- JOIN: transaksi + detail_transaksi + produk + info toko
- Return: HTML string siap cetak (browser yang handle print dialog)
- Jika akses tidak sah: HTTP 403

## AD-05: Retur Penjualan | ✅ VIA KAFKA
**Swimlanes:** Frontend → Backend → Kafka → Consumer+DB → WebSocket
- Validasi SEBELUM publish: transaksi harus COMPLETED, qty_retur ≤ qty beli asli
- Consumer: TAMBAH qty_stok (produk kembali ke gudang)
- Kafka topic: `inventory-updates`
- Event name: `sale.return.requested`

## AD-06: Lihat Stok Real-Time | ❌ Tidak via Kafka, ada WebSocket
**Swimlanes:** Frontend → Backend → Database → WebSocket
- Initial load: HTTP GET /stok (sinkron)
- Setelah render: Frontend establish WebSocket ke `/ws`
- Push event: `stock.updated` berisi `{produk_id, stok_baru}`
- Frontend: update hanya baris yang berubah, JANGAN reload seluruh tabel
- Highlight produk dengan qty_stok ≤ stok_minimum

## AD-07: Cek Stok | ❌ Tidak via Kafka
**Swimlanes:** Frontend → Backend → Database
- GET `/produk/{kode}/stok`
- Logika kondisional di Frontend:
  - `qty_stok = 0` → warning "Stok Habis", blokir input qty
  - `qty_stok ≤ stok_minimum` → warning "Stok Menipis", tetap izinkan input
  - `qty_stok > stok_minimum` → tampilkan info normal
- Jika kode tidak ditemukan: HTTP 404

## AD-08: Manajemen Produk CRUD | ❌ Tidak via Kafka
**Swimlanes:** Frontend → Backend → Database
- Hapus: cek `COUNT(*) FROM detail_transaksi WHERE produk_id=?` dulu
- Jika pernah di transaksi: UPDATE status='nonaktif' (soft delete)
- Jika belum pernah: DELETE langsung (hard delete)
- Kode produk harus UNIQUE, validasi sebelum INSERT

## AD-09: Pembelian / Restock | ✅ VIA KAFKA
**Swimlanes:** Frontend → Backend → Kafka → Consumer+DB → WebSocket
- Consumer: TAMBAH qty_stok + UPDATE harga_beli produk
- Kafka topic: `inventory-updates`
- Event name: `purchase.created`

## AD-10: Retur Pembelian | ✅ VIA KAFKA
**Swimlanes:** Frontend → Backend → Kafka → Consumer+DB → WebSocket
- Consumer: KURANGI qty_stok (produk keluar ke supplier)
- Hitung total_kredit = harga_satuan × qty_retur
- Kafka topic: `inventory-updates`
- Event name: `purchase.return.requested`

## AD-11: Manajemen Customer CRUD | ❌ Tidak via Kafka
**Swimlanes:** Frontend → Backend → Database
- Soft delete jika ada riwayat di tabel `transaksi`
- Unique key: `no_telp`
- Pola sama dengan AD-08 (Produk CRUD)

## AD-12: Riwayat Belanja Customer | ❌ Tidak via Kafka
**Swimlanes:** Frontend → Backend → Database
- GET `/customer/{id}/riwayat?page=1&limit=20`
- Include agregasi: `total_belanja` (SUM), `frekuensi_transaksi` (COUNT)
- Tampilkan detail transaksi dalam modal popup (tanpa navigasi halaman)
- Gunakan pagination untuk data historis yang banyak

## AD-13: Kelola Supplier CRUD | ❌ Tidak via Kafka
**Swimlanes:** Frontend → Backend → Database
- Soft delete jika ada riwayat di tabel `pembelian`
- Unique: `no_telp` (dan sebaiknya nama_supplier)
- Pola sama dengan AD-08 dan AD-11

## AD-14: Laporan | ❌ Tidak via Kafka
**Swimlanes:** Frontend → Backend → Database
- Role check PERTAMA: hanya owner yang boleh akses, HTTP 403 jika bukan
- Query agregasi: `SUM`, `COUNT`, `GROUP BY`, JOIN multiple table
- Export: GET `/laporan/{jenis}/export?format=pdf|excel`
- Jenis laporan: `penjualan`, `stok`, `pembelian`

## AD-15: Manajemen User | ❌ Tidak via Kafka
**Swimlanes:** Frontend → Backend → Database
- Role check: hanya owner
- Nonaktifkan: UPDATE status='nonaktif' DULU, baru DELETE sessions WHERE user_id=?
- Urutan WAJIB: UPDATE user dulu, revoke sessions kemudian
- Hash bcrypt SEBELUM simpan, JANGAN return password_hash di response
