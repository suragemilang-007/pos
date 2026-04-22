# CLASS_DIAGRAM.md — Referensi Method & Class
> **Versi 2.0** — Update: ProdukSatuan + multi-unit pricing

## Panduan Penggunaan
Setiap method di sini HARUS diimplementasikan dengan nama yang persis sama.
Jangan rename method tanpa update file ini terlebih dahulu.
Tipe parameter menggunakan konvensi PHP: `string`, `int`, `float`, `bool`, `array`, `?int` (nullable).

---

## DOMAIN: AUTH

### UserService — `app/Services/Auth/UserService.php`
```
login(username: string, password: string): array
  Return: {token, role: {id, nama}, user: {id, nama, username}}
  JANGAN sertakan password_hash

logout(token: string): bool

validateToken(token: string): bool

resetPassword(userId: int, newPassword: string): bool
  Hash bcrypt sebelum simpan

updateStatus(userId: int, status: string): bool
  Jika status='nonaktif': panggil SessionService::revokeAllByUser() sesudahnya
```

### SessionService — `app/Services/Auth/SessionService.php`
```
create(userId: int): string
  INSERT ke sessions, return JWT token

isValid(token: string): bool
  Cek: status='active' AND expired_at > NOW()

revoke(token: string): bool
  UPDATE sessions SET status='revoked'

revokeAllByUser(userId: int): bool
  UPDATE sessions SET status='revoked' WHERE user_id=?
  Dipakai saat user dinonaktifkan oleh Owner
```

---

## DOMAIN: TRANSACTION

### TransaksiService — `app/Services/Transaction/TransaksiService.php`
```
create(items: array, customerId: ?int, userId: int): Transaksi
  items item: {produk_id, produk_satuan_id, nama_satuan, isi_satuan, qty, harga_satuan, diskon_item}
  Langkah: calculateTotal() -> generate no_transaksi -> INSERT transaksi+detail -> publishKafka
  Return: Transaksi dengan status PENDING

calculateTotal(items: array): float
  Sum dari (harga_satuan x qty - diskon_item) per item

updateStatus(id: int, status: string): bool

generateStruk(id: int): string
  Return HTML string
  Query: JOIN transaksi + detail_transaksi + produk + info toko
  Tampilkan: nama_satuan dan qty per baris

canReturn(id: int): bool
  Return true hanya jika status='COMPLETED'
```

### ReturPenjualanService — `app/Services/Transaction/ReturPenjualanService.php`
```
create(transaksiId: int, itemsRetur: array, alasan: string, userId: int): ReturPenjualan
  itemsRetur item: {produk_id, nama_satuan, isi_satuan, qty_retur, harga_satuan}
  Snapshot nama_satuan dan isi_satuan dari detail_transaksi asal

validate(transaksiId: int, itemsRetur: array): bool
  Cek: transaksi status=COMPLETED
  Cek: qty_retur <= qty_beli_asli per produk

calculateRefund(itemsRetur: array): float
  Sum dari (harga_satuan x qty_retur) per item
```

### CustomerService — `app/Services/Inventory/CustomerService.php`
```
getRiwayatBelanja(customerId: int, page: int, limit: int): array
  Include: total_belanja (SUM), frekuensi (COUNT)

getTotalBelanja(customerId: int): float

getFrekuensiBelanja(customerId: int): int

create(data: array): Customer

update(customerId: int, data: array): bool

softDelete(customerId: int): bool
  UPDATE status='nonaktif'

hardDelete(customerId: int): bool
  DELETE FROM customers

hasTransaksi(customerId: int): bool
  Cek COUNT(*) di tabel transaksi WHERE customer_id=?
```

---

## DOMAIN: INVENTORY

### ProdukService — `app/Services/Inventory/ProdukService.php`
```
create(data: array): Produk
  data wajib: kode_produk, nama_produk, kategori_id, satuan_dasar, harga_beli_dasar

update(id: int, data: array): bool

softDelete(id: int): bool

hardDelete(id: int): bool

hasTransaksi(produkId: int): bool
  Cek COUNT(*) di detail_transaksi WHERE produk_id=?

updateStok(produkId: int, qtyDasar: int, operasi: string): bool
  WAJIB: qtyDasar sudah dalam satuan_dasar (sudah dikonversi)
  operasi: 'tambah' | 'kurangi'

isStokCukup(produkId: int, qty: int, isiSatuan: int): bool
  Cek: qty_stok >= qty x isiSatuan

isStokRendah(produkId: int): bool
  Cek: qty_stok <= stok_minimum

getByKode(kode: string): ?Produk
  Query produk berdasarkan kode_produk status='aktif'
  INCLUDE relasi produk_satuan (eager load)

getStokDalamSatuan(produkId: int): array
  Return list satuan dengan stok masing-masing:
  [{id, nama_satuan, isi, harga_jual, stok_dalam_satuan_ini, is_default}]
  stok_dalam_satuan_ini = floor(qty_stok / isi)
```

### ProdukSatuanService — `app/Services/Inventory/ProdukSatuanService.php`
```
getSatuanList(produkId: int): Collection
  Return semua satuan produk, urut: is_default DESC, isi ASC

getSatuanDefault(produkId: int): ?ProdukSatuan
  Return satuan dengan is_default=1

getSatuanByBarcode(barcode: string): ?ProdukSatuan
  Untuk scan barcode satuan besar (karton, dll)

create(produkId: int, data: array): ProdukSatuan
  data: {nama_satuan, isi, harga_beli, harga_jual, is_default, barcode?}
  Jika is_default=1: reset semua satuan lain di produk ini menjadi 0

update(id: int, data: array): bool
  Jika is_default=1: reset satuan lain menjadi 0

delete(id: int): bool
  Cek: jangan hapus jika masih ada di detail_transaksi atau detail_pembelian

getHargaEfektifPerDasar(satuanId: int): float
  Return: harga_jual / isi
  Digunakan untuk perbandingan harga antar satuan

getHematPersen(produkId: int, satuanId: int): float
  Bandingkan harga efektif satuan ini vs satuan default
  Return: persentase penghematan (0 jika satuan default)
  Contoh: (3500 - 3150) / 3500 * 100 = 10.0
```

### SupplierService — `app/Services/Inventory/SupplierService.php`
```
create(data: array): Supplier
update(id: int, data: array): bool
softDelete(id: int): bool
hardDelete(id: int): bool
hasPembelian(supplierId: int): bool
getProdukList(supplierId: int): Collection
getRiwayatPembelian(supplierId: int): Collection
```

---

## DOMAIN: PURCHASING

### PembelianService — `app/Services/Purchasing/PembelianService.php`
```
create(supplierId: int, items: array, tanggal: string, userId: int): Pembelian
  items item: {produk_id, produk_satuan_id, nama_satuan, isi_satuan, qty, harga_beli_satuan}
  Langkah: calculateTotal() -> generate no_pembelian -> INSERT pembelian+detail -> publishKafka

calculateTotal(items: array): float
  Sum dari (harga_beli_satuan x qty) per item

updateStatus(id: int, status: string): bool

canReturn(id: int): bool
  Return true hanya jika status='COMPLETED'
```

### ReturPembelianService — `app/Services/Purchasing/ReturPembelianService.php`
```
create(pembelianId: int, itemsRetur: array, alasan: string, userId: int): ReturPembelian
  Snapshot nama_satuan dan isi_satuan dari detail_pembelian asal

validate(pembelianId: int, itemsRetur: array): bool
  Cek: pembelian status=COMPLETED
  Cek: qty_retur <= qty_beli per produk

calculateKredit(itemsRetur: array): float
  Sum dari (harga_satuan x qty_retur) per item
```

---

## DOMAIN: EVENT-DRIVEN (KAFKA)

### KafkaProducerService — `app/Services/Kafka/KafkaProducerService.php`
```
publishTransactionCreated(transaksi: Transaksi): bool
  Payload items WAJIB sertakan: {produkId, produkSatuanId, qty, isiSatuan, hargaSatuan, diskonItem}

publishPurchaseCreated(pembelian: Pembelian): bool
  Payload items WAJIB sertakan: {produkId, produkSatuanId, qty, isiSatuan, hargaBeliSatuan}

publishSaleReturnRequested(retur: ReturPenjualan): bool
  Payload items: {produkId, qtyRetur, isiSatuan, hargaSatuan}

publishPurchaseReturnRequested(retur: ReturPembelian): bool
  Payload items: {produkId, qtyRetur, isiSatuan, hargaSatuan}
```

### transactionConsumer.js — `kafka-service/consumers/transactionConsumer.js`
```javascript
startTransactionConsumer(): void
processTransaction(event: object): void
validateStock(pool, items): {valid: bool, message: string}
  items item WAJIB punya: {produkId, qty, isiSatuan}
  Cek: qty_stok >= qty x isiSatuan
markTransaksiFailed(transaksiId, userId, message): void
```

### purchaseConsumer.js — `kafka-service/consumers/purchaseConsumer.js`
```javascript
startPurchaseConsumer(): void
processPurchase(event: object): void
  WAJIB: qty_bertambah = item.qty x item.isiSatuan
  UPDATE produk SET qty_stok = qty_stok + (qty x isiSatuan)
  UPDATE produk SET harga_beli = item.hargaBeliSatuan / item.isiSatuan  <- per satuan_dasar
```

### returnConsumer.js — `kafka-service/consumers/returnConsumer.js`
```javascript
startReturnConsumer(): void
processSaleReturn(event): void
  TAMBAH stok: qty_stok + (item.qtyRetur x item.isiSatuan)
processPurchaseReturn(event): void
  KURANGI stok: qty_stok - (item.qtyRetur x item.isiSatuan)
```

### wsService.js — `kafka-service/services/wsService.js`
```javascript
push(channel: string, data: object): void
  Kirim ke semua subscriber channel tertentu

pushAll(broadcastChannel: string, data: object): void
  Broadcast ke semua subscriber (dipakai untuk stok-global)

setChannels(channelsMap: Map): void
  Inisialisasi referensi ke channels Map dari wsServer
```

---

## DOMAIN: REPORTING

### LaporanService — `app/Services/Reporting/LaporanService.php`
```
generateLaporanPenjualan(dari: string, sampai: string): array
  Query: transaksi JOIN detail_transaksi JOIN produk
  Filter: status='COMPLETED', tanggal BETWEEN
  Tampilkan satuan yang digunakan per baris: nama_satuan, isi_satuan

generateLaporanStok(kategoriId: ?int): array
  Return per produk: {nama, satuan_dasar, qty_stok, stok_minimum, is_rendah,
                      satuan_tersedia: [{nama, stok_dalam_satuan}]}

generateLaporanPembelian(dari: string, sampai: string): array

getMetrikBisnis(dari: string, sampai: string): array
  Return: {omzet, profit_kotor, perputaran_stok}
  profit_kotor: harga_jual per satuan - (harga_beli_dasar x isi_satuan)

exportPDF(laporan: array): BinaryFileResponse
exportExcel(laporan: array): BinaryFileResponse
```

---

## MODEL ELOQUENT

### Model: `ProdukSatuan` — `app/Models/ProdukSatuan.php`
```php
$fillable = [
  'produk_id', 'nama_satuan', 'isi',
  'harga_beli', 'harga_jual', 'is_default', 'barcode'
]

$casts = [
  'isi'        => 'integer',
  'harga_beli' => 'decimal:2',
  'harga_jual' => 'decimal:2',
  'is_default' => 'boolean',
]

// Relasi
produk(): BelongsTo     -> Produk
detailTransaksi(): HasMany -> DetailTransaksi

// Accessor (computed, tidak disimpan di DB)
getHargaEfektifPerDasarAttribute(): float
  return $this->harga_jual / $this->isi

getStokDalamSatuanAttribute(): int
  return (int) floor($this->produk->qty_stok / $this->isi)
```

### Model: `Produk` — `app/Models/Produk.php`
```php
// PERUBAHAN dari v1:
// - Hapus: harga_jual dari fillable
// - Hapus: satuan dari fillable dan casts
// - Tambah: satuan_dasar, harga_beli_dasar

$fillable = [
  'kategori_id', 'kode_produk', 'nama_produk', 'deskripsi',
  'harga_beli_dasar', 'qty_stok', 'stok_minimum', 'satuan_dasar', 'status'
]

// Relasi BARU
satuanList(): HasMany    -> ProdukSatuan (urut: is_default DESC, isi ASC)
satuanDefault(): HasOne  -> ProdukSatuan WHERE is_default=1
```
