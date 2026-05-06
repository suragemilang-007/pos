# CLASS_DIAGRAM.md — Referensi Method & Class
> **Versi 4.0** — Tambah: PembayaranPembelianService + PenyesuaianStokService
> Update: PembelianService (hutang dagang)

---

## DOMAIN: AUTH

### UserService
```
login(username, password): array
logout(token): bool
validateToken(token): bool
resetPassword(userId, newPassword): bool
updateStatus(userId, status): bool
```

### SessionService
```
create(userId): string
isValid(token): bool
revoke(token): bool
revokeAllByUser(userId): bool
```

---

## DOMAIN: TRANSACTION

### TransaksiService
```
create(items, customerId?, userId): Transaksi
calculateTotal(items): float
updateStatus(id, status): bool
generateStruk(id): string
canReturn(id): bool
```

### VoidTransaksiService
```
checkVoidEligibility(transaksiId): array
getSisaWaktuVoid(transaksi): int
create(transaksiId, userId, alasan, keterangan?): VoidTransaksi
```

### ReturPenjualanService
```
create(transaksiId, itemsRetur, alasan, userId): ReturPenjualan
validate(transaksiId, itemsRetur): bool
calculateRefund(itemsRetur): float
```

### CustomerService
```
getRiwayatBelanja(customerId, page, limit): array
getTotalBelanja(customerId): float
getFrekuensiBelanja(customerId): int
create(data): Customer
update(customerId, data): bool
softDelete(customerId): bool
hardDelete(customerId): bool
hasTransaksi(customerId): bool
```

---

## DOMAIN: INVENTORY

### ProdukService
```
create(data): Produk
update(id, data): bool
softDelete(id): bool
hardDelete(id): bool
hasTransaksi(produkId): bool
updateStok(produkId, qtyDasar, operasi): bool
  operasi: 'tambah' | 'kurangi'
  WAJIB: qtyDasar sudah dalam satuan_dasar
isStokCukup(produkId, qty, isiSatuan): bool
isStokRendah(produkId): bool
getByKode(kode): ?Produk
getStokDalamSatuan(produkId): array
```

### ProdukSatuanService
```
getSatuanList(produkId): Collection
getSatuanDefault(produkId): ?ProdukSatuan
getSatuanByBarcode(barcode): ?ProdukSatuan
create(produkId, data): ProdukSatuan
update(id, data): bool
delete(id): bool
getHargaEfektifPerDasar(satuanId): float
getHematPersen(produkId, satuanId): float
```

### KategoriProdukService
```
getAll(): Collection
create(data): KategoriProduk
update(id, data): bool
delete(id): bool
hasProduk(id): bool
```

### PenyesuaianStokService — `app/Services/Inventory/PenyesuaianStokService.php` NEW
```
getAll(filters): Collection
  filters: {produk_id?, tipe?, dari?, sampai?}

create(data): PenyesuaianStok
  data: {produk_id, tipe, qty_satuan_dasar, keterangan}
  Validasi sebelum INSERT:
  - produk harus ada dan status='aktif'
  - qty_satuan_dasar > 0
  - Jika tipe kurang stok: qty_satuan_dasar <= qty_stok saat ini
    (cegah stok negatif)
  Setelah INSERT: publish KafkaProducerService::publishStockAdjustment()

isTipeKurang(tipe): bool
  Return true untuk: barang_rusak, barang_hilang,
                     barang_expired, koreksi_minus

isTipeTambah(tipe): bool
  Return true untuk: koreksi_plus
```

### SupplierService
```
create(data): Supplier
update(id, data): bool
softDelete(id): bool
hardDelete(id): bool
hasPembelian(supplierId): bool
```

---

## DOMAIN: PURCHASING ⚠️ Update v4

### PembelianService — `app/Services/Purchasing/PembelianService.php`
```
create(supplierId, items, tanggal, tipePembayaran,
       jumlahDp, tanggalJatuhTempo?, catatanPembayaran?,
       userId): Pembelian
  Hitung sisa_hutang = total - jumlah_dp
  Tentukan status_pembayaran:
    - cash: 'lunas'
    - kredit dp=0: 'belum_lunas'
    - kredit dp>0: 'sebagian'
  Validasi: jika kredit, tanggal_jatuh_tempo WAJIB diisi
  Publish: KafkaProducerService::publishPurchaseCreated()

calculateTotal(items): float

updateStatus(id, status): bool

canReturn(id): bool

getDaftarHutang(filters): Collection
  filters: {supplier_id?, status_pembayaran?, dari?, sampai?}
  Query pembelian WHERE status_pembayaran IN ('belum_lunas','sebagian')
  Include: total_dibayar (SUM pembayaran_pembelian)
           hari_tersisa (DATEDIFF tanggal_jatuh_tempo, CURDATE())
  Order: tanggal_jatuh_tempo ASC (yang paling dekat jatuh tempo di atas)

getTotalHutangPerSupplier(): array
  GROUP BY supplier_id, SUM sisa_hutang

getHutangJatuhTempoDalam(hari): Collection
  Return PO yang jatuh tempo dalam N hari ke depan
  Digunakan untuk sistem reminder/notifikasi
```

### PembayaranPembelianService — `app/Services/Purchasing/PembayaranPembelianService.php` NEW
```
create(pembelianId, userId, jumlah, metode,
       tanggalBayar, catatan?): PembayaranPembelian
  Validasi:
  - jumlah > 0
  - jumlah <= pembelian.sisa_hutang
  - pembelian.status_pembayaran != 'lunas'
  Setelah INSERT:
  - hitung sisa_hutang_baru = pembelian.sisa_hutang - jumlah
  - UPDATE pembelian.sisa_hutang = sisa_hutang_baru
  - Jika sisa_hutang_baru = 0:
      UPDATE pembelian.status_pembayaran = 'lunas'
  - Jika sisa_hutang_baru > 0:
      UPDATE pembelian.status_pembayaran = 'sebagian'
  Return PembayaranPembelian

getRiwayatPembayaran(pembelianId): Collection
  Return semua record pembayaran untuk PO ini
  Include: nama user yang mencatat, total sudah dibayar

getTotalDibayar(pembelianId): float
  Return SUM(jumlah) WHERE pembelian_id=?
```

### ReturPembelianService
```
create(pembelianId, itemsRetur, alasan, userId): ReturPembelian
validate(pembelianId, itemsRetur): bool
calculateKredit(itemsRetur): float
```

---

## DOMAIN: EVENT-DRIVEN (KAFKA)

### KafkaProducerService — update v4
```
publishTransactionCreated(transaksi): bool
publishPurchaseCreated(pembelian): bool
  Payload items: {produkId, produkSatuanId, qty, isiSatuan,
                  hargaBeliSatuan, tipePembayaran, jumlahDp}
publishSaleReturnRequested(retur): bool
publishPurchaseReturnRequested(retur): bool
publishTransactionVoid(void): bool
publishStockAdjustment(penyesuaian): bool  ← BARU
  Payload: {
    eventType: 'stock.adjustment.requested',
    adjustmentId: int,
    produkId: int,
    qtySatuanDasar: int,
    tipe: string,
    userId: int,
    timestamp: string
  }
```

### adjustmentConsumer.js — `kafka-service/consumers/adjustmentConsumer.js` NEW
```javascript
startAdjustmentConsumer(): void
  Topic: inventory-updates
  Group: pos-adjustment-group
  Filter: eventType === 'stock.adjustment.requested'

processAdjustment(event): void
  Urutan dalam satu DB transaction:
  1. BEGIN
  2. Jika tipe tambah (koreksi_plus):
     UPDATE produk SET qty_stok = qty_stok + qtySatuanDasar
  3. Jika tipe kurang (rusak/hilang/expired/koreksi_minus):
     UPDATE produk SET qty_stok = qty_stok - qtySatuanDasar
  4. UPDATE penyesuaian_stok SET status='COMPLETED'
  5. COMMIT
  6. Ambil stok terbaru
  7. push('user-{userId}', {event:'adjustment.completed', ...})
  8. pushAll('stok-global', {event:'stock.updated', updates:[...]})
```

### purchaseConsumer.js — update v4
```javascript
processPurchase(event): void
  Tambahan dari v3:
  - Setelah UPDATE stok dan status pembelian:
    Jika tipe_pembayaran='cash':
      INSERT pembayaran_pembelian {jumlah=total, metode='cash'}
    Jika tipe_pembayaran='kredit' AND jumlah_dp > 0:
      INSERT pembayaran_pembelian {jumlah=jumlah_dp, metode dari payload}
    Jika tipe_pembayaran='kredit' AND jumlah_dp = 0:
      TIDAK INSERT (belum ada pembayaran)
```

### wsService.js
```javascript
push(channel, data): void
pushAll(broadcastChannel, data): void
setChannels(channelsMap): void
```

---

## DOMAIN: REPORTING

### LaporanService — update v4
```
generateLaporanPenjualan(dari, sampai): array
generateLaporanStok(kategoriId?): array
generateLaporanPembelian(dari, sampai): array
generateLaporanVoid(dari, sampai): array
generateLaporanHutang(filters?): array          ← BARU
  Return: list hutang per supplier
  Include: total_hutang, sudah_dibayar, sisa,
           hari_tersisa, status_pembayaran
  Group by: supplier

generateLaporanPenyesuaianStok(dari, sampai): array  ← BARU
  Return: list penyesuaian stok
  Include: produk, tipe, qty, keterangan, admin, tanggal
  Summary: total kurang per tipe (rusak/hilang/expired)

getMetrikBisnis(dari, sampai): array
exportPDF(laporan): BinaryFileResponse
exportExcel(laporan): BinaryFileResponse
```

---

## MODEL ELOQUENT

### Model: `Pembelian` — Update v4
```php
// Tambah ke $fillable:
'tipe_pembayaran', 'jumlah_dp', 'sisa_hutang',
'tanggal_jatuh_tempo', 'status_pembayaran', 'catatan_pembayaran'

// Tambah $casts:
'jumlah_dp'   => 'decimal:2',
'sisa_hutang' => 'decimal:2',
'tanggal_jatuh_tempo' => 'date',

// Tambah relasi:
pembayaran(): HasMany → PembayaranPembelian
  (riwayat semua cicilan/pembayaran)

// Tambah accessor:
getTotalDibayarAttribute(): float
  return $this->pembayaran->sum('jumlah')

getHariTersisaAttribute(): ?int
  if (!$this->tanggal_jatuh_tempo) return null;
  return now()->diffInDays($this->tanggal_jatuh_tempo, false)
  // Negatif jika sudah lewat jatuh tempo

getIsJatuhTempoAttribute(): bool
  return $this->hari_tersisa !== null && $this->hari_tersisa <= 0
```

### Model: `PembayaranPembelian` — NEW
```php
$fillable = ['pembelian_id', 'user_id', 'jumlah',
             'metode', 'tanggal_bayar', 'catatan']

$casts = ['jumlah' => 'decimal:2', 'tanggal_bayar' => 'date']

// Relasi:
pembelian(): BelongsTo → Pembelian
user(): BelongsTo      → User (yang mencatat)
```

### Model: `PenyesuaianStok` — NEW
```php
$fillable = ['produk_id', 'user_id', 'tipe',
             'qty_satuan_dasar', 'keterangan', 'status']

$casts = ['qty_satuan_dasar' => 'integer']

// Relasi:
produk(): BelongsTo → Produk
user(): BelongsTo   → User

// Accessor:
getIsTipeTambahAttribute(): bool
  return $this->tipe === 'koreksi_plus'

getIsTipeKurangAttribute(): bool
  return in_array($this->tipe, [
    'barang_rusak','barang_hilang',
    'barang_expired','koreksi_minus'
  ])
```
