# SEQUENCE_FLOWS.md — Kontrak API & Urutan Pesan
> **Versi 2.0** — Update: Multi-unit pricing di transaksi dan pembelian

## Konvensi Response
- `sync`   → HTTP request-response (pengirim tunggu)
- `async`  → Kafka/WebSocket (pengirim tidak tunggu)
- `202`    → Diterima, diproses Kafka (async)
- `200`    → Selesai sinkron
- `201`    → Data berhasil dibuat

---

## SD-01: Login
```
POST /api/auth/login
Body: { username: string, password: string }

Response 200: {
  data: {
    token: string,
    role: { id, nama_role },
    user: { id, nama, username }
  }
}
Response 422: { message, errors: { field: [pesan] } }
Response 401: { message: "Username atau password salah" }
```

## SD-02: Logout
```
POST /api/auth/logout
Header: Authorization: Bearer {token}

Response 200: { message: "Logout berhasil" }
Response 401: { message: "Token tidak valid" }
```

---

## SD-03: Input Transaksi Penjualan
```
[per item — loop saat kasir scan/input]
GET /api/kasir/produk/{kode}/stok
Response 200: (lihat SD-07 untuk format lengkap)

[submit transaksi]
POST /api/kasir/transaksi
Body: {
  items: [
    {
      produk_id: int,
      produk_satuan_id: int,
      nama_satuan: string,      <- dari produk_satuan yang dipilih
      isi_satuan: int,          <- dari produk_satuan yang dipilih
      qty: int,                 <- qty dalam satuan yang dipilih
      harga_satuan: decimal,    <- dari produk_satuan.harga_jual
      diskon_item: decimal      <- opsional, default 0
    }
  ],
  customer_id: int|null,
  subtotal: decimal,
  diskon: decimal,
  total: decimal,
  bayar: decimal,
  kembalian: decimal,
  metode_bayar: 'tunai'|'transfer'|'qris'
}

Response 202: {
  id_transaksi: int,
  no_transaksi: string,
  status: "PENDING",
  message: "Transaksi sedang diproses"
}
Response 422: { message, errors }

[async via Kafka Consumer -> WebSocket]
WS PUSH sukses: {
  event: "transaction.completed",
  transaksiId: int,
  status: "COMPLETED",
  message: "Transaksi berhasil diproses"
}

WS PUSH gagal: {
  event: "transaction.failed",
  transaksiId: int,
  status: "FAILED",
  message: "Stok Kopi Kapal Api tidak mencukupi (tersedia: 5 renteng, dibutuhkan: 10 renteng)"
}

WS PUSH stok (setelah completed): {
  event: "stock.updated",
  updates: [ { produkId: int, stokBaru: int } ]
}
```

> **Catatan:** `stokBaru` selalu dalam satuan_dasar.
> Frontend menghitung stok per satuan: `floor(stokBaru / isi_satuan)`

## SD-04: Cetak Struk
```
GET /api/kasir/transaksi/{id}/struk
Header: Authorization: Bearer {token}

Response 200: {
  data: {
    no_transaksi: string,
    tanggal: string,
    kasir: string,
    customer: string|null,
    items: [
      {
        nama_produk: string,
        nama_satuan: string,    <- misal: "karton"
        qty: int,               <- misal: 2
        harga_satuan: decimal,  <- misal: 280000
        subtotal: decimal       <- misal: 560000
      }
    ],
    subtotal, diskon, total, bayar, kembalian,
    metode_bayar: string
  }
}
Response 403: { message: "Akses ditolak" }
Response 404: { message: "Transaksi tidak ditemukan" }
```

## SD-05: Retur Penjualan
```
[verifikasi transaksi asal]
GET /api/kasir/transaksi/{id}
Response 200: {
  data: {
    id, no_transaksi, status, tanggal_transaksi,
    items: [
      {
        produk_id, nama_produk,
        nama_satuan, isi_satuan,   <- tampilkan ke kasir
        qty, harga_satuan, subtotal
      }
    ]
  }
}
Response 400: { message: "Transaksi tidak dapat diretur" }

[submit retur]
POST /api/kasir/retur-penjualan
Body: {
  transaksi_id: int,
  items_retur: [
    {
      produk_id: int,
      nama_satuan: string,    <- harus sama dengan satuan di transaksi asal
      isi_satuan: int,        <- snapshot dari detail_transaksi asal
      qty_retur: int,
      harga_satuan: decimal   <- snapshot dari detail_transaksi asal
    }
  ],
  alasan: string
}

Response 202: { id_retur, no_retur, status: "PENDING" }
Response 422: { message, errors }

[async via Kafka -> WebSocket]
WS PUSH: {
  event: "return.sale.completed",
  returId: int,
  status: "COMPLETED",
  nilaiRefund: decimal,
  message: "Retur berhasil. Refund: Rp 31.500"
}
WS PUSH: { event: "stock.updated", updates: [...] }
```

## SD-06: Lihat Stok Real-Time
```
[initial load]
GET /api/kasir/stok?kategori_id=&nama=&page=1&limit=20

Response 200: {
  data: {
    stok: [
      {
        id, kode_produk, nama_produk, kategori,
        satuan_dasar: string,
        qty_stok: int,         <- dalam satuan_dasar
        stok_minimum: int,
        is_rendah: bool,
        satuan_tersedia: [
          {
            id, nama_satuan, isi,
            harga_jual: decimal,
            stok_dalam_satuan: int   <- floor(qty_stok / isi)
          }
        ]
      }
    ],
    pagination: { current_page, total, per_page }
  }
}

[WebSocket real-time]
WS CONNECT -> ws://localhost/ws (subscribe channel: 'stok-global')
WS PUSH: {
  event: "stock.updated",
  updates: [ { produkId: int, stokBaru: int } ]   <- stokBaru dalam satuan_dasar
}
Frontend hitung stok per satuan: floor(stokBaru / isi)
```

## SD-07: Cek Stok (saat Input Transaksi)
```
GET /api/kasir/produk/{kode}/stok

Response 200: {
  data: {
    id: int,
    kode_produk: string,
    nama_produk: string,
    satuan_dasar: string,
    qty_stok: int,           <- total stok dalam satuan_dasar
    stok_minimum: int,
    satuan_tersedia: [
      {
        id: int,
        nama_satuan: string,       <- "pcs", "renteng", "karton"
        isi: int,                  <- 1, 10, 96
        harga_jual: decimal,       <- harga per satuan ini
        stok_dalam_satuan: int,    <- floor(qty_stok / isi)
        hemat_persen: float,       <- 0, 10.0, 16.4 (vs satuan default)
        is_default: bool
      }
    ]
  }
}

Response 404: { message: "Produk tidak ditemukan atau tidak aktif" }

Frontend menentukan warning berdasarkan stok_dalam_satuan satuan default:
  stok_dalam_satuan = 0          -> "Stok Habis", blokir input
  stok_dalam_satuan <= minimum   -> "Stok Menipis", tetap izinkan
  stok_dalam_satuan > minimum    -> normal
```

## SD-08: CRUD Produk
```
GET  /api/gudang/produk?page=&limit=&kategori_id=&nama=&status=

Response 200: {
  data: [
    {
      id, kode_produk, nama_produk, kategori,
      satuan_dasar, qty_stok, stok_minimum, status,
      satuan_tersedia: [ {id, nama_satuan, isi, harga_jual, is_default} ]
    }
  ],
  pagination: { ... }
}

POST /api/gudang/produk
Body: {
  kode_produk: string,
  nama_produk: string,
  kategori_id: int,
  deskripsi: string|null,
  harga_beli_dasar: decimal,
  qty_stok: int,
  stok_minimum: int,
  satuan_dasar: string,        <- misal: "pcs"
  satuan_list: [               <- minimal 1 satuan (biasanya sama dengan satuan_dasar)
    {
      nama_satuan: string,
      isi: int,
      harga_beli: decimal,
      harga_jual: decimal,
      is_default: bool,
      barcode: string|null
    }
  ]
}
Response 201: { data: produk_dengan_satuan, message: "Produk berhasil ditambahkan" }
Response 422: { message, errors }

GET  /api/gudang/produk/{id}
PUT  /api/gudang/produk/{id}   <- body sama dengan POST tapi semua field opsional
DEL  /api/gudang/produk/{id}

-- CRUD Satuan Produk (sub-resource)
GET    /api/gudang/produk/{id}/satuan
POST   /api/gudang/produk/{id}/satuan
Body: { nama_satuan, isi, harga_beli, harga_jual, is_default, barcode? }
PUT    /api/gudang/produk/{id}/satuan/{satuan_id}
DELETE /api/gudang/produk/{id}/satuan/{satuan_id}
```

## SD-09: Pembelian / Restock
```
POST /api/gudang/pembelian
Body: {
  supplier_id: int,
  tanggal_pembelian: "YYYY-MM-DD",
  items: [
    {
      produk_id: int,
      produk_satuan_id: int,      <- pilih satuan pembelian
      nama_satuan: string,
      isi_satuan: int,
      qty: int,
      harga_beli_satuan: decimal  <- harga beli per satuan yang dipilih
    }
  ]
}

Response 202: { id_pembelian, no_pembelian, status: "PENDING" }
Response 422: { message, errors }

[async via Kafka -> WebSocket]
WS PUSH: {
  event: "purchase.completed",
  pembelianId: int,
  status: "COMPLETED",
  message: "Restock berhasil. Stok sudah diperbarui.",
  stokUpdates: [ { produkId, stokBaru } ]   <- dalam satuan_dasar
}
WS PUSH: { event: "stock.updated", updates: [...] }
```

## SD-10: Retur Pembelian
```
[verifikasi PO asal]
GET /api/gudang/pembelian/{id}
Response 200: {
  data: {
    id, no_pembelian, status, tanggal_pembelian,
    supplier: { id, nama_supplier },
    items: [
      {
        produk_id, nama_produk,
        nama_satuan, isi_satuan,
        qty, harga_beli_satuan, subtotal
      }
    ]
  }
}
Response 400: { message: "Pembelian tidak dapat diretur" }

[submit retur]
POST /api/gudang/retur-pembelian
Body: {
  pembelian_id: int,
  items_retur: [
    {
      produk_id: int,
      nama_satuan: string,    <- sama dengan satuan di PO asal
      isi_satuan: int,        <- snapshot dari detail_pembelian
      qty_retur: int,
      harga_satuan: decimal   <- snapshot dari detail_pembelian
    }
  ],
  alasan: string
}

Response 202: { id_retur, no_retur, status: "PENDING" }

[async]
WS PUSH: {
  event: "return.purchase.completed",
  returId, status: "COMPLETED",
  nilaiKredit: decimal,
  message: "Retur pembelian berhasil. Kredit: Rp 252.000"
}
```

## SD-11: CRUD Customer
```
GET  /api/kasir/customer?page=&search=&status=
POST /api/kasir/customer
Body: { nama, no_telp, alamat?, tipe_customer }
GET  /api/kasir/customer/{id}
PUT  /api/kasir/customer/{id}
DEL  /api/kasir/customer/{id}

Response 422 (duplikat): { errors: { no_telp: ["Nomor telepon sudah terdaftar"] } }
```

## SD-12: Riwayat Belanja Customer
```
GET /api/kasir/customer?search={keyword}
GET /api/kasir/customer/{id}/riwayat?page=1&limit=20

Response 200: {
  data: {
    riwayat: [
      {
        id, no_transaksi, tanggal, total, metode_bayar, status,
        jumlah_item: int
      }
    ],
    total_belanja: decimal,
    frekuensi: int,
    total_page: int
  }
}

GET /api/kasir/transaksi/{id}   <- detail + items dengan nama_satuan
```

## SD-13: CRUD Supplier
```
GET  /api/gudang/supplier?page=&search=
POST /api/gudang/supplier
Body: { nama_supplier, kontak_person?, no_telp, alamat? }
GET  /api/gudang/supplier/{id}
PUT  /api/gudang/supplier/{id}
DEL  /api/gudang/supplier/{id}
```

## SD-14: Laporan
```
GET /api/owner/laporan/{jenis}?dari=YYYY-MM-DD&sampai=YYYY-MM-DD&kategori_id=
jenis: penjualan | stok | pembelian

Response 200: {
  data: { ... },
  metrik: {
    omzet: decimal,
    profit_kotor: decimal,
    perputaran_stok: float
  }
}
Response 403: { message: "Akses hanya untuk pemilik toko" }

GET /api/owner/laporan/{jenis}/export?format=pdf|excel&dari=&sampai=
Response 200: file binary
```

## SD-15: Manajemen User
```
GET  /api/owner/user
POST /api/owner/user
Body: { nama, username, password, role_id }
PUT  /api/owner/user/{id}
PATCH /api/owner/user/{id}/reset-password
Body: { password_baru: string }
PATCH /api/owner/user/{id}/nonaktifkan

Response 422 (duplikat): { errors: { username: ["Username sudah digunakan"] } }
```

---

## Payload Kafka Events (Referensi Consumer)

```javascript
// transaction.created
{
  eventType: "transaction.created",
  transaksiId: 123,
  items: [
    {
      produkId: 1,
      produkSatuanId: 3,
      qty: 2,
      isiSatuan: 96,          // WAJIB — untuk konversi stok
      hargaSatuan: 280000,
      diskonItem: 0
    }
  ],
  customerId: null,
  userId: 2,
  total: 560000,
  metodeBayar: "tunai",
  timestamp: "2025-01-01T10:00:00.000Z"
}

// purchase.created
{
  eventType: "purchase.created",
  pembelianId: 45,
  supplierId: 3,
  items: [
    {
      produkId: 1,
      produkSatuanId: 3,
      qty: 5,
      isiSatuan: 96,          // WAJIB — untuk konversi stok
      hargaBeliSatuan: 252000
    }
  ],
  userId: 5,
  timestamp: "2025-01-01T10:00:00.000Z"
}

// sale.return.requested
{
  eventType: "sale.return.requested",
  returId: 10,
  transaksiId: 123,
  items: [
    {
      produkId: 1,
      qtyRetur: 1,
      isiSatuan: 96,          // WAJIB — untuk konversi stok
      hargaSatuan: 280000
    }
  ],
  userId: 2,
  timestamp: "..."
}

// purchase.return.requested
{
  eventType: "purchase.return.requested",
  returId: 5,
  pembelianId: 45,
  items: [
    {
      produkId: 1,
      qtyRetur: 1,
      isiSatuan: 96,          // WAJIB — untuk konversi stok
      hargaSatuan: 252000
    }
  ],
  userId: 5,
  timestamp: "..."
}
```

> **ATURAN CRITICAL di Consumer:** Selalu gunakan `isiSatuan` untuk konversi:
> `qty_stok_berubah = qty (atau qtyRetur) x isiSatuan`
