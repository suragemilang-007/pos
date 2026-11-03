# ERD.md — Struktur Database Sistem POS Grosir
> **Versi 4.0** — Tambah: Hutang Dagang (pembayaran_pembelian) + Penyesuaian Stok

## Konvensi Global
- Harga & nilai keuangan: `DECIMAL(10,2)` — JANGAN pakai FLOAT
- Primary key: `INT UNSIGNED AUTO_INCREMENT`
- Timestamps: `TIMESTAMP` (bukan DATETIME)
- Status: selalu `ENUM`, bukan `VARCHAR`
- Soft delete: via kolom `status ENUM('aktif','nonaktif')`
- Nama tabel: `snake_case` plural | Nama kolom: `snake_case`
- **Stok selalu dalam satuan_dasar** — konversi: `qty x isi_satuan`

---

## PAKET 1: AUTENTIKASI

### Tabel: `roles`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | TINYINT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| nama_role | VARCHAR(50) | NO | | — | kasir, admin_gudang, owner |
| permissions | JSON | YES | | NULL | |
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
| no_telp | VARCHAR(20) | NO | UNIQUE | — | |
| alamat | TEXT | YES | | NULL | |
| tipe_customer | ENUM('reguler','grosir') | NO | | reguler | |
| status | ENUM('aktif','nonaktif') | NO | | aktif | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP ON UPDATE | |

### Tabel: `transaksi`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| user_id | INT UNSIGNED | NO | FK→users | — | Kasir |
| customer_id | INT UNSIGNED | YES | FK→customers | NULL | |
| no_transaksi | VARCHAR(30) | NO | UNIQUE | — | TRX-YYYYMMDD-XXXXX |
| subtotal | DECIMAL(10,2) | NO | | 0.00 | |
| total | DECIMAL(10,2) | NO | | 0.00 | |
| bayar | DECIMAL(10,2) | NO | | 0.00 | |
| kembalian | DECIMAL(10,2) | NO | | 0.00 | |
| metode_bayar | ENUM('tunai','transfer','qris') | NO | | tunai | |
| status | ENUM('PENDING','COMPLETED','FAILED','CANCELLED','VOIDED') | NO | | PENDING | |
| tanggal_transaksi | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

### Tabel: `detail_transaksi`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| transaksi_id | INT UNSIGNED | NO | FK→transaksi | — | |
| produk_id | INT UNSIGNED | NO | FK→produk | — | |
| produk_satuan_id | INT UNSIGNED | YES | FK→produk_satuan | NULL | |
| nama_satuan | VARCHAR(30) | NO | | pcs | Snapshot |
| isi_satuan | INT | NO | | 1 | Snapshot |
| qty | INT | NO | | — | |
| harga_satuan | DECIMAL(10,2) | NO | | — | Snapshot |
| subtotal | DECIMAL(10,2) | NO | | — | |

### Tabel: `retur_penjualan`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| transaksi_id | INT UNSIGNED | NO | FK→transaksi | — | |
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

### Tabel: `void_transaksi`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| transaksi_id | INT UNSIGNED | NO | FK→transaksi, UNIQUE | — | |
| transaksi_baru_id | INT UNSIGNED | YES | FK→transaksi | NULL | |
| user_id | INT UNSIGNED | NO | FK→users | — | |
| alasan | ENUM('kesalahan_input','barang_rusak_saat_transaksi','permintaan_pembeli','lainnya') | NO | | — | |
| keterangan | TEXT | YES | | NULL | |
| status | ENUM('PENDING','COMPLETED','REJECTED') | NO | | PENDING | |
| batas_waktu | TIMESTAMP | NO | | — | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

---

## PAKET 3: INVENTORI

### Tabel: `kategori_produk`
| Kolom | Tipe | Null | Key | Default |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT |
| nama_kategori | VARCHAR(100) | NO | UNIQUE | — |
| deskripsi | TEXT | YES | | NULL |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP |

### Tabel: `produk`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| kategori_id | INT UNSIGNED | NO | FK→kategori_produk | — | |
| kode_produk | VARCHAR(20) | NO | UNIQUE | — | |
| nama_produk | VARCHAR(200) | NO | | — | |
| deskripsi | TEXT | YES | | NULL | |
| harga_beli_dasar | DECIMAL(10,2) | NO | | 0.00 | Per satuan_dasar |
| qty_stok | INT | NO | | 0 | Dalam satuan_dasar |
| stok_minimum | INT | NO | | 0 | |
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
| is_default | TINYINT(1) | NO | | 0 | |
| barcode | VARCHAR(50) | YES | UNIQUE | NULL | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP ON UPDATE | |

> UNIQUE KEY: `uq_produk_satuan (produk_id, nama_satuan)`

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
| is_primary | TINYINT(1) | NO | | 0 | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

### Tabel: `penyesuaian_stok` NEW
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| produk_id | INT UNSIGNED | NO | FK→produk | — | |
| user_id | INT UNSIGNED | NO | FK→users | — | Admin yang membuat |
| tipe | ENUM('barang_rusak','barang_hilang','barang_expired','koreksi_plus','koreksi_minus') | NO | | — | |
| qty_satuan_dasar | INT | NO | | — | Selalu positif, arah ditentukan tipe |
| keterangan | TEXT | NO | | — | Wajib diisi — alasan penyesuaian |
| status | ENUM('PENDING','COMPLETED') | NO | | PENDING | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

> **Aturan arah stok berdasarkan tipe:**
> - `koreksi_plus` → qty_stok **BERTAMBAH**
> - `barang_rusak`, `barang_hilang`, `barang_expired`, `koreksi_minus` → qty_stok **BERKURANG**
>
> **Validasi sebelum INSERT:** Jika tipe kurang stok, pastikan `qty_satuan_dasar <= qty_stok` saat ini untuk mencegah stok negatif.

---

## PAKET 4: PEMBELIAN (RESTOCK) ⚠️ Diperbarui v4

### Tabel: `pembelian` ⚠️ Diperbarui
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| user_id | INT UNSIGNED | NO | FK→users | — | Admin Gudang |
| supplier_id | INT UNSIGNED | NO | FK→suppliers | — | |
| no_pembelian | VARCHAR(30) | NO | UNIQUE | — | PO-YYYYMMDD-XXXXX |
| total | DECIMAL(10,2) | NO | | 0.00 | Total nilai PO |
| tipe_pembayaran | ENUM('cash','kredit') | NO | | cash | **BARU** |
| jumlah_dp | DECIMAL(10,2) | NO | | 0.00 | **BARU** DP saat terima barang |
| sisa_hutang | DECIMAL(10,2) | NO | | 0.00 | **BARU** total - jumlah_dp |
| tanggal_jatuh_tempo | DATE | YES | | NULL | **BARU** wajib jika kredit |
| status_pembayaran | ENUM('lunas','belum_lunas','sebagian') | NO | | lunas | **BARU** |
| catatan_pembayaran | TEXT | YES | | NULL | **BARU** |
| status | ENUM('PENDING','COMPLETED','CANCELLED') | NO | | PENDING | Status proses Kafka |
| tanggal_pembelian | DATE | NO | | — | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP ON UPDATE | |

> **Aturan status_pembayaran:**
> - Cash: `status_pembayaran='lunas'`, `sisa_hutang=0`
> - Kredit full (dp=0): `status_pembayaran='belum_lunas'`, `sisa_hutang=total`
> - Kredit dengan dp: `status_pembayaran='sebagian'`, `sisa_hutang=total-jumlah_dp`
> - Setelah lunas dibayar: `status_pembayaran='lunas'`, `sisa_hutang=0`

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

### Tabel: `pembayaran_pembelian` NEW
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| pembelian_id | INT UNSIGNED | NO | FK→pembelian | — | PO yang dibayar |
| user_id | INT UNSIGNED | NO | FK→users | — | Yang mencatat pembayaran |
| jumlah | DECIMAL(10,2) | NO | | — | Jumlah yang dibayarkan |
| metode | ENUM('cash','transfer','cek','giro') | NO | | cash | |
| tanggal_bayar | DATE | NO | | — | Tanggal pembayaran |
| catatan | TEXT | YES | | NULL | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |

> **Satu PO bisa punya banyak record pembayaran** (cicilan bertahap).
> Total lunas = `SUM(pembayaran_pembelian.jumlah) WHERE pembelian_id=?`

### Tabel: `retur_pembelian`
| Kolom | Tipe | Null | Key | Default | Keterangan |
|---|---|---|---|---|---|
| id | INT UNSIGNED | NO | PK | AUTO_INCREMENT | |
| pembelian_id | INT UNSIGNED | NO | FK→pembelian | — | |
| user_id | INT UNSIGNED | NO | FK→users | — | |
| no_retur | VARCHAR(30) | NO | UNIQUE | — | RTP-YYYYMMDD-XXXXX |
| alasan | TEXT | NO | | — | |
| total_kredit | DECIMAL(10,2) | NO | | 0.00 | Nilai kredit ke supplier |
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
| users | void_transaksi | 1:N | |
| users | pembelian | 1:N | |
| users | retur_pembelian | 1:N | |
| users | pembayaran_pembelian | 1:N | Yang mencatat pembayaran |
| users | penyesuaian_stok | 1:N | Yang membuat penyesuaian |
| customers | transaksi | 1:N | |
| kategori_produk | produk | 1:N | |
| produk | produk_satuan | 1:N | |
| produk | suppliers | N:M | Via produk_supplier |
| produk | penyesuaian_stok | 1:N | Satu produk bisa banyak penyesuaian |
| produk_satuan | detail_transaksi | 1:N | |
| produk_satuan | detail_pembelian | 1:N | |
| transaksi | detail_transaksi | 1:N | |
| transaksi | retur_penjualan | 1:N | |
| transaksi | void_transaksi | 1:1 | |
| retur_penjualan | detail_retur_penjualan | 1:N | |
| suppliers | pembelian | 1:N | |
| **pembelian** | **pembayaran_pembelian** | **1:N** | Satu PO bisa banyak cicilan |
| pembelian | detail_pembelian | 1:N | |
| pembelian | retur_pembelian | 1:N | |
| retur_pembelian | detail_retur_pembelian | 1:N | |

---

## Aturan Kritis Consumer Kafka

```
KONVERSI SATUAN (semua operasi stok):
  qty_berubah = qty x isi_satuan

PENYESUAIAN STOK:
  koreksi_plus         → UPDATE produk SET qty_stok = qty_stok + qty_satuan_dasar
  barang_rusak/hilang/
  expired/koreksi_minus → UPDATE produk SET qty_stok = qty_stok - qty_satuan_dasar

PEMBELIAN (setelah status COMPLETED):
  - Jika cash: INSERT pembayaran_pembelian (jumlah=total)
  - Jika kredit + dp>0: INSERT pembayaran_pembelian (jumlah=dp)
  - Jika kredit + dp=0: tidak INSERT pembayaran_pembelian (belum ada bayaran)

ENV:
  VOID_TIME_LIMIT_MINUTES=30
  HUTANG_REMINDER_DAYS=3   ← notifikasi H-N sebelum jatuh tempo
```
