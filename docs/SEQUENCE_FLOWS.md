# SEQUENCE_FLOWS.md — Kontrak API & Urutan Pesan
> **Versi 3.0** — Tambah: SD-17 Void + SD-18 Promo. Update: SD-03, SD-07

## Konvensi
- `202` → async via Kafka | `200` → sinkron selesai | `201` → data dibuat

---

## SD-01: Login
```
POST /api/auth/login
Body: { username, password }
← 200: { data: { token, role: {id, nama_role}, user: {id, nama, username} } }
← 422: { message, errors }
← 401: { message: "Username atau password salah" }
```

## SD-02: Logout
```
POST /api/auth/logout
Header: Authorization: Bearer {token}
← 200: { message: "Logout berhasil" }
← 401: { message: "Token tidak valid" }
```

## SD-03: Input Transaksi Penjualan ⚠️ Update v3
```
POST /api/kasir/transaksi
Body: {
  items: [
    {
      produk_id: int,
      produk_satuan_id: int,
      nama_satuan: string,
      isi_satuan: int,
      qty: int,
      harga_satuan: decimal,  <- 0 jika item bonus
      diskon_item: decimal,
      promo_id: int|null,     <- ID promo yang diterapkan (untuk audit)
      is_bonus: bool          <- true jika item gratis dari promo
    }
  ],
  customer_id: int|null,
  subtotal, diskon, total, bayar, kembalian,
  metode_bayar: 'tunai'|'transfer'|'qris'
}
← 202: { id_transaksi, no_transaksi, status: "PENDING" }
← 422: { message, errors }

[async via Kafka → WebSocket]
← WS PUSH: { event: "transaction.completed"|"transaction.failed",
              transaksiId, status, message }
← WS PUSH: { event: "stock.updated", updates: [{produkId, stokBaru}] }
```

> **Catatan item bonus:** Item dengan `is_bonus=true` memiliki `harga_satuan=0`
> dan `promo_id` terisi. Consumer TETAP mengurangi stok untuk item bonus
> (barang fisik tetap keluar dari gudang).

## SD-04: Cetak Struk
```
GET /api/kasir/transaksi/{id}/struk
← 200: { data: { no_transaksi, tanggal, kasir, customer?,
                 items: [{nama_produk, nama_satuan, qty, harga_satuan,
                          subtotal, is_bonus, nama_promo?}],
                 subtotal, diskon, total, bayar, kembalian, metode_bayar } }
← 403 | 404
```

## SD-05: Retur Penjualan
```
GET /api/kasir/transaksi/{id}
← 400: { message: "Transaksi VOIDED tidak dapat diretur" }  <- tambahan v3

POST /api/kasir/retur-penjualan
Body: { transaksi_id, items_retur: [{produk_id, nama_satuan,
        isi_satuan, qty_retur, harga_satuan}], alasan }
← 202: { id_retur, no_retur, status: "PENDING" }

[async] WS PUSH: { event: "return.sale.completed", returId, nilaiRefund }
```

> Item dengan `is_bonus=true` tidak perlu diretur
> (tidak ada nilai refund untuk item gratis).

## SD-06: Lihat Stok Real-Time
```
GET /api/kasir/stok?kategori_id=&nama=&page=1&limit=20
← 200: { data: { stok: [{id, kode_produk, nama_produk, kategori,
                          satuan_dasar, qty_stok, stok_minimum,
                          is_rendah, satuan_tersedia: [...]}],
                 pagination } }

WS CONNECT → ws://localhost/ws (subscribe: 'stok-global')
WS PUSH: { event: "stock.updated", updates: [{produkId, stokBaru}] }
```

## SD-07: Cek Stok ⚠️ Update v3 (tambah promo_aktif)
```
GET /api/kasir/produk/{kode}/stok
← 200: {
  data: {
    id, kode_produk, nama_produk,
    satuan_dasar, qty_stok, stok_minimum,
    satuan_tersedia: [
      { id, nama_satuan, isi, harga_jual,
        stok_dalam_satuan, hemat_persen, is_default }
    ],
    promo_aktif: [           <- BARU v3
      {
        id: int,
        nama_promo: string,  <- "Beli 5 Gratis 1"
        tipe_promo: string,
        min_qty: int,
        produk_satuan_id: int,
        bonus_qty: int|null,
        bonus_produk: {id, nama_produk, satuan_tersedia}|null,
        bonus_satuan_id: int|null,
        diskon_nilai: decimal|null,
        keterangan: string|null
      }
    ]
  }
}
← 404: { message: "Produk tidak ditemukan" }
```

## SD-08: CRUD Produk
```
GET  /api/gudang/produk?page=&limit=&kategori_id=&nama=&status=
POST /api/gudang/produk
Body: { kode_produk, nama_produk, kategori_id, deskripsi?,
        harga_beli_dasar, qty_stok, stok_minimum, satuan_dasar,
        satuan_list: [{nama_satuan, isi, harga_beli, harga_jual,
                       is_default, barcode?}] }
GET  /api/gudang/produk/{id}
PUT  /api/gudang/produk/{id}
DEL  /api/gudang/produk/{id}

GET    /api/gudang/produk/{id}/satuan
POST   /api/gudang/produk/{id}/satuan
PUT    /api/gudang/produk/{id}/satuan/{satuan_id}
DELETE /api/gudang/produk/{id}/satuan/{satuan_id}
```

## SD-09: Pembelian/Restock
```
POST /api/gudang/pembelian
Body: { supplier_id, tanggal_pembelian,
        items: [{produk_id, produk_satuan_id, nama_satuan,
                 isi_satuan, qty, harga_beli_satuan}] }
← 202: { id_pembelian, no_pembelian, status: "PENDING" }
WS PUSH: { event: "purchase.completed", pembelianId, stokUpdates }
```

## SD-10: Retur Pembelian
```
GET /api/gudang/pembelian/{id}
POST /api/gudang/retur-pembelian
Body: { pembelian_id, items_retur: [{produk_id, nama_satuan,
        isi_satuan, qty_retur, harga_satuan}], alasan }
← 202: { id_retur, no_retur, status: "PENDING" }
WS PUSH: { event: "return.purchase.completed", returId, nilaiKredit }
```

## SD-11: CRUD Customer
```
GET    /api/kasir/customer?page=&search=&status=
POST   /api/kasir/customer
Body: { nama, no_telp, alamat?, tipe_customer }
GET    /api/kasir/customer/{id}
PUT    /api/kasir/customer/{id}
DELETE /api/kasir/customer/{id}
```

## SD-12: Riwayat Belanja Customer
```
GET /api/kasir/customer?search={keyword}
GET /api/kasir/customer/{id}/riwayat?page=1&limit=20
← 200: { data: { riwayat: [...], total_belanja, frekuensi, total_page } }
GET /api/kasir/transaksi/{id}
```

## SD-13: CRUD Supplier
```
GET    /api/gudang/supplier?page=&search=
POST   /api/gudang/supplier
Body: { nama_supplier, kontak_person?, no_telp, alamat? }
GET    /api/gudang/supplier/{id}
PUT    /api/gudang/supplier/{id}
DELETE /api/gudang/supplier/{id}
```

## SD-14: Laporan
```
GET /api/owner/laporan/{jenis}?dari=&sampai=&kategori_id=
jenis: penjualan | stok | pembelian | void | promo   <- void & promo baru
GET /api/owner/laporan/{jenis}/export?format=pdf|excel
```

## SD-15: Manajemen User
```
GET  /api/owner/user
POST /api/owner/user
Body: { nama, username, password, role_id }
PUT  /api/owner/user/{id}
PATCH /api/owner/user/{id}/reset-password
Body: { password_baru }
PATCH /api/owner/user/{id}/nonaktifkan
```

## SD-16: Manajemen Kategori
```
GET    /api/kategori                     <- publik (kasir+gudang+owner)
GET    /api/gudang/kategori              <- dengan jumlah_produk (gudang+owner)
POST   /api/gudang/kategori
Body: { nama_kategori, deskripsi? }
PUT    /api/gudang/kategori/{id}
DELETE /api/gudang/kategori/{id}
← 422 jika masih ada produk yang menggunakan kategori ini
```

## SD-17: Void Transaksi NEW
```
[Cek kelayakan void]
GET /api/kasir/transaksi/{id}
← 200: { data: { ...transaksi, can_void: bool,
                 sisa_waktu_void: int,     <- sisa detik (0 jika expired)
                 void_reason: string|null  <- alasan tidak bisa void } }

[Kondisi dapat di-void: can_void=true]
  - status = 'COMPLETED'
  - sisa_waktu_void > 0
  - Belum ada void sebelumnya
  - Belum ada retur

[Proses void]
POST /api/kasir/transaksi/{id}/void
Body: {
  alasan: 'kesalahan_input'|'barang_rusak_saat_transaksi'|
          'permintaan_pembeli'|'lainnya',
  keterangan: string|null
}
← 202: { id_void, status: "PENDING", message: "Void sedang diproses" }
← 400: { message: "Alasan spesifik mengapa tidak bisa di-void" }
← 422: { message, errors }

[async via Kafka → WebSocket]
WS PUSH: {
  event: "void.completed",
  voidId: int,
  transaksiId: int,
  status: "COMPLETED",
  message: "Transaksi berhasil dibatalkan. Stok dikembalikan."
}
WS PUSH: { event: "stock.updated", updates: [{produkId, stokBaru}] }
```

## SD-18: Manajemen Promo Produk NEW
```
[CRUD Promo]
GET  /api/gudang/promo?produk_id=&status=&nama=
POST /api/gudang/promo
Body: {
  produk_id: int,
  produk_satuan_id: int,
  nama_promo: string,
  tipe_promo: 'bonus_produk_sama'|'bonus_produk_lain'|
              'diskon_persen'|'diskon_nominal',
  min_qty: int,
  bonus_qty: int|null,
  bonus_produk_id: int|null,
  bonus_satuan_id: int|null,
  diskon_nilai: decimal|null,
  keterangan: string|null,
  is_active: bool,
  berlaku_dari: date|null,
  berlaku_sampai: date|null
}
← 201: { data: promo }
← 422: { message, errors }

PUT   /api/gudang/promo/{id}
PATCH /api/gudang/promo/{id}/toggle
← 200: { data: { id, is_active: bool } }
DELETE /api/gudang/promo/{id}
← 422 jika promo sudah pernah digunakan di transaksi

[Promo digunakan saat transaksi — tidak ada endpoint terpisah]
Promo aktif sudah di-load bersama GET /produk/{kode}/stok (SD-07)
Kalkulasi dilakukan di Frontend (JavaScript)
promo_id dikirim dalam payload POST /transaksi (SD-03)
```

---

## Payload Kafka Events

```javascript
// transaction.created (update v3 — tambah promoId dan isBonus)
{
  eventType: "transaction.created",
  transaksiId: 123,
  items: [
    { produkId: 1, produkSatuanId: 3, qty: 2,
      isiSatuan: 96, hargaSatuan: 280000,
      diskonItem: 0, promoId: null, isBonus: false },
    { produkId: 1, produkSatuanId: 1, qty: 1,
      isiSatuan: 1, hargaSatuan: 0,
      diskonItem: 0, promoId: 5, isBonus: true }  // item gratis
  ],
  userId: 2, total: 560000, metodeBayar: "tunai",
  timestamp: "..."
}

// transaction.void.requested (NEW)
{
  eventType: "transaction.void.requested",
  voidId: 10,
  transaksiId: 123,
  items: [   // hanya item isBonus=false yang perlu dikembalikan stoknya
    { produkId: 1, qty: 2, isiSatuan: 96 }
  ],
  userId: 2,
  timestamp: "..."
}

// purchase.created, sale.return.requested,
// purchase.return.requested — tidak berubah dari v2
```
