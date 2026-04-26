# ERD.md — Struktur Database Sistem POS Grosir

> **Versi 2.0** — Update: Multi-Unit Pricing (`produk_satuan`)

## Konvensi Global

- Semua harga & nilai keuangan: `DECIMAL(10,2)` — **JANGAN** pakai `FLOAT`
- Semua primary key: `INT UNSIGNED AUTO_INCREMENT`
- Timestamps: `TIMESTAMP` (bukan DATETIME) — otomatis UTC, hemat 4 byte
- Status: selalu `ENUM`, bukan `VARCHAR`
- Soft delete: via kolom `status ENUM('aktif','nonaktif')`, bukan `deleted_at`
- Nama tabel: `snake_case` plural
- Nama kolom: `snake_case`
- **Stok selalu disimpan dalam satuan terkecil (satuan_dasar)** — konversi dilakukan saat transaksi

---

## PAKET 1: AUTENTIKASI

### Tabel: `roles`

| Kolom       | Tipe             | Null | Key | Default           | Keterangan                        |
| ----------- | ---------------- | ---- | --- | ----------------- | --------------------------------- |
| id          | TINYINT UNSIGNED | NO   | PK  | AUTO_INCREMENT    | Max 255, hemat storage            |
| nama_role   | VARCHAR(50)      | NO   |     | —                 | Nilai: kasir, admin_gudang, owner |
| permissions | JSON             | YES  |     | NULL              | Array string izin akses           |
| created_at  | TIMESTAMP        | NO   |     | CURRENT_TIMESTAMP |                                   |

### Tabel: `users`

| Kolom         | Tipe                     | Null | Key      | Default           | Keterangan                             |
| ------------- | ------------------------ | ---- | -------- | ----------------- | -------------------------------------- |
| id            | INT UNSIGNED             | NO   | PK       | AUTO_INCREMENT    |                                        |
| role_id       | TINYINT UNSIGNED         | NO   | FK→roles | —                 |                                        |
| nama          | VARCHAR(100)             | NO   |          | —                 |                                        |
| username      | VARCHAR(50)              | NO   | UNIQUE   | —                 | Untuk login                            |
| password_hash | VARCHAR(255)             | NO   |          | —                 | bcrypt — JANGAN expose di response API |
| status        | ENUM('aktif','nonaktif') | NO   |          | aktif             |                                        |
| last_login    | TIMESTAMP                | YES  |          | NULL              | Diperbarui tiap login sukses           |
| created_at    | TIMESTAMP                | NO   |          | CURRENT_TIMESTAMP |                                        |

### Tabel: `sessions`

| Kolom      | Tipe                     | Null | Key      | Default           | Keterangan |
| ---------- | ------------------------ | ---- | -------- | ----------------- | ---------- |
| id         | INT UNSIGNED             | NO   | PK       | AUTO_INCREMENT    |            |
| user_id    | INT UNSIGNED             | NO   | FK→users | —                 |            |
| token      | VARCHAR(500)             | NO   |          | —                 | JWT token  |
| status     | ENUM('active','revoked') | NO   |          | active            |            |
| expired_at | TIMESTAMP                | NO   |          | —                 |            |
| created_at | TIMESTAMP                | NO   |          | CURRENT_TIMESTAMP |            |

---

## PAKET 2: TRANSAKSI PENJUALAN

### Tabel: `customers`

| Kolom         | Tipe                     | Null | Key    | Default                     | Keterangan        |
| ------------- | ------------------------ | ---- | ------ | --------------------------- | ----------------- |
| id            | INT UNSIGNED             | NO   | PK     | AUTO_INCREMENT              |                   |
| nama          | VARCHAR(100)             | NO   |        | —                           |                   |
| no_telp       | VARCHAR(20)              | NO   | UNIQUE | —                           | Identifier bisnis |
| alamat        | TEXT                     | YES  |        | NULL                        |                   |
| tipe_customer | ENUM('reguler','grosir') | NO   |        | reguler                     |                   |
| status        | ENUM('aktif','nonaktif') | NO   |        | aktif                       | Soft delete       |
| created_at    | TIMESTAMP                | NO   |        | CURRENT_TIMESTAMP           |                   |
| updated_at    | TIMESTAMP                | NO   |        | CURRENT_TIMESTAMP ON UPDATE |                   |

### Tabel: `transaksi`

| Kolom             | Tipe                                             | Null | Key          | Default           | Keterangan                 |
| ----------------- | ------------------------------------------------ | ---- | ------------ | ----------------- | -------------------------- |
| id                | INT UNSIGNED                                     | NO   | PK           | AUTO_INCREMENT    |                            |
| user_id           | INT UNSIGNED                                     | NO   | FK→users     | —                 | Kasir yang memproses       |
| customer_id       | INT UNSIGNED                                     | YES  | FK→customers | NULL              | Opsional                   |
| no_transaksi      | VARCHAR(30)                                      | NO   | UNIQUE       | —                 | Format: TRX-YYYYMMDD-XXXXX |
| subtotal          | DECIMAL(10,2)                                    | NO   |              | 0.00              | Sebelum diskon             |
| diskon            | DECIMAL(10,2)                                    | NO   |              | 0.00              |                            |
| total             | DECIMAL(10,2)                                    | NO   |              | 0.00              | subtotal - diskon          |
| bayar             | DECIMAL(10,2)                                    | NO   |              | 0.00              | Uang diterima              |
| kembalian         | DECIMAL(10,2)                                    | NO   |              | 0.00              | bayar - total              |
| metode_bayar      | ENUM('tunai','transfer','qris')                  | NO   |              | tunai             |                            |
| status            | ENUM('PENDING','COMPLETED','FAILED','CANCELLED') | NO   |              | PENDING           |                            |
| tanggal_transaksi | TIMESTAMP                                        | NO   |              | CURRENT_TIMESTAMP |                            |
| created_at        | TIMESTAMP                                        | NO   |              | CURRENT_TIMESTAMP |                            |

### Tabel: `detail_transaksi` ⚠️ Diperbarui

| Kolom            | Tipe          | Null | Key              | Default        | Keterangan                               |
| ---------------- | ------------- | ---- | ---------------- | -------------- | ---------------------------------------- |
| id               | INT UNSIGNED  | NO   | PK               | AUTO_INCREMENT |                                          |
| transaksi_id     | INT UNSIGNED  | NO   | FK→transaksi     | —              |                                          |
| produk_id        | INT UNSIGNED  | NO   | FK→produk        | —              |                                          |
| produk_satuan_id | INT UNSIGNED  | YES  | FK→produk_satuan | NULL           | Satuan yang digunakan                    |
| nama_satuan      | VARCHAR(30)   | NO   |                  | pcs            | Snapshot nama satuan saat transaksi      |
| isi_satuan       | INT           | NO   |                  | 1              | Snapshot isi satuan saat transaksi       |
| qty              | INT           | NO   |                  | —              | Qty dalam satuan yang dipilih            |
| harga_satuan     | DECIMAL(10,2) | NO   |                  | —              | Snapshot harga per satuan saat transaksi |
| diskon_item      | DECIMAL(10,2) | NO   |                  | 0.00           |                                          |
| subtotal         | DECIMAL(10,2) | NO   |                  | —              | harga_satuan x qty - diskon_item         |

> Kolom `nama_satuan`, `isi_satuan`, `harga_satuan` adalah **snapshot** — disimpan agar
> riwayat tidak berubah jika admin mengedit satuan di kemudian hari.
> **Konversi stok di Consumer:** `qty_stok_berkurang = qty x isi_satuan`

### Tabel: `retur_penjualan`

| Kolom         | Tipe                                   | Null | Key          | Default           | Keterangan                 |
| ------------- | -------------------------------------- | ---- | ------------ | ----------------- | -------------------------- |
| id            | INT UNSIGNED                           | NO   | PK           | AUTO_INCREMENT    |                            |
| transaksi_id  | INT UNSIGNED                           | NO   | FK→transaksi | —                 | Harus status COMPLETED     |
| user_id       | INT UNSIGNED                           | NO   | FK→users     | —                 |                            |
| no_retur      | VARCHAR(30)                            | NO   | UNIQUE       | —                 | Format: RTJ-YYYYMMDD-XXXXX |
| alasan        | TEXT                                   | NO   |              | —                 |                            |
| total_refund  | DECIMAL(10,2)                          | NO   |              | 0.00              |                            |
| status        | ENUM('PENDING','COMPLETED','REJECTED') | NO   |              | PENDING           |                            |
| tanggal_retur | TIMESTAMP                              | NO   |              | CURRENT_TIMESTAMP |                            |
| created_at    | TIMESTAMP                              | NO   |              | CURRENT_TIMESTAMP |                            |

### Tabel: `detail_retur_penjualan`

| Kolom           | Tipe          | Null | Key                | Default        | Keterangan                                             |
| --------------- | ------------- | ---- | ------------------ | -------------- | ------------------------------------------------------ |
| id              | INT UNSIGNED  | NO   | PK                 | AUTO_INCREMENT |                                                        |
| retur_id        | INT UNSIGNED  | NO   | FK→retur_penjualan | —              |                                                        |
| produk_id       | INT UNSIGNED  | NO   | FK→produk          | —              |                                                        |
| nama_satuan     | VARCHAR(30)   | NO   |                    | pcs            | Snapshot satuan dari transaksi asal                    |
| isi_satuan      | INT           | NO   |                    | 1              | Snapshot isi satuan dari transaksi asal                |
| qty_retur       | INT           | NO   |                    | —              | Qty retur dalam satuan yang sama dengan transaksi asal |
| harga_satuan    | DECIMAL(10,2) | NO   |                    | —              | Snapshot dari detail_transaksi                         |
| subtotal_refund | DECIMAL(10,2) | NO   |                    | —              | harga_satuan x qty_retur                               |

---

## PAKET 3: INVENTORI

### Tabel: `kategori_produk`

| Kolom         | Tipe         | Null | Key    | Default           |
| ------------- | ------------ | ---- | ------ | ----------------- |
| id            | INT UNSIGNED | NO   | PK     | AUTO_INCREMENT    |
| nama_kategori | VARCHAR(100) | NO   | UNIQUE | —                 |
| deskripsi     | TEXT         | YES  |        | NULL              |
| created_at    | TIMESTAMP    | NO   |        | CURRENT_TIMESTAMP |

> Tidak ada soft delete untuk kategori.
> Jika kategori masih dipakai produk aktif: DILARANG hapus.
> Cek COUNT(\*) di tabel produk WHERE kategori_id=? sebelum DELETE.

### Tabel: `produk` ⚠️ Diperbarui dari v1

| Kolom            | Tipe                     | Null | Key                | Default                     | Keterangan                                    |
| ---------------- | ------------------------ | ---- | ------------------ | --------------------------- | --------------------------------------------- |
| id               | INT UNSIGNED             | NO   | PK                 | AUTO_INCREMENT              |                                               |
| kategori_id      | INT UNSIGNED             | NO   | FK→kategori_produk | —                           |                                               |
| kode_produk      | VARCHAR(20)              | NO   | UNIQUE             | —                           | Barcode satuan terkecil                       |
| nama_produk      | VARCHAR(200)             | NO   |                    | —                           |                                               |
| deskripsi        | TEXT                     | YES  |                    | NULL                        |                                               |
| harga_beli_dasar | DECIMAL(10,2)            | NO   |                    | 0.00                        | Harga beli per satuan_dasar                   |
| qty_stok         | INT                      | NO   |                    | 0                           | Stok dalam satuan_dasar — diperbarui Consumer |
| stok_minimum     | INT                      | NO   |                    | 0                           | Dalam satuan_dasar — trigger warning          |
| satuan_dasar     | VARCHAR(30)              | NO   |                    | pcs                         | Satuan terkecil: pcs, gram, ml, dll           |
| status           | ENUM('aktif','nonaktif') | NO   |                    | aktif                       | Soft delete                                   |
| created_at       | TIMESTAMP                | NO   |                    | CURRENT_TIMESTAMP           |                                               |
| updated_at       | TIMESTAMP                | NO   |                    | CURRENT_TIMESTAMP ON UPDATE |                                               |

> **Perubahan dari v1:** Kolom `satuan ENUM` dan `harga_jual` DIHAPUS dari tabel produk.
> Diganti dengan `satuan_dasar VARCHAR` dan `harga_beli_dasar`.
> Semua harga jual kini ada di tabel `produk_satuan`.

### Tabel: `produk_satuan` NEW

| Kolom       | Tipe          | Null | Key       | Default                     | Keterangan                              |
| ----------- | ------------- | ---- | --------- | --------------------------- | --------------------------------------- |
| id          | INT UNSIGNED  | NO   | PK        | AUTO_INCREMENT              |                                         |
| produk_id   | INT UNSIGNED  | NO   | FK→produk | —                           |                                         |
| nama_satuan | VARCHAR(30)   | NO   |           | —                           | Contoh: pcs, renteng, karton, lusin, kg |
| isi         | INT           | NO   |           | 1                           | Berapa satuan_dasar dalam 1 unit ini    |
| harga_beli  | DECIMAL(10,2) | NO   |           | 0.00                        | Harga beli per unit satuan ini          |
| harga_jual  | DECIMAL(10,2) | NO   |           | 0.00                        | Harga jual per unit satuan ini          |
| is_default  | TINYINT(1)    | NO   |           | 0                           | 1 = satuan default di form transaksi    |
| barcode     | VARCHAR(50)   | YES  | UNIQUE    | NULL                        | Barcode khusus satuan ini               |
| created_at  | TIMESTAMP     | NO   |           | CURRENT_TIMESTAMP           |                                         |
| updated_at  | TIMESTAMP     | NO   |           | CURRENT_TIMESTAMP ON UPDATE |                                         |

> **Unique constraint:** `UNIQUE KEY uq_produk_satuan (produk_id, nama_satuan)`
> Satu produk tidak boleh punya dua entri dengan nama satuan yang sama.

**Contoh data — Kopi Kapal Api 165g:**

```
produk: id=1, satuan_dasar='pcs', qty_stok=960, harga_beli_dasar=3000

produk_satuan:
 id | produk_id | nama_satuan | isi |  harga_beli | harga_jual | is_default
  1 |     1     |    pcs      |  1  |    3.000    |   3.500    |     1      <- default
  2 |     1     |  renteng    | 10  |   28.000    |  31.500    |     0
  3 |     1     |  karton     | 96  |  252.000    | 280.000    |     0

Stok tersedia per satuan:
  pcs     : 960 / 1  = 960 pcs
  renteng : 960 / 10 =  96 renteng
  karton  : 960 / 96 =  10 karton

Harga efektif per pcs:
  pcs     : 3.500 / 1  = 3.500/pcs  (baseline)
  renteng : 31.500 / 10 = 3.150/pcs  (hemat 10%)
  karton  : 280.000 / 96 = 2.916/pcs  (hemat 16%)
```

### Tabel: `suppliers`

| Kolom         | Tipe                     | Null | Key    | Default                     |
| ------------- | ------------------------ | ---- | ------ | --------------------------- |
| id            | INT UNSIGNED             | NO   | PK     | AUTO_INCREMENT              |
| nama_supplier | VARCHAR(150)             | NO   |        | —                           |
| kontak_person | VARCHAR(100)             | YES  |        | NULL                        |
| no_telp       | VARCHAR(20)              | NO   | UNIQUE | —                           |
| alamat        | TEXT                     | YES  |        | NULL                        |
| status        | ENUM('aktif','nonaktif') | NO   |        | aktif                       |
| created_at    | TIMESTAMP                | NO   |        | CURRENT_TIMESTAMP           |
| updated_at    | TIMESTAMP                | NO   |        | CURRENT_TIMESTAMP ON UPDATE |

### Tabel: `produk_supplier` (Junction N:M)

| Kolom        | Tipe          | Null | Key          | Default           | Keterangan                                |
| ------------ | ------------- | ---- | ------------ | ----------------- | ----------------------------------------- |
| id           | INT UNSIGNED  | NO   | PK           | AUTO_INCREMENT    |                                           |
| produk_id    | INT UNSIGNED  | NO   | FK→produk    | —                 |                                           |
| supplier_id  | INT UNSIGNED  | NO   | FK→suppliers | —                 |                                           |
| harga_suplai | DECIMAL(10,2) | NO   |              | 0.00              | Harga beli dari supplier per satuan_dasar |
| is_primary   | TINYINT(1)    | NO   |              | 0                 | 1 = supplier utama produk ini             |
| created_at   | TIMESTAMP     | NO   |              | CURRENT_TIMESTAMP |                                           |

---

## PAKET 4: PEMBELIAN (RESTOCK)

### Tabel: `pembelian`

| Kolom             | Tipe                                    | Null | Key          | Default                     | Keterangan                |
| ----------------- | --------------------------------------- | ---- | ------------ | --------------------------- | ------------------------- |
| id                | INT UNSIGNED                            | NO   | PK           | AUTO_INCREMENT              |                           |
| user_id           | INT UNSIGNED                            | NO   | FK→users     | —                           | Admin Gudang              |
| supplier_id       | INT UNSIGNED                            | NO   | FK→suppliers | —                           |                           |
| no_pembelian      | VARCHAR(30)                             | NO   | UNIQUE       | —                           | Format: PO-YYYYMMDD-XXXXX |
| total             | DECIMAL(10,2)                           | NO   |              | 0.00                        |                           |
| status            | ENUM('PENDING','COMPLETED','CANCELLED') | NO   |              | PENDING                     |                           |
| tanggal_pembelian | DATE                                    | NO   |              | —                           | DATE bukan TIMESTAMP      |
| created_at        | TIMESTAMP                               | NO   |              | CURRENT_TIMESTAMP           |                           |
| updated_at        | TIMESTAMP                               | NO   |              | CURRENT_TIMESTAMP ON UPDATE |                           |

### Tabel: `detail_pembelian` ⚠️ Diperbarui

| Kolom             | Tipe          | Null | Key              | Default        | Keterangan                     |
| ----------------- | ------------- | ---- | ---------------- | -------------- | ------------------------------ |
| id                | INT UNSIGNED  | NO   | PK               | AUTO_INCREMENT |                                |
| pembelian_id      | INT UNSIGNED  | NO   | FK→pembelian     | —              |                                |
| produk_id         | INT UNSIGNED  | NO   | FK→produk        | —              |                                |
| produk_satuan_id  | INT UNSIGNED  | YES  | FK→produk_satuan | NULL           | Satuan pembelian               |
| nama_satuan       | VARCHAR(30)   | NO   |                  | pcs            | Snapshot nama satuan           |
| isi_satuan        | INT           | NO   |                  | 1              | Snapshot isi satuan            |
| qty               | INT           | NO   |                  | —              | Qty dalam satuan yang dipilih  |
| harga_beli_satuan | DECIMAL(10,2) | NO   |                  | —              | Harga beli per unit satuan ini |
| subtotal          | DECIMAL(10,2) | NO   |                  | —              | harga_beli_satuan x qty        |

> **Konversi stok di Consumer:** `qty_stok_bertambah = qty x isi_satuan`

### Tabel: `retur_pembelian`

| Kolom         | Tipe                                   | Null | Key          | Default           | Keterangan                 |
| ------------- | -------------------------------------- | ---- | ------------ | ----------------- | -------------------------- |
| id            | INT UNSIGNED                           | NO   | PK           | AUTO_INCREMENT    |                            |
| pembelian_id  | INT UNSIGNED                           | NO   | FK→pembelian | —                 | Harus status COMPLETED     |
| user_id       | INT UNSIGNED                           | NO   | FK→users     | —                 | Admin Gudang               |
| no_retur      | VARCHAR(30)                            | NO   | UNIQUE       | —                 | Format: RTP-YYYYMMDD-XXXXX |
| alasan        | TEXT                                   | NO   |              | —                 |                            |
| total_kredit  | DECIMAL(10,2)                          | NO   |              | 0.00              | Kredit ke supplier         |
| status        | ENUM('PENDING','COMPLETED','REJECTED') | NO   |              | PENDING           |                            |
| tanggal_retur | TIMESTAMP                              | NO   |              | CURRENT_TIMESTAMP |                            |
| created_at    | TIMESTAMP                              | NO   |              | CURRENT_TIMESTAMP |                            |

### Tabel: `detail_retur_pembelian`

| Kolom              | Tipe          | Null | Key                | Default        | Keterangan                       |
| ------------------ | ------------- | ---- | ------------------ | -------------- | -------------------------------- |
| id                 | INT UNSIGNED  | NO   | PK                 | AUTO_INCREMENT |                                  |
| retur_pembelian_id | INT UNSIGNED  | NO   | FK→retur_pembelian | —              |                                  |
| produk_id          | INT UNSIGNED  | NO   | FK→produk          | —              |                                  |
| nama_satuan        | VARCHAR(30)   | NO   |                    | pcs            | Snapshot dari detail_pembelian   |
| isi_satuan         | INT           | NO   |                    | 1              | Snapshot dari detail_pembelian   |
| qty_retur          | INT           | NO   |                    | —              | Qty retur dalam satuan yang sama |
| harga_satuan       | DECIMAL(10,2) | NO   |                    | —              | Snapshot dari detail_pembelian   |
| subtotal_kredit    | DECIMAL(10,2) | NO   |                    | —              | harga_satuan x qty_retur         |

---

## Ringkasan Relasi

| Tabel A           | Tabel B                | Relasi  | Keterangan                                  |
| ----------------- | ---------------------- | ------- | ------------------------------------------- |
| roles             | users                  | 1:N     | Satu role dimiliki banyak user              |
| users             | sessions               | 1:N     | Multi-device session                        |
| users             | transaksi              | 1:N     | Kasir memproses banyak transaksi            |
| users             | retur_penjualan        | 1:N     |                                             |
| users             | pembelian              | 1:N     | Admin gudang buat banyak PO                 |
| users             | retur_pembelian        | 1:N     |                                             |
| customers         | transaksi              | 1:N     |                                             |
| kategori_produk   | produk                 | 1:N     |                                             |
| **produk**        | **produk_satuan**      | **1:N** | Satu produk punya banyak satuan dan harga   |
| produk            | suppliers              | N:M     | Via produk_supplier                         |
| **produk_satuan** | **detail_transaksi**   | **1:N** | Satuan yang dipakai di tiap baris transaksi |
| **produk_satuan** | **detail_pembelian**   | **1:N** | Satuan yang dipakai di tiap baris PO        |
| transaksi         | detail_transaksi       | 1:N     | Composition                                 |
| transaksi         | retur_penjualan        | 1:N     | Retur parsial diizinkan                     |
| retur_penjualan   | detail_retur_penjualan | 1:N     | Composition                                 |
| suppliers         | pembelian              | 1:N     |                                             |
| pembelian         | detail_pembelian       | 1:N     | Composition                                 |
| pembelian         | retur_pembelian        | 1:N     |                                             |
| retur_pembelian   | detail_retur_pembelian | 1:N     | Composition                                 |

---

## Aturan Kritis Consumer Kafka (Wajib Diikuti)

```
SEMUA update stok harus menggunakan konversi satuan:

qty_berkurang (transaksi jual)   = item.qty x item.isi_satuan
qty_bertambah (pembelian)        = item.qty x item.isi_satuan
qty_bertambah (retur penjualan)  = item.qty_retur x item.isi_satuan
qty_berkurang (retur pembelian)  = item.qty_retur x item.isi_satuan

Contoh:
  Kasir jual 2 karton kopi (isi=96)  -> stok berkurang: 2 x 96 = 192 pcs
  Admin beli 5 karton kopi (isi=96)  -> stok bertambah: 5 x 96 = 480 pcs
  Retur 1 renteng kopi (isi=10)      -> stok bertambah: 1 x 10 = 10 pcs
```
