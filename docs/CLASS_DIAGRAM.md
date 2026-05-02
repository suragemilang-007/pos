# CLASS_DIAGRAM.md — Referensi Method & Class
> **Versi 3.0** — Tambah: VoidTransaksiService + PromoService

## Panduan
Nama method HARUS identik dengan implementasi.
Tipe: `string`, `int`, `float`, `bool`, `array`, `?int` (nullable).

---

## DOMAIN: AUTH

### UserService — `app/Services/Auth/UserService.php`
```
login(username: string, password: string): array
logout(token: string): bool
validateToken(token: string): bool
resetPassword(userId: int, newPassword: string): bool
updateStatus(userId: int, status: string): bool
```

### SessionService — `app/Services/Auth/SessionService.php`
```
create(userId: int): string
isValid(token: string): bool
revoke(token: string): bool
revokeAllByUser(userId: int): bool
```

---

## DOMAIN: TRANSACTION

### TransaksiService — `app/Services/Transaction/TransaksiService.php`
```
create(items: array, customerId: ?int, userId: int): Transaksi
  items item: {produk_id, produk_satuan_id, nama_satuan, isi_satuan,
               qty, harga_satuan, diskon_item, promo_id?, is_bonus}
  is_bonus=true: item dari promo, harga_satuan=0

calculateTotal(items: array): float
  Hitung dari item non-bonus saja (is_bonus=false)

updateStatus(id: int, status: string): bool

generateStruk(id: int): string
  Include: nama_satuan, is_bonus, nama_promo per baris item

canReturn(id: int): bool
  true hanya jika status='COMPLETED' DAN status BUKAN 'VOIDED'
```

### VoidTransaksiService — `app/Services/Transaction/VoidTransaksiService.php`
```
checkVoidEligibility(transaksiId: int): array
  Return: {eligible: bool, reason: ?string, sisa_detik: ?int}
  Validasi 4 kondisi berurutan:
  1. status = 'COMPLETED'
  2. created_at > NOW() - INTERVAL {env:VOID_TIME_LIMIT_MINUTES} MINUTE
  3. Belum ada record di void_transaksi WHERE transaksi_id=?
  4. Belum ada record di retur_penjualan WHERE transaksi_id=?
  Return reason spesifik per kondisi jika tidak eligible

create(transaksiId: int, userId: int, alasan: string, keterangan: ?string): VoidTransaksi
  1. checkVoidEligibility() ulang (race condition guard)
  2. INSERT void_transaksi status='PENDING'
  3. Publish KafkaProducerService::publishTransactionVoid()
  4. Return VoidTransaksi

getSisaWaktuVoid(transaksi: Transaksi): int
  Return sisa detik (0 jika sudah expired)
  Hitung: VOID_TIME_LIMIT_MINUTES*60 - selisih_detik_sejak_transaksi
```

### ReturPenjualanService — `app/Services/Transaction/ReturPenjualanService.php`
```
create(transaksiId: int, itemsRetur: array, alasan: string, userId: int): ReturPenjualan
validate(transaksiId: int, itemsRetur: array): bool
  Cek tambahan v3: transaksi status BUKAN 'VOIDED'
calculateRefund(itemsRetur: array): float
  Hitung hanya dari item is_bonus=false (item bonus tidak di-refund)
```

### CustomerService — `app/Services/Inventory/CustomerService.php`
```
getRiwayatBelanja(customerId: int, page: int, limit: int): array
getTotalBelanja(customerId: int): float
getFrekuensiBelanja(customerId: int): int
create(data: array): Customer
update(customerId: int, data: array): bool
softDelete(customerId: int): bool
hardDelete(customerId: int): bool
hasTransaksi(customerId: int): bool
```

---

## DOMAIN: INVENTORY

### ProdukService — `app/Services/Inventory/ProdukService.php`
```
create(data: array): Produk
update(id: int, data: array): bool
softDelete(id: int): bool
hardDelete(id: int): bool
hasTransaksi(produkId: int): bool
updateStok(produkId: int, qtyDasar: int, operasi: string): bool
isStokCukup(produkId: int, qty: int, isiSatuan: int): bool
isStokRendah(produkId: int): bool
getByKode(kode: string): ?Produk
  INCLUDE eager load: satuanList(), promoAktif()
getStokDalamSatuan(produkId: int): array
```

### ProdukSatuanService — `app/Services/Inventory/ProdukSatuanService.php`
```
getSatuanList(produkId: int): Collection
getSatuanDefault(produkId: int): ?ProdukSatuan
getSatuanByBarcode(barcode: string): ?ProdukSatuan
create(produkId: int, data: array): ProdukSatuan
update(id: int, data: array): bool
delete(id: int): bool
getHargaEfektifPerDasar(satuanId: int): float
getHematPersen(produkId: int, satuanId: int): float
```

### KategoriProdukService — `app/Services/Inventory/KategoriProdukService.php`
```
getAll(): Collection
create(data: array): KategoriProduk
update(id: int, data: array): bool
delete(id: int): bool
hasProduk(id: int): bool
```

### PromoService — `app/Services/Inventory/PromoService.php`
```
getAll(filters: array): Collection
  filters: {produk_id?, status?, nama?}

getByProduk(produkId: int): Collection
  Return semua promo produk tersebut (aktif maupun tidak)

getPromoAktif(produkId: int): Collection
  Return promo yang berlaku hari ini:
  WHERE is_active=1
    AND (berlaku_dari IS NULL OR berlaku_dari <= CURDATE())
    AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())

create(data: array): ProdukPromo
  Validasi sesuai tipe_promo:
  - bonus_produk_sama: wajib bonus_qty, bonus_produk_id null
  - bonus_produk_lain: wajib bonus_qty + bonus_produk_id + bonus_satuan_id
  - diskon_persen: wajib diskon_nilai (0 < nilai <= 100)
  - diskon_nominal: wajib diskon_nilai (> 0)

update(id: int, data: array): bool

toggle(id: int): bool
  UPDATE is_active = NOT is_active

delete(id: int): bool
  Cek apakah promo sudah pernah dipakai di detail_transaksi
  Jika sudah: TOLAK, return false

hasBeenUsed(promoId: int): bool
  Cek COUNT(*) di detail_transaksi WHERE promo_id=?
```

### SupplierService — `app/Services/Inventory/SupplierService.php`
```
create(data: array): Supplier
update(id: int, data: array): bool
softDelete(id: int): bool
hardDelete(id: int): bool
hasPembelian(supplierId: int): bool
```

---

## DOMAIN: PURCHASING

### PembelianService — `app/Services/Purchasing/PembelianService.php`
```
create(supplierId: int, items: array, tanggal: string, userId: int): Pembelian
calculateTotal(items: array): float
updateStatus(id: int, status: string): bool
canReturn(id: int): bool
```

### ReturPembelianService — `app/Services/Purchasing/ReturPembelianService.php`
```
create(pembelianId: int, itemsRetur: array, alasan: string, userId: int): ReturPembelian
validate(pembelianId: int, itemsRetur: array): bool
calculateKredit(itemsRetur: array): float
```

---

## DOMAIN: EVENT-DRIVEN (KAFKA)

### KafkaProducerService — `app/Services/Kafka/KafkaProducerService.php`
```
publishTransactionCreated(transaksi: Transaksi): bool
  Payload items WAJIB: {produkId, produkSatuanId, qty, isiSatuan,
                        hargaSatuan, diskonItem, promoId, isBonus}

publishPurchaseCreated(pembelian: Pembelian): bool

publishSaleReturnRequested(retur: ReturPenjualan): bool

publishPurchaseReturnRequested(retur: ReturPembelian): bool

publishTransactionVoid(void: VoidTransaksi): bool
  Payload: {
    eventType: 'transaction.void.requested',
    voidId: int,
    transaksiId: int,
    items: [{produkId, qty, isiSatuan, isBonus}],
    userId: int,
    timestamp: string
  }
  CATATAN: hanya kirim item dengan isBonus=false ke Consumer
  Item bonus tidak perlu dikembalikan stoknya
```

### voidConsumer.js — `kafka-service/consumers/voidConsumer.js` NEW
```javascript
startVoidConsumer(): void
  Subscribe: topic transaction-events, group pos-void-group
  Filter: eventType === 'transaction.void.requested'

processVoid(event: object): void
  Urutan WAJIB dalam satu DB transaction:
  1. BEGIN
  2. Untuk setiap item (hanya isBonus=false):
     UPDATE produk SET qty_stok = qty_stok + (qty x isiSatuan)
  3. UPDATE transaksi SET status = 'VOIDED'
  4. UPDATE void_transaksi SET status = 'COMPLETED'
  5. COMMIT
  6. wsService.push('user-{userId}', {event:'void.completed', ...})
  7. wsService.pushAll('stok-global', {event:'stock.updated', updates:[...]})
```

### transactionConsumer.js — Update
```javascript
validateStock(pool, items):
  Hanya validasi item dengan isBonus=false
  Item bonus (isBonus=true) tidak perlu validasi stok

processStockDecrease(items):
  Hanya kurangi stok untuk item dengan isBonus=false
  Item bonus stok JUGA dikurangi (barang tetap keluar dari gudang)
  KOREKSI: semua item (termasuk bonus) mengurangi stok
```

### wsService.js
```javascript
push(channel: string, data: object): void
pushAll(broadcastChannel: string, data: object): void
setChannels(channelsMap: Map): void
```

---

## DOMAIN: REPORTING

### LaporanService — `app/Services/Reporting/LaporanService.php`
```
generateLaporanPenjualan(dari: string, sampai: string): array
  Include: breakdown item bonus dari promo

generateLaporanStok(kategoriId: ?int): array

generateLaporanPembelian(dari: string, sampai: string): array

generateLaporanVoid(dari: string, sampai: string): array
  Return: list void_transaksi dengan detail transaksi asal
  Include: alasan, kasir, total nilai yang di-void

generateLaporanPromo(dari: string, sampai: string): array
  Return per promo: berapa kali diterapkan, total item bonus,
  total nilai diskon yang diberikan

getMetrikBisnis(dari: string, sampai: string): array

exportPDF(laporan: array): BinaryFileResponse
exportExcel(laporan: array): BinaryFileResponse
```

---

## MODEL ELOQUENT

### Model: `VoidTransaksi` — `app/Models/VoidTransaksi.php`
```php
$fillable = ['transaksi_id', 'transaksi_baru_id', 'user_id',
             'alasan', 'keterangan', 'status', 'batas_waktu']

// Relasi
transaksi(): BelongsTo      -> Transaksi (transaksi asal)
transaksi_baru(): BelongsTo -> Transaksi (transaksi koreksi, nullable)
user(): BelongsTo           -> User (kasir yang void)
```

### Model: `ProdukPromo` — `app/Models/ProdukPromo.php`
```php
$fillable = ['produk_id', 'produk_satuan_id', 'nama_promo',
             'tipe_promo', 'min_qty', 'bonus_qty',
             'bonus_produk_id', 'bonus_satuan_id',
             'diskon_nilai', 'keterangan', 'is_active',
             'berlaku_dari', 'berlaku_sampai']

$casts = ['is_active' => 'boolean', 'min_qty' => 'integer',
          'bonus_qty' => 'integer', 'diskon_nilai' => 'decimal:2']

// Relasi
produk(): BelongsTo         -> Produk (produk yang promo)
satuanSyarat(): BelongsTo   -> ProdukSatuan (satuan syarat beli)
bonusProduk(): BelongsTo    -> Produk (produk bonus, nullable)
bonusSatuan(): BelongsTo    -> ProdukSatuan (satuan bonus, nullable)

// Scope
scopeAktif($query)          -> filter promo yang berlaku hari ini
```

### Model: `Transaksi` — Update
```php
// Tambah nilai VOIDED pada cast status
// Tambah relasi baru:
voidTransaksi(): HasOne -> VoidTransaksi
```

### Model: `DetailTransaksi` — Update
```php
// Tambah ke fillable: promo_id, is_bonus
// Tambah relasi:
promo(): BelongsTo -> ProdukPromo (nullable)
```
