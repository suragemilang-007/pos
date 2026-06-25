# ALUR DATA TRANSAKSI — Contoh Nyata
> Dokumen ini menunjukkan bentuk data yang mengalir di setiap tahap transaksi.
> Contoh: Kasir menjual **2 Karton Mie Instan + 1 Dus Minyak Goreng**, bayar tunai Rp 500.000

---

## Skenario Contoh

| # | Produk | Satuan Jual | Qty | Isi Satuan | Harga/Satuan | Stok Awal (pcs) |
|---|--------|-------------|-----|------------|--------------|-----------------|
| 1 | Mie Instan ABC | Karton | 2 | 10 bungkus | Rp 145.000 | 150 bungkus |
| 2 | Minyak Goreng 2L | Dus | 1 | 12 botol | Rp 190.000 | 48 botol |

- **Total:** Rp 480.000
- **Bayar:** Rp 500.000
- **Kembalian:** Rp 20.000

---

## FASE 1 — Frontend mengirim ke Laravel

**Dari:** `transaksi.blade.php` fungsi `prosesTransaksi()`
**Ke:** `POST /api/kasir/transaksi`

### Request Header
```http
POST /api/kasir/transaksi HTTP/1.1
Host: localhost
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json
```

### Request Body (JSON)
```json
{
  "items": [
    {
      "produk_id": 1,
      "produk_satuan_id": 3,
      "nama_satuan": "Karton",
      "isi_satuan": 10,
      "qty": 2,
      "harga_satuan": 145000,
      "diskon_item": 0
    },
    {
      "produk_id": 2,
      "produk_satuan_id": 7,
      "nama_satuan": "Dus",
      "isi_satuan": 12,
      "qty": 1,
      "harga_satuan": 190000,
      "diskon_item": 0
    }
  ],
  "customer_id": 5,
  "metode_bayar": "tunai",
  "total": 480000,
  "bayar": 500000,
  "kembalian": 20000
}
```

---

## FASE 2 — Middleware AuthenticateJWT memproses token

**File:** `app/Http/Middleware/AuthenticateJWT.php`

### Input: Header Authorization
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9
               .eyJ1c2VyX2lkIjoyLCJyb2xlX2lkIjoxLCJyb2xlX25hbWUiOiJrYXNpciIsImV4cCI6MTcxOTI0MDAwMH0
               .SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c
```

### JWT Payload setelah di-decode
```json
{
  "user_id": 2,
  "role_id": 1,
  "role_name": "kasir",
  "exp": 1719240000
}
```

### Cek ke Database (tabel: sessions)
```sql
SELECT * FROM sessions
WHERE  token  = 'eyJhbGci...'
AND    status = 'active'
AND    expired_at > NOW();
-- Hasil: 1 row → valid
```

### Data yang disuntikkan ke Request
```php
// $request->merge([...]) di baris 51
$request->auth_user_id   = 2
$request->auth_role_id   = 1
$request->auth_role_name = "kasir"
$request->auth_token     = "eyJhbGci..."
```

### Jika Token Tidak Valid → Berhenti di Sini
```json
HTTP 401
{
  "message": "Token tidak valid atau sudah kedaluwarsa"
}
```

---

## FASE 3 — StoreTransaksiRequest memvalidasi data

**File:** `app/Http/Requests/Transaksi/StoreTransaksiRequest.php`

### Data yang Divalidasi
```
items                    ✓ array, 2 item
items.0.produk_id        ✓ integer, exists di tabel produk (id=1 ada)
items.0.isi_satuan       ✓ integer, min:1 (10 ✓)
items.0.qty              ✓ integer, min:1 (2 ✓)
items.0.harga_satuan     ✓ numeric, min:0 (145000 ✓)
items.1.produk_id        ✓ integer, exists di tabel produk (id=2 ada)
items.1.isi_satuan       ✓ 12 ✓
metode_bayar             ✓ in:tunai,transfer,qris ('tunai' ✓)
bayar                    ✓ numeric, min:0 (500000 ✓)
```

### Jika Validasi Gagal → Berhenti di Sini
```json
HTTP 422
{
  "message": "Data transaksi tidak valid",
  "errors": {
    "items.0.isi_satuan": [
      "isi_satuan wajib ada (digunakan untuk konversi stok)"
    ]
  }
}
```

---

## FASE 4 — Controller mengembalikan respons awal ke kasir

**File:** `app/Http/Controllers/TransaksiController.php` baris 198

### Controller memanggil Service
```php
$transaksi = $this->transaksiService->create(
    items:       $v['items'],         // 2 item
    customerId:  5,                   // dari $v['customer_id']
    userId:      2,                   // dari $request->auth_user_id (middleware)
    bayar:       500000.0,
    metodeBayar: 'tunai'
);
```

### Respons yang dikembalikan ke Browser (LANGSUNG, tidak tunggu Kafka)
```json
HTTP 202 Accepted
{
  "id_transaksi": 47,
  "no_transaksi": "TRX-20260624-00003",
  "status": "PENDING",
  "message": "Transaksi sedang diproses"
}
```

> **Kasir melihat:** Spinner + teks "Sedang Diproses..."
> Browser sekarang menunggu event WebSocket.

---

## FASE 5 — TransaksiService menyimpan ke Database

**File:** `app/Services/Transaction/TransaksiService.php`

### A. Kalkulasi di PHP
```php
$subtotal  = (2 × 145000) + (1 × 190000) = 480000
$diskon    = 0 + 0                        = 0
$total     = 480000 - 0                   = 480000
$kembalian = 500000 - 480000              = 20000
$noTransaksi = "TRX-20260624-00003"
```

### B. Query INSERT ke Database (dalam DB::transaction)

**INSERT ke tabel `transaksi`:**
```sql
INSERT INTO transaksi (
  user_id, customer_id, no_transaksi,
  subtotal, diskon, total, bayar, kembalian,
  metode_bayar, status
) VALUES (
  2, 5, 'TRX-20260624-00003',
  480000, 0, 480000, 500000, 20000,
  'tunai', 'PENDING'
);
-- AUTO_INCREMENT id = 47
```

**INSERT ke tabel `detail_transaksi` (item 1 — Mie Instan):**
```sql
INSERT INTO detail_transaksi (
  transaksi_id, produk_id, produk_satuan_id,
  nama_satuan,  isi_satuan,
  qty, harga_satuan, diskon_item, subtotal
) VALUES (
  47, 1, 3,
  'Karton', 10,
  2, 145000, 0, 290000
);
```

**INSERT ke tabel `detail_transaksi` (item 2 — Minyak Goreng):**
```sql
INSERT INTO detail_transaksi (
  transaksi_id, produk_id, produk_satuan_id,
  nama_satuan,  isi_satuan,
  qty, harga_satuan, diskon_item, subtotal
) VALUES (
  47, 2, 7,
  'Dus', 12,
  1, 190000, 0, 190000
);
```

> **DB COMMIT** — data transaksi sudah aman di database meski Kafka mati sekalipun.

---

## FASE 6 — KafkaProducerService membuat & mengirim payload

**File:** `app/Services/Kafka/KafkaProducerService.php` baris 48

### Payload yang dibentuk PHP
```json
{
  "eventType":   "transaction.created",
  "transaksiId": 47,
  "items": [
    {
      "produkId":       1,
      "produkSatuanId": 3,
      "namaSatuan":     "Karton",
      "isiSatuan":      10,
      "qty":            2,
      "hargaSatuan":    145000,
      "diskonItem":     0
    },
    {
      "produkId":       2,
      "produkSatuanId": 7,
      "namaSatuan":     "Dus",
      "isiSatuan":      12,
      "qty":            1,
      "hargaSatuan":    190000,
      "diskonItem":     0
    }
  ],
  "customerId":  5,
  "userId":      2,
  "total":       480000,
  "metodeBayar": "tunai",
  "timestamp":   "2026-06-24T09:15:32+07:00"
}
```

### HTTP POST ke Node.js (timeout 5 detik)
```http
POST http://kafka-service:3000/publish HTTP/1.1
Content-Type: application/json

{ ...payload di atas... }
```

---

## FASE 7 — Node.js httpServer menerima & meneruskan ke Kafka

**File:** `kafka-service/producers/httpServer.js` baris 91

### Input yang diterima dari Laravel
```
eventType = "transaction.created"
topicName = TOPIC_MAP["transaction.created"] = "transaction-events"
```

### Perintah publish ke Kafka
```javascript
await producer.send({
    topic:    "transaction-events",
    messages: [{
        value: '{"eventType":"transaction.created","transaksiId":47,...}'
    }]
    // idempotent=true → tidak ada duplikasi walau Laravel retry
    // acks=-1 → tunggu semua replica confirm → tidak ada data hilang
});
```

### Respons kembali ke Laravel
```json
HTTP 200
{
  "success":   true,
  "eventType": "transaction.created",
  "topic":     "transaction-events"
}
```

### Jika Kafka tidak tersedia → Respons ke Laravel
```json
HTTP 503
{
  "success": false,
  "message": "Gagal mempublikasikan event ke Kafka",
  "error":   "Connection timeout to kafka:9092"
}
```

---

## FASE 8 — Kafka Broker menyimpan pesan

**Container:** `pos_kafka` port 9092

### Pesan yang tersimpan di topic `transaction-events`
```
Topic:     transaction-events
Partition: 1   (ditentukan Kafka otomatis)
Offset:    142 (urutan ke-142 di partisi ini)
Timestamp: 1750727732000
Key:       null
Value:     {"eventType":"transaction.created","transaksiId":47,...}
```

> Pesan ini **tersimpan di disk Kafka**. Jika Consumer mati sekarang,
> pesan tidak hilang. Consumer bisa melanjutkan dari offset 142 saat restart.

---

## FASE 9 — transactionConsumer memproses event

**File:** `kafka-service/consumers/transactionConsumer.js`

### Pesan diterima Consumer
```javascript
message.value.toString()
// = '{"eventType":"transaction.created","transaksiId":47,...}'

event = JSON.parse(message.value.toString())
// eventType = "transaction.created" ✓ → lanjut proses
```

### A. validateStock() — Cek Stok Aktual

**Query untuk item 1 (Mie Instan):**
```sql
SELECT qty_stok, nama_produk
FROM   produk
WHERE  id = 1 AND status = 'aktif';

-- Hasil:
-- qty_stok   = 150
-- nama_produk = "Mie Instan ABC"
```

```javascript
qtyDibutuhkan = item.qty × item.isiSatuan = 2 × 10 = 20
// 150 >= 20 ✓ stok cukup
item._qtySaatIni    = 150
item._qtyDibutuhkan = 20
```

**Query untuk item 2 (Minyak Goreng):**
```sql
SELECT qty_stok, nama_produk
FROM   produk
WHERE  id = 2 AND status = 'aktif';

-- Hasil:
-- qty_stok    = 48
-- nama_produk = "Minyak Goreng 2L"
```

```javascript
qtyDibutuhkan = item.qty × item.isiSatuan = 1 × 12 = 12
// 48 >= 12 ✓ stok cukup
item._qtySaatIni    = 48
item._qtyDibutuhkan = 12
```

### B. Jika Stok Tidak Cukup (contoh gagal)
```sql
-- Misal stok Mie Instan hanya 15 bungkus, butuh 20
UPDATE transaksi SET status = 'FAILED' WHERE id = 47;
```

```javascript
wsService.push('user-2', {
    event:       "transaction.failed",
    transaksiId: 47,
    status:      "FAILED",
    message:     "Stok Mie Instan ABC tidak mencukupi (tersedia: 1 Karton, dibutuhkan: 2 Karton)"
})
// → lanjut ke FASE 10 path gagal
```

### C. Semua Stok Cukup → Eksekusi DB Transaction

**UPDATE stok item 1:**
```sql
UPDATE produk
SET    qty_stok = qty_stok - 20   -- 150 - 20 = 130
WHERE  id = 1 AND status = 'aktif';
```

**UPDATE stok item 2:**
```sql
UPDATE produk
SET    qty_stok = qty_stok - 12   -- 48 - 12 = 36
WHERE  id = 2 AND status = 'aktif';
```

**UPDATE status transaksi:**
```sql
UPDATE transaksi SET status = 'COMPLETED' WHERE id = 47;
```

```javascript
await conn.commit();
// ← Stok berubah. Transaksi selesai.

stokUpdates = [
    { produkId: 1, stokBaru: 130 },  // 150 - 20
    { produkId: 2, stokBaru: 36  },  // 48  - 12
]
```

---

## FASE 10 — WebSocket push ke Browser

**File:** `kafka-service/services/wsService.js`

### Push 1 — Notifikasi personal ke kasir yang bertransaksi
```javascript
wsService.push('user-2', {
    event:       "transaction.completed",
    transaksiId: 47,
    status:      "COMPLETED",
    message:     "Transaksi berhasil diproses"
})
```

**Kasir melihat di layar:**
```
✓ Transaksi Berhasil
  No: TRX-20260624-00003
  Total: Rp 480.000
  [Cetak Struk]  [Transaksi Baru]
```

### Push 2 — Broadcast stok ke SEMUA kasir aktif
```javascript
wsService.pushAll('stok-global', {
    event:   "stock.updated",
    updates: [
        { produkId: 1, stokBaru: 130 },
        { produkId: 2, stokBaru: 36  }
    ]
})
```

**Semua browser kasir yang terbuka menerima:**
```json
{
  "event":   "stock.updated",
  "updates": [
    { "produkId": 1, "stokBaru": 130 },
    { "produkId": 2, "stokBaru": 36  }
  ]
}
```

**UI semua kasir aktif diperbarui otomatis:**
```
Mie Instan ABC   : stok 150 → 130 bungkus  (tanpa refresh)
Minyak Goreng 2L : stok 48  → 36 botol     (tanpa refresh)
```

---

## Ringkasan Perubahan Data di Database

### Sebelum Transaksi
| Tabel | Kondisi |
|-------|---------|
| `transaksi` | Tidak ada baris untuk transaksi ini |
| `detail_transaksi` | Tidak ada |
| `produk` id=1 | `qty_stok = 150` |
| `produk` id=2 | `qty_stok = 48` |
| `sessions` | Token kasir: status=active |

### Setelah Transaksi COMPLETED
| Tabel | Kondisi |
|-------|---------|
| `transaksi` id=47 | `status = 'COMPLETED'`, `total = 480000` |
| `detail_transaksi` | 2 baris baru (snapshot harga & satuan tersimpan) |
| `produk` id=1 | `qty_stok = 130` (berkurang 20) |
| `produk` id=2 | `qty_stok = 36` (berkurang 12) |

---

## Ringkasan Format Data per Fase

```
FASE 1   Browser       →  Laravel     JSON body { items[], metode_bayar, bayar }
FASE 2   Header        →  Middleware  Bearer <JWT token>
FASE 3   JSON body     →  Validation  422 jika gagal
FASE 4   Validated data →  Controller  HTTP 202 { id, no_transaksi, status:PENDING }
FASE 5   PHP object    →  MySQL       INSERT transaksi + detail_transaksi
FASE 6   PHP array     →  Node.js     JSON payload { eventType, transaksiId, items[] }
FASE 7   JSON          →  Kafka       producer.send({ topic, messages:[{value}] })
FASE 8   Kafka message →  Disk        offset, partition, value (string JSON)
FASE 9   Kafka message →  MySQL       UPDATE produk qty_stok, UPDATE transaksi status
FASE 10  Node.js       →  Browser     WebSocket JSON { event, transaksiId / updates[] }
```

---

## Jika Dosen Bertanya: "Apa yang terjadi pada stok?"

```
Stok Awal Mie Instan  : 150 bungkus
Dijual                : 2 Karton × 10 bungkus = 20 bungkus
Stok Akhir            : 130 bungkus

Rumus di Consumer (FASE 9):
qty_berubah = item.qty × item.isiSatuan = 2 × 10 = 20

UPDATE produk SET qty_stok = qty_stok - 20 WHERE id = 1
```

> Stok selalu disimpan dalam **satuan dasar** (bungkus, botol, pcs).
> Konversi dari satuan jual (karton, dus) ke satuan dasar dilakukan
> **di Consumer**, bukan di Laravel — menggunakan field `isiSatuan`
> yang wajib disertakan dalam setiap payload Kafka.
