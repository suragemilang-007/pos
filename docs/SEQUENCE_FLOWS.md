# SEQUENCE_FLOWS.md — Kontrak API & Urutan Pesan
> **Versi 4.0** — Update: SD-09 (Cash/Kredit) + Tambah SD-19, SD-20

## Konvensi
- `202` → async via Kafka | `200` → sinkron selesai | `201` → data dibuat

---

## SD-01: Login
```
POST /api/auth/login
Body: { username, password }
← 200: { data: { token, role: {id, nama_role}, user: {id, nama, username} } }
← 422 | 401
```

## SD-02: Logout
```
POST /api/auth/logout
← 200 | 401
```

## SD-03: Input Transaksi Penjualan
```
POST /api/kasir/transaksi
Body: { items: [{produk_id, produk_satuan_id, nama_satuan,
                 isi_satuan, qty, harga_satuan, diskon_item}],
        customer_id?, subtotal, diskon, total, bayar, kembalian,
        metode_bayar }
← 202: { id_transaksi, no_transaksi, status: "PENDING" }
[async WS] transaction.completed | transaction.failed | stock.updated
```

## SD-04: Cetak Struk
```
GET /api/kasir/transaksi/{id}/struk
← 200: { data: { no_transaksi, items[], subtotal, diskon,
                 total, bayar, kembalian, metode_bayar } }
← 403 | 404
```

## SD-05: Retur Penjualan
```
GET /api/kasir/transaksi/{id}
POST /api/kasir/retur-penjualan
Body: { transaksi_id, items_retur: [{produk_id, nama_satuan,
        isi_satuan, qty_retur, harga_satuan}], alasan }
← 202: { id_retur, no_retur, status: "PENDING" }
[async WS] return.sale.completed | stock.updated
```

## SD-06: Lihat Stok Real-Time
```
GET /api/kasir/stok?kategori_id=&nama=&page=1&limit=20
← 200: { data: { stok: [{id, kode_produk, nama_produk,
                          satuan_dasar, qty_stok, stok_minimum,
                          is_rendah, satuan_tersedia[]}],
                 pagination } }
WS CONNECT + PUSH stock.updated
```

## SD-07: Cek Stok
```
GET /api/kasir/produk/{kode}/stok
← 200: { data: { id, nama_produk, satuan_dasar, qty_stok,
                 stok_minimum, satuan_tersedia[] } }
← 404
```

## SD-08: CRUD Produk
```
GET/POST/PUT/DELETE /api/gudang/produk
GET/POST/PUT/DELETE /api/gudang/produk/{id}/satuan/{satuan_id?}
```

## SD-09: Pembelian / Restock ⚠️ Update v4
```
POST /api/gudang/pembelian
Body: {
  supplier_id: int,
  tanggal_pembelian: date,
  tipe_pembayaran: 'cash' | 'kredit',
  jumlah_dp: decimal,              <- 0 jika full kredit atau cash
  tanggal_jatuh_tempo: date|null,  <- WAJIB jika kredit
  catatan_pembayaran: string|null,
  items: [{
    produk_id, produk_satuan_id,
    nama_satuan, isi_satuan,
    qty, harga_beli_satuan
  }]
}
← 202: {
  id_pembelian, no_pembelian,
  status: "PENDING",
  tipe_pembayaran,
  sisa_hutang,
  tanggal_jatuh_tempo
}
← 422: { errors: { tanggal_jatuh_tempo: ["Wajib diisi untuk pembelian kredit"] } }

[async via Kafka Consumer → MySQL]:
  - UPDATE stok semua item (qty x isi_satuan)
  - UPDATE pembelian status='COMPLETED'
  - INSERT pembayaran_pembelian (jika cash atau kredit+dp)

[async WS]:
{
  event: 'purchase.completed',
  pembelianId,
  tipe_pembayaran,
  sisa_hutang,        <- 0 jika cash
  tanggal_jatuh_tempo <- null jika cash
}
+ { event: 'stock.updated', updates: [...] }

GET /api/gudang/pembelian         <- daftar PO
GET /api/gudang/pembelian/{id}    <- detail PO + riwayat pembayaran
```

## SD-10: Retur Pembelian
```
GET /api/gudang/pembelian/{id}
POST /api/gudang/retur-pembelian
Body: { pembelian_id, items_retur: [{produk_id, nama_satuan,
        isi_satuan, qty_retur, harga_satuan}], alasan }
← 202: { id_retur, no_retur, status: "PENDING" }
[async WS] return.purchase.completed | stock.updated
```

## SD-11: CRUD Customer
```
GET/POST/PUT/DELETE /api/kasir/customer
GET /api/kasir/customer?search=
```

## SD-12: Riwayat Belanja Customer
```
GET /api/kasir/customer/{id}/riwayat?page=1&limit=20
GET /api/kasir/transaksi/{id}
```

## SD-13: CRUD Supplier
```
GET/POST/PUT/DELETE /api/gudang/supplier
```

## SD-14: Laporan
```
GET /api/owner/laporan/{jenis}?dari=&sampai=&filter=
jenis: penjualan | stok | pembelian | void | hutang | penyesuaian_stok
GET /api/owner/laporan/{jenis}/export?format=pdf|excel
```

## SD-15: Manajemen User
```
GET/POST /api/owner/user
PUT /api/owner/user/{id}
PATCH /api/owner/user/{id}/reset-password
PATCH /api/owner/user/{id}/nonaktifkan
```

## SD-16: Manajemen Kategori
```
GET /api/kategori                  <- publik (dropdown)
GET/POST/PUT/DELETE /api/gudang/kategori/{id?}
```

## SD-17: Void Transaksi
```
GET /api/kasir/transaksi/{id}
← 200: { data: { ...transaksi, can_void, sisa_waktu_void, void_reason } }

POST /api/kasir/transaksi/{id}/void
Body: { alasan, keterangan? }
← 202: { id_void, status: "PENDING" }
[async WS] void.completed | stock.updated
```

## SD-18: Manajemen Promo Produk
```
GET/POST/PUT /api/gudang/promo/{id?}
PATCH /api/gudang/promo/{id}/toggle
DELETE /api/gudang/promo/{id}
```

## SD-19: Pembayaran Hutang Pembelian NEW
```
[Muat daftar hutang]
GET /api/gudang/hutang
  Query params: supplier_id?, status_pembayaran?, page?
← 200: {
  data: [
    {
      id, no_pembelian,
      supplier: { id, nama_supplier },
      total,
      jumlah_dp,
      sisa_hutang,
      tanggal_jatuh_tempo,
      status_pembayaran,
      hari_tersisa: int,   <- negatif jika sudah lewat jatuh tempo
      total_dibayar: decimal
    }
  ],
  ringkasan: {
    total_hutang_semua: decimal,
    hutang_jatuh_tempo_3hari: int  <- jumlah PO yang < 3 hari
  },
  pagination
}

[Detail PO + riwayat bayar]
GET /api/gudang/pembelian/{id}
← 200: {
  data: {
    ...pembelian,
    items[],
    riwayat_pembayaran: [
      { id, jumlah, metode, tanggal_bayar, catatan, nama_user }
    ],
    total_dibayar,
    sisa_hutang
  }
}

[Catat pembayaran / cicilan]
POST /api/gudang/pembelian/{id}/bayar
Body: {
  jumlah: decimal,
  metode: 'cash'|'transfer'|'cek'|'giro',
  tanggal_bayar: date,
  catatan: string|null
}
← 200: {
  message: "Pembayaran berhasil dicatat",
  sisa_hutang_baru: decimal,
  status_pembayaran_baru: 'lunas'|'sebagian'
}
← 422: {
  message: "Jumlah melebihi sisa hutang",
  errors: { jumlah: ["Maksimal Rp X"] }
}

[Tidak ada Kafka — operasi sinkron murni keuangan]
```

## SD-20: Penyesuaian Stok NEW
```
[Muat riwayat penyesuaian]
GET /api/gudang/penyesuaian-stok?page=1&limit=20&produk_id=&tipe=
← 200: {
  data: [
    {
      id, produk: {id, nama_produk, kode_produk},
      tipe, qty_satuan_dasar,
      keterangan, status,
      created_at, nama_user
    }
  ],
  pagination
}

[Cek stok produk sebelum input — reuse endpoint existing]
GET /api/kasir/produk/{kode}/stok
← 200: { data: { produk, qty_stok, satuan_tersedia[] } }

[Submit penyesuaian baru]
POST /api/gudang/penyesuaian-stok
Body: {
  produk_id: int,
  tipe: 'barang_rusak'|'barang_hilang'|'barang_expired'|
        'koreksi_plus'|'koreksi_minus',
  qty_satuan_dasar: int,  <- selalu positif, arah ditentukan tipe
  keterangan: string      <- wajib diisi
}
← 202: { id_penyesuaian, status: "PENDING" }
← 422: {
  errors: {
    qty_satuan_dasar: ["Qty melebihi stok tersedia (maks: N)"]
  }
}

[async via Kafka Consumer]
Kafka event: stock.adjustment.requested

[async WS]
← WS PUSH: {
  event: 'adjustment.completed',
  adjustmentId: int,
  tipe: string,
  produkId: int,
  stokBaru: int,
  message: 'Stok [nama_produk] diperbarui. Stok baru: N pcs'
}
← WS PUSH: { event: 'stock.updated', updates: [{produkId, stokBaru}] }
```

---

## Payload Kafka Events (Semua)

```javascript
// transaction.created — tidak berubah dari v3
// transaction.void.requested — tidak berubah dari v3
// sale.return.requested — tidak berubah
// purchase.return.requested — tidak berubah

// purchase.created — update v4 (tambah tipe_pembayaran + jumlah_dp)
{
  eventType: 'purchase.created',
  pembelianId: 45,
  supplierId: 3,
  tipePembayaran: 'kredit',
  jumlahDp: 500000,
  items: [
    { produkId: 1, produkSatuanId: 3, qty: 5,
      isiSatuan: 6, hargaBeliSatuan: 150000 }
  ],
  userId: 5,
  timestamp: '...'
}

// stock.adjustment.requested — BARU
{
  eventType: 'stock.adjustment.requested',
  adjustmentId: 10,
  produkId: 1,
  qtySatuanDasar: 12,   <- selalu positif
  tipe: 'barang_rusak', <- menentukan arah +/-
  userId: 5,
  timestamp: '...'
}
```
