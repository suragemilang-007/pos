# PETA KODE — Sistem POS Toko Melati
> Dokumen referensi cepat untuk sidang skripsi.
> Cetak dokumen ini dan letakkan di meja saat sidang.

---

## DAFTAR ISI
1. [Struktur Folder Proyek](#1-struktur-folder-proyek)
2. [Laravel — Fitur per Fitur](#2-laravel--fitur-per-fitur)
3. [Kafka Service — Node.js](#3-kafka-service--nodejs)
4. [Tampilan Blade (Frontend)](#4-tampilan-blade-frontend)
5. [Tabel Referensi Cepat Sidang](#5-tabel-referensi-cepat-sidang)

---

## 1. Struktur Folder Proyek

```
pos/
├── laravel-app/                    ← Backend PHP (API + UI)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/        ← Menerima request, kembalikan response
│   │   │   ├── Middleware/         ← Cek token JWT + role
│   │   │   └── Requests/           ← Validasi input form
│   │   ├── Services/               ← Logika bisnis utama
│   │   ├── Models/                 ← Representasi tabel database
│   │   └── Helpers/                ← Fungsi pembantu (JWT)
│   ├── resources/views/            ← Halaman Blade (HTML + Tailwind)
│   └── routes/api.php              ← Semua endpoint API
│
├── kafka-service/                  ← Node.js (Kafka + WebSocket)
│   ├── index.js                    ← Entry point, urutan startup
│   ├── producers/httpServer.js     ← Terima HTTP dari Laravel → publish Kafka
│   ├── consumers/                  ← Proses event → update DB → push WS
│   ├── websocket/wsServer.js       ← Server WebSocket port 3001
│   └── services/wsService.js       ← Helper push ke browser
│
├── nginx/                          ← Reverse proxy (port 80)
└── docker-compose.yml              ← Konfigurasi semua container
```

---

## 2. Laravel — Fitur per Fitur

---

### LOGIN & AUTENTIKASI

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Form validasi login | `laravel-app/app/Http/Requests/Auth/LoginRequest.php` | Validasi username + password wajib diisi |
| Controller login/logout | `laravel-app/app/Http/Controllers/Auth/AuthController.php` | `login()` baris 21, `logout()` baris 37 |
| Logika cek password | `laravel-app/app/Services/Auth/UserService.php` | `Hash::check()` — verifikasi bcrypt |
| Buat & simpan token JWT | `laravel-app/app/Services/Auth/SessionService.php` | `create()` baris 24 |
| Cek token valid | `laravel-app/app/Services/Auth/SessionService.php` | `isValid()` baris 54 |
| Revoke token (logout) | `laravel-app/app/Services/Auth/SessionService.php` | `revoke()` baris 62 |
| Generate JWT | `laravel-app/app/Helpers/JwtHelper.php` | Membuat token JWT dengan payload user |
| Middleware cek token | `laravel-app/app/Http/Middleware/AuthenticateJWT.php` | Dijalankan sebelum setiap request |
| Middleware cek role | `laravel-app/app/Http/Middleware/CheckRole.php` | Cek kasir/gudang/owner |
| Halaman login (UI) | `laravel-app/resources/views/auth/login.blade.php` | Tampilan form login |

**Alur Login:**
```
Browser → POST /api/auth/login
  → LoginRequest (validasi)
  → AuthController::login()
  → UserService::login()       ← cek username + Hash::check(password)
  → SessionService::create()   ← buat JWT + simpan ke tabel sessions
  → return token
```

---

### TRANSAKSI PENJUALAN

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Validasi input transaksi | `laravel-app/app/Http/Requests/Transaksi/StoreTransaksiRequest.php` | Validasi items, metode_bayar, bayar |
| Controller transaksi | `laravel-app/app/Http/Controllers/TransaksiController.php` | `store()` baris 198 |
| **Simpan transaksi ke DB** | `laravel-app/app/Services/Transaction/TransaksiService.php` | `DB::transaction()` baris 71 |
| Hitung total transaksi | `laravel-app/app/Services/Transaction/TransaksiService.php` | `calculateTotal()` baris 133 |
| Generate nomor transaksi | `laravel-app/app/Services/Transaction/TransaksiService.php` | `generateNoTransaksi()` baris 318 |
| Publish event ke Kafka | `laravel-app/app/Services/Kafka/KafkaProducerService.php` | `publishTransactionCreated()` baris 48 |
| Kirim HTTP ke Node.js | `laravel-app/app/Services/Kafka/KafkaProducerService.php` | `publish()` baris 291 (timeout 5 detik) |
| Generate HTML struk | `laravel-app/app/Services/Transaction/TransaksiService.php` | `generateStruk()` baris 179 |
| Model transaksi | `laravel-app/app/Models/Transaksi.php` | Tabel: `transaksi` |
| Model detail item | `laravel-app/app/Models/DetailTransaksi.php` | Tabel: `detail_transaksi` |
| Halaman kasir (UI) | `laravel-app/resources/views/kasir/transaksi.blade.php` | Form input transaksi |

**Alur Simpan Transaksi:**
```
Kasir → POST /api/kasir/transaksi
  → StoreTransaksiRequest (validasi)
  → TransaksiController::store()
  → TransaksiService::create()
      ├── DB::transaction()
      │     ├── INSERT transaksi (status: PENDING)
      │     └── INSERT detail_transaksi (semua item)
      │   [COMMIT]
      └── KafkaProducerService::publishTransactionCreated()
            └── HTTP POST → kafka-service:3000/publish
                  └── Kafka topic: transaction-events
```

> **Catatan penting:** Publish Kafka dilakukan SETELAH commit DB.
> Alasannya di `TransaksiService.php` baris 57–69 (ada komentar penjelasan).

---

### VOID TRANSAKSI

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Controller void | `laravel-app/app/Http/Controllers/VoidTransaksiController.php` | `store()` |
| Cek syarat void | `laravel-app/app/Services/Transaction/VoidTransaksiService.php` | `checkVoidEligibility()` |
| Publish void ke Kafka | `laravel-app/app/Services/Kafka/KafkaProducerService.php` | `publishTransactionVoid()` baris 201 |
| Model void | `laravel-app/app/Models/VoidTransaksi.php` | Tabel: `void_transaksi` |
| Halaman void (UI) | `laravel-app/resources/views/kasir/void-transaksi.blade.php` | |

**Syarat Void (4 kondisi):**
1. Status transaksi = `COMPLETED`
2. Dilakukan < 30 menit setelah transaksi (`VOID_TIME_LIMIT_MINUTES=30`)
3. Belum pernah di-void sebelumnya
4. Belum ada retur untuk transaksi ini

---

### RETUR PENJUALAN

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Validasi input retur | `laravel-app/app/Http/Requests/Retur/StoreReturPenjualanRequest.php` | |
| Controller retur jual | `laravel-app/app/Http/Controllers/ReturPenjualanController.php` | |
| Logika retur jual | `laravel-app/app/Services/Transaction/ReturPenjualanService.php` | |
| Publish ke Kafka | `laravel-app/app/Services/Kafka/KafkaProducerService.php` | `publishSaleReturnRequested()` baris 125 |
| Model retur jual | `laravel-app/app/Models/ReturPenjualan.php` | Tabel: `retur_penjualan` |
| Halaman retur (UI) | `laravel-app/resources/views/kasir/retur-penjualan.blade.php` | |

---

### PEMBELIAN / RESTOCK

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Validasi pembelian | `laravel-app/app/Http/Requests/Pembelian/StorePembelianRequest.php` | Termasuk tipe_pembayaran, jatuh_tempo |
| Controller pembelian | `laravel-app/app/Http/Controllers/PembelianController.php` | |
| Logika pembelian | `laravel-app/app/Services/Purchasing/PembelianService.php` | |
| Publish ke Kafka | `laravel-app/app/Services/Kafka/KafkaProducerService.php` | `publishPurchaseCreated()` baris 86 |
| Model pembelian | `laravel-app/app/Models/Pembelian.php` | Tabel: `pembelian` |
| Halaman buat pembelian (UI) | `laravel-app/resources/views/gudang/pembelian/create.blade.php` | |
| Halaman daftar pembelian (UI) | `laravel-app/resources/views/gudang/pembelian/index.blade.php` | |

---

### HUTANG DAGANG (PEMBAYARAN)

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Validasi pembayaran | `laravel-app/app/Http/Requests/Purchasing/StorePembayaranRequest.php` | |
| Controller pembayaran | `laravel-app/app/Http/Controllers/PembayaranPembelianController.php` | |
| **Logika bayar hutang** | `laravel-app/app/Services/Purchasing/PembayaranPembelianService.php` | Sinkron — TIDAK via Kafka |
| Model pembayaran | `laravel-app/app/Models/PembayaranPembelian.php` | Tabel: `pembayaran_pembelian` |
| Halaman hutang (UI) | `laravel-app/resources/views/gudang/hutang/index.blade.php` | |

> **Mengapa TIDAK via Kafka?** Pembayaran hutang tidak mengubah stok.
> Cukup sinkron langsung ke DB. Penjelasan di `CLAUDE.md` seksi 7.3.

---

### PENYESUAIAN STOK

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Validasi penyesuaian | `laravel-app/app/Http/Requests/Inventory/StorePenyesuaianStokRequest.php` | Tipe: rusak/hilang/expired/koreksi |
| Controller | `laravel-app/app/Http/Controllers/PenyesuaianStokController.php` | |
| Logika penyesuaian | `laravel-app/app/Services/Inventory/PenyesuaianStokService.php` | Validasi stok cukup sebelum kurangi |
| Publish ke Kafka | `laravel-app/app/Services/Kafka/KafkaProducerService.php` | `publishStockAdjustment()` baris 259 |
| Model | `laravel-app/app/Models/PenyesuaianStok.php` | Tabel: `penyesuaian_stok` |
| Halaman (UI) | `laravel-app/resources/views/gudang/penyesuaian-stok/index.blade.php` | |

---

### RETUR PEMBELIAN

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Validasi | `laravel-app/app/Http/Requests/Retur/StoreReturPembelianRequest.php` | |
| Controller | `laravel-app/app/Http/Controllers/ReturPembelianController.php` | |
| Logika | `laravel-app/app/Services/Purchasing/ReturPembelianService.php` | |
| Publish ke Kafka | `laravel-app/app/Services/Kafka/KafkaProducerService.php` | `publishPurchaseReturnRequested()` baris 157 |
| Model | `laravel-app/app/Models/ReturPembelian.php` | |

---

### PRODUK & STOK

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Controller produk | `laravel-app/app/Http/Controllers/ProdukController.php` | CRUD produk + stok real-time |
| Logika produk | `laravel-app/app/Services/Inventory/ProdukService.php` | |
| Controller satuan | `laravel-app/app/Http/Controllers/ProdukSatuanController.php` | Multi-unit pricing |
| Logika satuan | `laravel-app/app/Services/Inventory/ProdukSatuanService.php` | |
| Model produk | `laravel-app/app/Models/Produk.php` | Tabel: `produk` (ada `qty_stok`) |
| Model satuan | `laravel-app/app/Models/ProdukSatuan.php` | Tabel: `produk_satuan` (ada `isi_satuan`) |
| Halaman produk (UI) | `laravel-app/resources/views/gudang/produk/index.blade.php` | |
| Halaman stok kasir (UI) | `laravel-app/resources/views/kasir/stok.blade.php` | Pantau stok real-time |

---

### LAPORAN

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Controller laporan | `laravel-app/app/Http/Controllers/LaporanController.php` | |
| Logika laporan | `laravel-app/app/Services/Reporting/LaporanService.php` | Semua jenis laporan |
| Halaman laporan (UI) | `laravel-app/resources/views/owner/laporan/index.blade.php` | |

---

### SEMUA ENDPOINT API

| File | Isi |
|------|-----|
| `laravel-app/routes/api.php` | Semua route: login, transaksi, pembelian, retur, hutang, stok, laporan |

**Ringkasan endpoint penting:**
```
POST   /api/auth/login                      ← Login
POST   /api/auth/logout                     ← Logout

POST   /api/kasir/transaksi                 ← Buat transaksi (async Kafka)
GET    /api/kasir/transaksi/{id}            ← Detail transaksi
GET    /api/kasir/transaksi/{id}/struk      ← Cetak struk
POST   /api/kasir/transaksi/{id}/void       ← Void transaksi (async Kafka)
POST   /api/kasir/retur-penjualan           ← Retur dari customer

POST   /api/gudang/pembelian                ← Buat pembelian (async Kafka)
POST   /api/gudang/pembelian/{id}/bayar     ← Bayar hutang (SINKRON)
POST   /api/gudang/retur-pembelian          ← Retur ke supplier (async Kafka)
POST   /api/gudang/penyesuaian-stok         ← Koreksi stok (async Kafka)

GET    /api/owner/laporan/{jenis}           ← Laporan (penjualan/stok/dll)
```

---

## 3. Kafka Service — Node.js

---

### PRODUCER (Terima dari Laravel)

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Entry point startup | `kafka-service/index.js` | Urutan: DB → WS → HTTP → Consumers |
| **Server HTTP port 3000** | `kafka-service/producers/httpServer.js` | Terima POST /publish dari Laravel |
| Konfigurasi Kafka | `kafka-service/config/kafka.js` | Nama topics, koneksi broker |
| Konfigurasi MySQL | `kafka-service/config/database.js` | Connection pool ke MySQL |

---

### CONSUMERS (Proses Event → Update Stok)

| Consumer | File | Menangani Event | Operasi Stok |
|----------|------|-----------------|--------------|
| Transaksi | `kafka-service/consumers/transactionConsumer.js` | `transaction.created` | **KURANGI** stok (penjualan) |
| Void | `kafka-service/consumers/voidConsumer.js` | `transaction.void.requested` | **TAMBAH** stok kembali |
| Pembelian | `kafka-service/consumers/purchaseConsumer.js` | `purchase.created` | **TAMBAH** stok (restock) |
| Retur | `kafka-service/consumers/returnConsumer.js` | `sale.return.requested` / `purchase.return.requested` | TAMBAH (retur jual) / KURANG (retur beli) |
| Penyesuaian | `kafka-service/consumers/adjustmentConsumer.js` | `stock.adjustment.requested` | TAMBAH atau KURANGI (tergantung tipe) |

**Rumus konversi stok di setiap consumer:**
```javascript
qty_berubah = item.qty × item.isiSatuan
// Contoh: jual 2 karton (isi 24) → kurangi stok 48 pcs
```

---

### WEBSOCKET (Push ke Browser)

| Kode | File | Penjelasan Singkat |
|------|------|--------------------|
| Server WebSocket port 3001 | `kafka-service/websocket/wsServer.js` | Kelola koneksi browser |
| Helper push notifikasi | `kafka-service/services/wsService.js` | `push()` personal, `pushAll()` broadcast |

**Event WebSocket yang dikirim ke browser:**
```
transaction.completed  → kasir tahu transaksi berhasil
transaction.failed     → kasir tahu transaksi gagal (stok kurang)
void.completed         → konfirmasi void berhasil
purchase.completed     → gudang tahu restock berhasil
return.sale.completed  → konfirmasi retur diterima
adjustment.completed   → konfirmasi penyesuaian stok berhasil
stock.updated          → SEMUA kasir aktif → stok di layar diperbarui otomatis
```

---

## 4. Tampilan Blade (Frontend)

| Halaman | File | Role yang Akses |
|---------|------|-----------------|
| Login | `resources/views/auth/login.blade.php` | Semua |
| Dashboard kasir | `resources/views/kasir/dashboard.blade.php` | Kasir |
| **Form transaksi** | `resources/views/kasir/transaksi.blade.php` | Kasir |
| Pantau stok real-time | `resources/views/kasir/stok.blade.php` | Kasir |
| Retur penjualan | `resources/views/kasir/retur-penjualan.blade.php` | Kasir |
| Void transaksi | `resources/views/kasir/void-transaksi.blade.php` | Kasir |
| Data customer | `resources/views/kasir/customer/index.blade.php` | Kasir |
| Riwayat customer | `resources/views/kasir/customer/riwayat.blade.php` | Kasir |
| Dashboard gudang | `resources/views/gudang/dashboard.blade.php` | Admin Gudang |
| Data produk | `resources/views/gudang/produk/index.blade.php` | Admin Gudang |
| Form produk | `resources/views/gudang/produk/form.blade.php` | Admin Gudang |
| **Buat pembelian** | `resources/views/gudang/pembelian/create.blade.php` | Admin Gudang |
| Daftar pembelian | `resources/views/gudang/pembelian/index.blade.php` | Admin Gudang |
| Detail pembelian | `resources/views/gudang/pembelian/show.blade.php` | Admin Gudang |
| Hutang dagang | `resources/views/gudang/hutang/index.blade.php` | Admin Gudang |
| Penyesuaian stok | `resources/views/gudang/penyesuaian-stok/index.blade.php` | Admin Gudang |
| Promo | `resources/views/gudang/promo/index.blade.php` | Admin Gudang |
| Data supplier | `resources/views/gudang/supplier/index.blade.php` | Admin Gudang |
| Kategori produk | `resources/views/gudang/kategori/index.blade.php` | Admin Gudang |
| Dashboard owner | `resources/views/owner/dashboard.blade.php` | Owner |
| Daftar transaksi | `resources/views/owner/transaksi/index.blade.php` | Owner |
| Laporan | `resources/views/owner/laporan/index.blade.php` | Owner |
| Manajemen user | `resources/views/owner/user/index.blade.php` | Owner |

---

## 5. Tabel Referensi Cepat Sidang

> Gunakan tabel ini ketika dosen bertanya "tunjukkan kode ..."

---

### Pertanyaan → File → Baris

| Dosen Bertanya | Buka File Ini | Langsung ke Baris |
|----------------|---------------|-------------------|
| "Kode login di mana?" | `Controllers/Auth/AuthController.php` | Baris 21 |
| "Bagaimana cek password?" | `Services/Auth/UserService.php` | Cari `Hash::check` |
| "Kode buat token JWT?" | `Services/Auth/SessionService.php` | Baris 24 |
| "Kode simpan transaksi?" | `Services/Transaction/TransaksiService.php` | Baris 71 |
| "Mengapa publish di luar DB transaction?" | `Services/Transaction/TransaksiService.php` | Baris 57–69 |
| "Kode hitung total transaksi?" | `Services/Transaction/TransaksiService.php` | Baris 133 |
| "Kode generate nomor transaksi?" | `Services/Transaction/TransaksiService.php` | Baris 318 |
| "Kode publish ke Kafka?" | `Services/Kafka/KafkaProducerService.php` | Baris 291 |
| "Kode kalau Kafka mati?" | `Services/Kafka/KafkaProducerService.php` | Baris 323 |
| "Kode isi_satuan dikirim ke Kafka?" | `Services/Kafka/KafkaProducerService.php` | Baris 56–64 |
| "Kode Controller transaksi?" | `Controllers/TransaksiController.php` | Baris 198 |
| "Kode tangani error Kafka di Controller?" | `Controllers/TransaksiController.php` | Baris 218 |
| "Kode syarat void transaksi?" | `Services/Transaction/VoidTransaksiService.php` | Cari `checkVoidEligibility` |
| "Kode consumer kurangi stok?" | `kafka-service/consumers/transactionConsumer.js` | Cari `isiSatuan` |
| "Kode konversi multi-unit?" | `kafka-service/consumers/transactionConsumer.js` | `qty * isiSatuan` |
| "Kode WebSocket push ke kasir?" | `kafka-service/services/wsService.js` | Cari `push` / `pushAll` |
| "Kode bayar hutang?" | `Services/Purchasing/PembayaranPembelianService.php` | Langsung ke DB |
| "Kode penyesuaian stok?" | `Services/Inventory/PenyesuaianStokService.php` | Validasi qty <= stok |
| "Kode cegah stok negatif?" | `Services/Inventory/PenyesuaianStokService.php` | Cari validasi stok |
| "Semua endpoint ada di mana?" | `routes/api.php` | Baris 36 ke bawah |
| "Middleware cek token?" | `Http/Middleware/AuthenticateJWT.php` | Seluruh file |
| "Middleware cek role?" | `Http/Middleware/CheckRole.php` | Seluruh file |

---

### Kata Kunci Ctrl+Shift+F (Search Global)

| Dosen Tanya Tentang | Kata Kunci yang Dicari |
|---------------------|------------------------|
| Simpan transaksi | `DB::transaction` |
| Cek password login | `Hash::check` |
| Publish ke Kafka | `publishTransactionCreated` |
| Penanganan Kafka mati | `ConnectionException` |
| Konversi multi-unit | `isiSatuan` |
| Generate nomor transaksi | `generateNoTransaksi` |
| Syarat void | `VOID_TIME_LIMIT` atau `checkVoidEligibility` |
| Push WebSocket | `pushAll` atau `stock.updated` |
| Bayar hutang sinkron | `PembayaranPembelianService` |
| Cegah stok negatif | `qty_stok` atau `isStokCukup` |
| Status lifecycle | `PENDING` atau `COMPLETED` |

---

### Kalimat Tenang Saat Panik

Jika butuh waktu mencari, ucapkan salah satu kalimat berikut:

> *"Baik, izinkan saya buka file yang relevan."*

> *"Kode tersebut ada di Service layer, sebentar saya tunjukkan."*

> *"Ini ada di KafkaProducerService, saya buka dulu."*

Kalimat ini memberi Anda 10–15 detik tanpa terlihat gugup.

---

*Dokumen ini dibuat untuk kemudahan navigasi kode saat sidang skripsi.*
*Cetak halaman ini dan letakkan di samping laptop.*

---

## 6. Alur Lengkap Kode Transaksi (Frontend → Selesai)

> Alur ini menelusuri SETIAP file yang dieksekusi saat kasir menekan tombol "Bayar".
> Gunakan untuk menjawab pertanyaan "tunjukkan bagaimana transaksi berjalan dari awal sampai akhir".

---

```
FASE 1   Frontend Blade
FASE 2   Middleware Laravel
FASE 3   Validasi Request
FASE 4   Controller
FASE 5   Service (DB)
FASE 6   KafkaProducerService
FASE 7   Node.js httpServer
FASE 8   Kafka Broker
FASE 9   Node.js Consumer
FASE 10  WebSocket → Browser
```

---

### FASE 1 — Frontend Blade
**File:** `resources/views/kasir/transaksi.blade.php`

```
Kasir scan barcode / ketik kode produk
    → input#input-kode (baris 20)
    → oninput="onInputKode()" → fetch GET /api/kasir/stok?kode=...

Kasir pilih satuan → onSatuanChange() → harga & stok diperbarui
Kasir input qty   → onQtyChange()    → subtotal dihitung ulang
Kasir klik [+ Keranjang]             → item masuk array keranjang[]

Kasir input nominal bayar → pilih metode (tunai/transfer/qris)
Kasir klik [Bayar / Proses Transaksi]

    → JavaScript kirim:
      axios.post('/api/kasir/transaksi', {
          items: [
              {
                  produk_id,
                  produk_satuan_id,
                  nama_satuan,      // snapshot
                  isi_satuan,       // WAJIB untuk konversi stok
                  qty,
                  harga_satuan,
                  diskon_item
              },
              ...
          ],
          customer_id,   // nullable
          metode_bayar,  // tunai | transfer | qris
          total,
          bayar,
          kembalian
      })

    → Browser juga subscribe WebSocket:
      ws.subscribe('user-{userId}')   // notifikasi personal
      ws.subscribe('stok-global')     // update stok semua kasir
```

---

### FASE 2 — Middleware Laravel
**File 1:** `app/Http/Middleware/AuthenticateJWT.php`
**File 2:** `app/Http/Middleware/CheckRole.php`

```
Request masuk → Route: POST /api/kasir/transaksi
    → middleware ['auth.jwt', 'role:kasir,owner']

AuthenticateJWT:
    → Ambil token dari header Authorization
    → JwtHelper::verify(token)       // decode & validasi signature
    → SessionService::isValid(token) // cek status='active' di tabel sessions
    → Jika valid: inject auth_user_id, auth_role_name ke request
    → Jika tidak: return 401

CheckRole:
    → Cek auth_role_name IN ['kasir', 'owner']
    → Jika tidak sesuai: return 403
```

---

### FASE 3 — Validasi Request
**File:** `app/Http/Requests/Transaksi/StoreTransaksiRequest.php`

```
rules() → dipanggil otomatis oleh Laravel sebelum Controller:

  items                    → required, array, min:1
  items.*.produk_id        → required, exists:produk,id
  items.*.produk_satuan_id → nullable, exists:produk_satuan,id
  items.*.nama_satuan      → required, string
  items.*.isi_satuan       → required, integer, min:1   ← KRITIS
  items.*.qty              → required, integer, min:1
  items.*.harga_satuan     → required, numeric, min:0
  items.*.diskon_item      → nullable, numeric, min:0
  customer_id              → nullable, exists:customers,id
  metode_bayar             → required, in:tunai,transfer,qris
  bayar                    → required, numeric, min:0

Jika gagal → failedValidation() → return HTTP 422
    {
        "message": "Data transaksi tidak valid",
        "errors": { "field": ["pesan error"] }
    }

Jika lolos → lanjut ke Controller
```

---

### FASE 4 — Controller
**File:** `app/Http/Controllers/TransaksiController.php` — baris 198

```php
public function store(StoreTransaksiRequest $request): JsonResponse
{
    try {
        $v = $request->validated();

        $transaksi = $this->transaksiService->create(
            items:       $v['items'],
            customerId:  $v['customer_id'] ?? null,
            userId:      $request->input('auth_user_id'),  // dari middleware
            bayar:       (float) $v['bayar'],
            metodeBayar: $v['metode_bayar']
        );

        return response()->json([
            'id_transaksi' => $transaksi->id,
            'no_transaksi' => $transaksi->no_transaksi,
            'status'       => 'PENDING',    // ← bukan COMPLETED
            'message'      => 'Transaksi sedang diproses',
        ], 202);                            // ← 202 Accepted (async)

    } catch (\RuntimeException $e) {
        // Kafka tidak bisa dijangkau
        return response()->json([
            'message' => 'Gagal mengirim transaksi ke antrian: ' . $e->getMessage(),
        ], 503);
    }
}
```

> **202 diterima kasir** → Frontend tampilkan "Sedang Diproses..." dan
> MULAI menunggu event WebSocket. Kasir tidak diblokir.

---

### FASE 5 — Service (Simpan ke Database)
**File:** `app/Services/Transaction/TransaksiService.php` — baris 42

```php
public function create(...): Transaksi
{
    // a. Hitung nilai keuangan
    $subtotal = collect($items)->sum(fn($i) => $i['harga_satuan'] * $i['qty']);
    $diskon   = collect($items)->sum(fn($i) => $i['diskon_item'] ?? 0);
    $total    = $this->calculateTotal($items);   // baris 133
    $kembalian = $bayar - $total;

    // b. Generate nomor dokumen: TRX-YYYYMMDD-XXXXX
    $noTransaksi = $this->generateNoTransaksi();  // baris 318

    // c & d. INSERT dalam satu DB transaction
    $transaksi = DB::transaction(function () use (...) {

        $trx = Transaksi::create([         // INSERT ke tabel transaksi
            'no_transaksi' => $noTransaksi,
            'status'       => 'PENDING',   // ← Consumer yang ubah ke COMPLETED
            'total'        => $total,
            ...
        ]);

        foreach ($items as $item) {
            DetailTransaksi::create([      // INSERT ke tabel detail_transaksi
                'transaksi_id'    => $trx->id,
                'isi_satuan'      => $item['isi_satuan'], // snapshot
                'harga_satuan'    => $item['harga_satuan'],
                ...
            ]);
        }

        return $trx;
    });
    // ← DB COMMIT di sini

    // e. Publish ke Kafka SETELAH commit (baris 57-69 ada penjelasan mengapa)
    $this->kafkaProducer->publishTransactionCreated($transaksi);

    return $transaksi; // status masih PENDING
}
```

---

### FASE 6 — KafkaProducerService
**File:** `app/Services/Kafka/KafkaProducerService.php`

```php
// publishTransactionCreated() — baris 48
// Susun payload dengan isi_satuan wajib ada
$payload = [
    'eventType'   => 'transaction.created',
    'transaksiId' => $transaksi->id,
    'items'       => $transaksi->details->map(fn($d) => [
        'produkId'   => $d->produk_id,
        'isiSatuan'  => (int) $d->isi_satuan,  // WAJIB untuk konversi stok
        'qty'        => (int) $d->qty,
        ...
    ])->all(),
    'userId'    => $transaksi->user_id,
    'timestamp' => now()->toIso8601String(),
];

// publish() — baris 291
// HTTP POST ke Node.js, timeout 5 detik
$response = Http::timeout(5)
    ->acceptJson()
    ->post('http://kafka-service:3000/publish', $payload);

// Jika HTTP gagal → throw RuntimeException → Controller tangkap → 503
// Jika HTTP sukses → return true → Controller kembalikan 202 ke kasir
```

---

### FASE 7 — Node.js HTTP Server
**File:** `kafka-service/producers/httpServer.js` — baris 91

```javascript
app.post('/publish', async (req, res) => {
    const { eventType } = req.body;

    // Routing: eventType → nama topic Kafka
    // 'transaction.created' → TOPICS.TRANSACTION ('transaction-events')
    const topicName = TOPIC_MAP[eventType];

    // Publish ke Kafka (idempotent=true → tidak ada duplikasi meski retry)
    await producer.send({
        topic:    topicName,
        messages: [{ value: JSON.stringify(body) }],
        // acks: -1 (all replicas) — pesan tidak hilang walau leader broker crash
    });

    return res.status(200).json({ success: true });
    // ← Node.js balas 200 ke Laravel
    // ← Laravel meneruskan 202 ke Frontend (sudah terjadi di FASE 4)
});
```

---

### FASE 8 — Kafka Broker
**Container:** `pos_kafka` — port 9092

```
Topic: transaction-events
    Partisi: 3
    Replication Factor: 1

Event masuk ke partisi (ditentukan Kafka otomatis)
    → Tersimpan di disk Kafka (durable)
    → Consumer group 'pos-transaction-group' mendapat notifikasi ada pesan baru
    → transactionConsumer.js mulai mengambil pesan
```

---

### FASE 9 — Node.js Consumer (Proses Stok)
**File:** `kafka-service/consumers/transactionConsumer.js`

```javascript
// eachMessage — baris 265
// Diproses SEKUENSIAL (satu per satu) — tidak paralel

// Step 1: Parse JSON
event = JSON.parse(message.value.toString());

// Step 2: Filter eventType
if (event.eventType !== 'transaction.created') return;

// Step 3: validateStock() — baris 46
// Cek stok AKTUAL di DB per item
for (const item of items) {
    const qtyDibutuhkan = item.qty * item.isiSatuan; // ← konversi multi-unit

    SELECT qty_stok FROM produk WHERE id = item.produkId

    if (qty_stok < qtyDibutuhkan) {
        // → markFailed() → UPDATE transaksi FAILED
        // → wsService.push('user-{userId}', { event: 'transaction.failed' })
        return;
    }
}

// Step 4: Semua stok cukup → beginTransaction()
const conn = await pool.getConnection();
await conn.beginTransaction();

    // Kurangi stok setiap item
    UPDATE produk
    SET    qty_stok = qty_stok - (item.qty × item.isiSatuan)
    WHERE  id = item.produkId

    // Update status transaksi
    UPDATE transaksi SET status = 'COMPLETED' WHERE id = transaksiId

await conn.commit();
conn.release(); // WAJIB — kembalikan koneksi ke pool

// Step 5: Jika error di tengah → ROLLBACK → markFailed()
//         baris 210-237
```

---

### FASE 10 — WebSocket Push ke Browser
**File:** `kafka-service/services/wsService.js`

```javascript
// Notifikasi personal ke kasir yang melakukan transaksi
wsService.push(`user-${userId}`, {
    event:       'transaction.completed',
    transaksiId: transaksiId,
    status:      'COMPLETED',
    message:     'Transaksi berhasil diproses',
});

// Broadcast stok terbaru ke SEMUA kasir yang sedang aktif
wsService.pushAll('stok-global', {
    event:   'stock.updated',
    updates: [
        { produkId: 1, stokBaru: 47 },  // stok setelah dikurangi
        ...
    ],
});

// wsService.push() — baris 93
// Iterate semua subscriber channel → ws.send(JSON.stringify(data))
// Koneksi mati otomatis dibersihkan
```

```
Browser kasir menerima event WebSocket:

  'transaction.completed'
      → Tampilan berubah: "Transaksi Berhasil"
      → Tombol "Cetak Struk" muncul
      → Keranjang dikosongkan

  'stock.updated'
      → Stok di layar SEMUA kasir aktif diperbarui otomatis
      → Tanpa refresh halaman
```

---

### Ringkasan Visual Lengkap

```
[KASIR klik Bayar]
      │
      ▼ axios.post('/api/kasir/transaksi', payload)
[NGINX :80]
      │
      ▼ PHP-FPM
[AuthenticateJWT] → cek token JWT + status di DB sessions
      │
[CheckRole]       → cek role kasir/owner
      │
[StoreTransaksiRequest] → validasi 422 jika gagal
      │
[TransaksiController::store()]
      │
[TransaksiService::create()]
      ├── calculateTotal()
      ├── generateNoTransaksi()       → TRX-20260624-00001
      ├── DB::transaction()
      │     ├── INSERT transaksi      → status: PENDING
      │     └── INSERT detail_transaksi (semua item + snapshot)
      │   [COMMIT]
      └── KafkaProducerService::publishTransactionCreated()
              │
              ▼ HTTP POST :3000/publish (timeout 5 detik)
      [Node.js httpServer.js]
              │
              ▼ producer.send() idempotent=true
      [pos_kafka :9092]
              │ topic: transaction-events
              ▼
      [transactionConsumer.js eachMessage()]
              ├── validateStock(): qty × isiSatuan per item
              │
              ├── [Stok TIDAK cukup]
              │     └── UPDATE transaksi FAILED
              │         push WebSocket: transaction.failed
              │
              └── [Stok CUKUP]
                    ├── BEGIN
                    ├── UPDATE produk qty_stok -= (qty × isiSatuan)
                    ├── UPDATE transaksi status = COMPLETED
                    ├── COMMIT
                    ├── push('user-{id}') → transaction.completed
                    └── pushAll('stok-global') → stock.updated
                              │
                              ▼ WebSocket
              [Browser Kasir]
                    ├── Tampilkan: "Transaksi Berhasil"
                    ├── Tampilkan tombol Cetak Struk
                    └── Stok di layar semua kasir diperbarui otomatis
```

---

### File yang Terlibat — Urutan Eksekusi

| Urutan | File | Peran |
|--------|------|-------|
| 1 | `resources/views/kasir/transaksi.blade.php` | UI kasir, kirim request |
| 2 | `app/Http/Middleware/AuthenticateJWT.php` | Cek token JWT |
| 3 | `app/Http/Middleware/CheckRole.php` | Cek role |
| 4 | `app/Http/Requests/Transaksi/StoreTransaksiRequest.php` | Validasi input |
| 5 | `app/Http/Controllers/TransaksiController.php` | Orchestrate, return 202 |
| 6 | `app/Services/Transaction/TransaksiService.php` | Hitung, generate no, simpan DB |
| 7 | `app/Services/Kafka/KafkaProducerService.php` | Susun payload, HTTP POST |
| 8 | `kafka-service/producers/httpServer.js` | Terima dari Laravel, publish Kafka |
| 9 | `pos_kafka` (Kafka Broker) | Simpan event di disk |
| 10 | `kafka-service/consumers/transactionConsumer.js` | Validasi stok, update DB |
| 11 | `kafka-service/services/wsService.js` | Push notifikasi ke browser |
| 12 | `resources/views/kasir/transaksi.blade.php` | Terima WS, update UI |
