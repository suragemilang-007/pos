# ERD.md — Struktur Database Sistem POS Grosir
> **Versi 3.0** — Tambah: Void Transaksi + Promo Produk

## Konvensi Global
- Harga & nilai keuangan: `DECIMAL(10,2)` — JANGAN pakai FLOAT
- Primary key: `INT UNSIGNED AUTO_INCREMENT`
- Timestamps: `TIMESTAMP` (bukan DATETIME)
- Status: selalu `ENUM`, bukan `VARCHAR`
- Soft delete: via kolom `status ENUM('aktif','nonaktif')`, bukan `deleted_at`
- Nama tabel: `snake_case` plural | Nama kolom: `snake_case`
- **Stok selalu dalam satuan_dasar** — konversi: `qty x isi_satuan`

---

## PAKET 1: AUTENTIKASI

### Tabel: `roles`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | TINYINT UNSIGNED | NO | PK | AUTO_INCREMENT | Max 255, hemat storage |
| nama_role | VARCHAR(50) | NO | | — | kasir, admin_gudang, owner |
| permissions | JSON | YES | | NULL | Array string izin |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

### Tabel: `users`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| role_id | TINYINT UNSIGNED | NO | FK→roles | — | |
| nama | VARCHAR(100) | NO | | — | |
| username | VARCHAR(50) | NO | UNIQUE | — | |
| password_hash | VARCHAR(255) | NO | | — | bcrypt — JANGAN expose |
| status | ENUM('aktif','nonaktif') | NO | | aktif | |
| last_login | TIMESTAMP | YES | | NULL | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

### Tabel: `sessions`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| user_id | INT UNSIGNED | NO | FK→users | — | |
| token | VARCHAR(500) | NO | | — | JWT token |
| status | ENUM('active','revoked') | NO | | active | |
| expired_at | TIMESTAMP | NO | | — | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

---

## PAKET 2: TRANSAKSI PENJUALAN

### Tabel: `customers`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| nama | VARCHAR(100) | NO | | — | |
| no_telp | VARCHAR(20) | NO | UNIQUE | — | Identifier bisnis |
| alamat | TEXT | YES | | NULL | |
| tipe_customer | ENUM('reguler','grosir') | NO | | reguler | |
| status | ENUM('aktif','nonaktif') | NO | | aktif | Soft delete |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP ON UPDATE | |

### Tabel: `transaksi` ⚠️ Diperbarui v3
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| user_id | INT UNSIGNED | NO | FK→users | — | Kasir |
| customer_id | INT UNSIGNED | YES | FK→customers | NULL | Opsional |
| no_transaksi | VARCHAR(30) | NO | UNIQUE | — | TRX-YYYYMMDD-XXXXX |
| subtotal | DECIMAL(10,2) | NO | | 0.00 | Sebelum diskon |
| diskon | DECIMAL(10,2) | NO | | 0.00 | |
| total | DECIMAL(10,2) | NO | | 0.00 | subtotal - diskon |
| bayar | DECIMAL(10,2) | NO | | 0.00 | |
| kembalian | DECIMAL(10,2) | NO | | 0.00 | |
| metode_bayar | ENUM('tunai','transfer','qris') | NO | | tunai | |
| status | ENUM('PENDING','COMPLETED','FAILED','CANCELLED','VOIDED') | NO | | PENDING | VOIDED = dibatalkan via void |
| tanggal_transaksi | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

> **Perubahan v3:** Tambah nilai `VOIDED` pada ENUM status.

### Tabel: `detail_transaksi` ⚠️ Diperbarui v3
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| transaksi_id | INT UNSIGNED | NO | FK→transaksi | — | |
| produk_id | INT UNSIGNED | NO | FK→produk | — | |
| produk_satuan_id | INT UNSIGNED | YES | FK→produk_satuan | NULL | |
| promo_id | INT UNSIGNED | YES | FK→produk_promo | NULL | Promo yang diterapkan (nullable) |
| nama_satuan | VARCHAR(30) | NO | | pcs | Snapshot |
| isi_satuan | INT | NO | | 1 | Snapshot |
| qty | INT | NO | | — | |
| harga_satuan | DECIMAL(10,2) | NO | | — | Snapshot (0 jika item bonus) |
| diskon_item | DECIMAL(10,2) | NO | | 0.00 | Diskon dari promo |
| subtotal | DECIMAL(10,2) | NO | | — | |
| is_bonus | TINYINT(1) | NO | | 0 | 1 = item bonus dari promo |

> **Perubahan v3:** Tambah `promo_id` (FK, nullable) dan `is_bonus` untuk
> membedakan item reguler dengan item gratis dari promo.

### Tabel: `retur_penjualan`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| transaksi_id | INT UNSIGNED | NO | FK→transaksi | — | Harus COMPLETED |
| user_id | INT UNSIGNED | NO | FK→users | — | |
| no_retur | VARCHAR(30) | NO | UNIQUE | — | RTJ-YYYYMMDD-XXXXX |
| alasan | TEXT | NO | | — | |
| total_refund | DECIMAL(10,2) | NO | | 0.00 | |
| status | ENUM('PENDING','COMPLETED','REJECTED') | NO | | PENDING | |
| tanggal_retur | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

### Tabel: `detail_retur_penjualan`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| retur_id | INT UNSIGNED | NO | FK→retur_penjualan | — | |
| produk_id | INT UNSIGNED | NO | FK→produk | — | |
| nama_satuan | VARCHAR(30) | NO | | pcs | Snapshot |
| isi_satuan | INT | NO | | 1 | Snapshot |
| qty_retur | INT | NO | | — | |
| harga_satuan | DECIMAL(10,2) | NO | | — | Snapshot |
| subtotal_refund | DECIMAL(10,2) | NO | | — | |

### Tabel: `void_transaksi` NEW
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| transaksi_id | INT UNSIGNED | NO | FK→transaksi, UNIQUE | — | Satu transaksi hanya bisa di-void sekali |
| transaksi_baru_id | INT UNSIGNED | YES | FK→transaksi | NULL | Transaksi koreksi pengganti (jika ada) |
| user_id | INT UNSIGNED | NO | FK→users | — | Kasir yang melakukan void |
| alasan | ENUM('kesalahan_input','barang_rusak_saat_transaksi','permintaan_pembeli','lainnya') | NO | | — | |
| keterangan | TEXT | YES | | NULL | Detail tambahan |
| status | ENUM('PENDING','COMPLETED','REJECTED') | NO | | PENDING | |
| batas_waktu | TIMESTAMP | NO | | — | Waktu kedaluwarsa void |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

> **Constraint penting:** `UNIQUE KEY uq_void_transaksi (transaksi_id)` —
> satu transaksi hanya bisa di-void satu kali.
>
> **Aturan void:** transaksi hanya bisa di-void jika:
> 1. status = 'COMPLETED'
> 2. created_at > NOW() - INTERVAL {VOID_TIME_LIMIT_MINUTES} MINUTE
> 3. Tidak ada record di void_transaksi untuk transaksi ini
> 4. Tidak ada record di retur_penjualan untuk transaksi ini

---

## PAKET 3: INVENTORI

### Tabel: `kategori_produk`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| nama_kategori | VARCHAR(100) | NO | UNIQUE | — | |
| deskripsi | TEXT | YES | | NULL | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

> Tidak ada soft delete. Jika masih dipakai produk: TOLAK hapus.

### Tabel: `produk`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| kategori_id | INT UNSIGNED | NO | FK→kategori_produk | — | |
| kode_produk | VARCHAR(20) | NO | UNIQUE | — | Barcode satuan terkecil |
| nama_produk | VARCHAR(200) | NO | | — | |
| deskripsi | TEXT | YES | | NULL | |
| harga_beli_dasar | DECIMAL(10,2) | NO | | 0.00 | Per satuan_dasar |
| qty_stok | INT | NO | | 0 | Dalam satuan_dasar — diperbarui Consumer |
| stok_minimum | INT | NO | | 0 | Trigger warning |
| satuan_dasar | VARCHAR(30) | NO | | pcs | |
| status | ENUM('aktif','nonaktif') | NO | | aktif | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP ON UPDATE | |

### Tabel: `produk_satuan`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| produk_id | INT UNSIGNED | NO | FK→produk | — | |
| nama_satuan | VARCHAR(30) | NO | | — | |
| isi | INT | NO | | 1 | Berapa satuan_dasar dalam 1 unit |
| harga_beli | DECIMAL(10,2) | NO | | 0.00 | |
| harga_jual | DECIMAL(10,2) | NO | | 0.00 | |
| is_default | TINYINT(1) | NO | | 0 | 1=default di form transaksi |
| barcode | VARCHAR(50) | YES | UNIQUE | NULL | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP ON UPDATE | |

> UNIQUE KEY: `uq_produk_satuan (produk_id, nama_satuan)`

### Tabel: `produk_promo` NEW
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| produk_id | INT UNSIGNED | NO | FK→produk | — | Produk yang promo |
| produk_satuan_id | INT UNSIGNED | NO | FK→produk_satuan | — | Satuan syarat beli |
| nama_promo | VARCHAR(200) | NO | | — | Teks deskriptif: "Beli 5 Gratis 1" |
| tipe_promo | ENUM('bonus_produk_sama','bonus_produk_lain','diskon_persen','diskon_nominal') | NO | | — | |
| min_qty | INT | NO | | 1 | Minimum qty beli (syarat) |
| bonus_qty | INT | YES | | NULL | Qty bonus (untuk tipe bonus) |
| bonus_produk_id | INT UNSIGNED | YES | FK→produk | NULL | Produk bonus (tipe bonus_produk_lain) |
| bonus_satuan_id | INT UNSIGNED | YES | FK→produk_satuan | NULL | Satuan produk bonus |
| diskon_nilai | DECIMAL(10,2) | YES | | NULL | Nilai diskon (% atau nominal Rp) |
| keterangan | TEXT | YES | | NULL | Catatan tambahan untuk kasir |
| is_active | TINYINT(1) | NO | | 1 | 1=aktif |
| berlaku_dari | DATE | YES | | NULL | NULL = berlaku mulai sekarang |
| berlaku_sampai | DATE | YES | | NULL | NULL = tidak ada batas akhir |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP ON UPDATE | |

> **Validasi tipe_promo:**
> - `bonus_produk_sama`: wajib `bonus_qty`, `bonus_produk_id` NULL
> - `bonus_produk_lain`: wajib `bonus_qty`, `bonus_produk_id`, `bonus_satuan_id`
> - `diskon_persen`: wajib `diskon_nilai` (0-100), kolom bonus NULL
> - `diskon_nominal`: wajib `diskon_nilai` (> 0), kolom bonus NULL
>
> **Promo aktif query:**
> ```sql
> WHERE is_active = 1
>   AND (berlaku_dari IS NULL OR berlaku_dari <= CURDATE())
>   AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
> ```

### Tabel: `suppliers`
| Kolom | Tipe | Null | Key | Default |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT |
| nama_supplier | VARCHAR(150) | NO | | — |
| kontak_person | VARCHAR(100) | YES | | NULL |
| no_telp | VARCHAR(20) | NO | UNIQUE | — |
| alamat | TEXT | YES | | NULL |
| status | ENUM('aktif','nonaktif') | NO | | aktif |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP ON UPDATE |

### Tabel: `produk_supplier`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| produk_id | INT UNSIGNED | NO | FK→produk | — | |
| supplier_id | INT UNSIGNED | NO | FK→suppliers | — | |
| harga_suplai | DECIMAL(10,2) | NO | | 0.00 | Per satuan_dasar |
| is_primary | TINYINT(1) | NO | | 0 | 1=supplier utama |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

---

## PAKET 4: PEMBELIAN

### Tabel: `pembelian`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| user_id | INT UNSIGNED | NO | FK→users | — | Admin Gudang |
| supplier_id | INT UNSIGNED | NO | FK→suppliers | — | |
| no_pembelian | VARCHAR(30) | NO | UNIQUE | — | PO-YYYYMMDD-XXXXX |
| total | DECIMAL(10,2) | NO | | 0.00 | |
| status | ENUM('PENDING','COMPLETED','CANCELLED') | NO | | PENDING | |
| tanggal_pembelian | DATE | NO | | — | DATE bukan TIMESTAMP |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP ON UPDATE | |

### Tabel: `detail_pembelian`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| pembelian_id | INT UNSIGNED | NO | FK→pembelian | — | |
| produk_id | INT UNSIGNED | NO | FK→produk | — | |
| produk_satuan_id | INT UNSIGNED | YES | FK→produk_satuan | NULL | |
| nama_satuan | VARCHAR(30) | NO | | pcs | Snapshot |
| isi_satuan | INT | NO | | 1 | Snapshot |
| qty | INT | NO | | — | |
| harga_beli_satuan | DECIMAL(10,2) | NO | | — | |
| subtotal | DECIMAL(10,2) | NO | | — | |

### Tabel: `retur_pembelian`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| pembelian_id | INT UNSIGNED | NO | FK→pembelian | — | |
| user_id | INT UNSIGNED | NO | FK→users | — | |
| no_retur | VARCHAR(30) | NO | UNIQUE | — | RTP-YYYYMMDD-XXXXX |
| alasan | TEXT | NO | | — | |
| total_kredit | DECIMAL(10,2) | NO | | 0.00 | |
| status | ENUM('PENDING','COMPLETED','REJECTED') | NO | | PENDING | |
| tanggal_retur | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

### Tabel: `detail_retur_pembelian`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| retur_pembelian_id | INT UNSIGNED | NO | FK→retur_pembelian | — | |
| produk_id | INT UNSIGNED | NO | FK→produk | — | |
| nama_satuan | VARCHAR(30) | NO | | pcs | Snapshot |
| isi_satuan | INT | NO | | 1 | Snapshot |
| qty_retur | INT | NO | | — | |
| harga_satuan | DECIMAL(10,2) | NO | | — | Snapshot |
| subtotal_kredit | DECIMAL(10,2) | NO | | — | |

---

## Ringkasan Relasi

| Tabel A | Tabel B | Relasi | Keterangan |
|---|---|---|---|
| roles | users | 1:N | |
| users | sessions | 1:N | |
| users | transaksi | 1:N | |
| users | retur_penjualan | 1:N | |
| users | void_transaksi | 1:N | Kasir yang melakukan void |
| users | pembelian | 1:N | |
| users | retur_pembelian | 1:N | |
| customers | transaksi | 1:N | |
| kategori_produk | produk | 1:N | |
| produk | produk_satuan | 1:N | Multi-unit pricing |
| produk | produk_promo | 1:N | Satu produk bisa punya banyak promo |
| produk | suppliers | N:M | Via produk_supplier |
| produk_satuan | detail_transaksi | 1:N | |
| produk_satuan | detail_pembelian | 1:N | |
| produk_satuan | produk_promo | 1:N | Satuan syarat promo |
| produk_promo | detail_transaksi | 1:N | Audit promo yang diterapkan |
| transaksi | detail_transaksi | 1:N | Composition |
| transaksi | retur_penjualan | 1:N | |
| transaksi | void_transaksi | 1:1 | Satu transaksi max satu void |
| void_transaksi | transaksi | 1:1 | transaksi_baru_id (nullable) |
| retur_penjualan | detail_retur_penjualan | 1:N | |
| suppliers | pembelian | 1:N | |
| pembelian | detail_pembelian | 1:N | |
| pembelian | retur_pembelian | 1:N | |
| retur_pembelian | detail_retur_pembelian | 1:N | |

---

## Aturan Kritis Consumer Kafka

```
KONVERSI SATUAN (semua operasi stok):
qty_berubah = qty x isi_satuan

VOID TRANSAKSI:
qty_kembali = qty x isi_satuan (untuk setiap item REGULER)
Item is_bonus=1: tidak perlu dikembalikan ke stok
(barang bonus sudah keluar dan tidak dicatat sebagai penjualan berbayar)

ENV WAJIB DITAMBAH:
VOID_TIME_LIMIT_MINUTES=30  (batas waktu void dalam menit)
```
