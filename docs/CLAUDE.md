# CLAUDE.md — Sistem POS Toko Grosir (Event-Driven)
> **Versi 2.0** — Update: Multi-Unit Pricing

## Identitas Proyek
- **Nama:** Sistem POS Toko Melati
- **Skripsi:** "Perancangan Sistem Point of Sales Toko Grosir dengan Antarmuka Real-Time Berbasis Arsitektur Event Streaming"
- **Stack:** Laravel 11 + Node.js + Apache Kafka + MySQL 8 + WebSocket + Docker

---

## 1. Arsitektur Sistem (WAJIB DIIKUTI)

```
Browser (Blade + Axios)
    │
    ▼ :80
  NGINX (Reverse Proxy)
    │
    ├── PHP-FPM ──► pos_laravel (Laravel 11 API)
    │                    │
    │                    │ HTTP POST http://kafka-service:3000
    │                    ▼
    │              pos_kafka_service (Node.js)
    │                    │ Producer → Kafka Topic
    │                    │              │
    │                    │         pos_kafka (Broker)
    │                    │              │
    │                    │         Consumer ←┘
    │                    │              │ UPDATE stok (dengan konversi satuan)
    │                    │         pos_mysql (MySQL 8)
    │                    │
    └── WebSocket ──► pos_kafka_service:3001 (Push real-time)
```

### Mana yang lewat Kafka, mana yang tidak:

| Operasi | Via Kafka? | Alasan |
|---|---|---|
| Input transaksi penjualan | YES | Berdampak stok real-time |
| Pembelian / restock | YES | Berdampak stok real-time |
| Retur penjualan | YES | Berdampak stok real-time |
| Retur pembelian | YES | Berdampak stok real-time |
| Login / Logout | NO | Operasi sesi |
| CRUD produk + satuan | NO | Data referensi |
| CRUD customer / supplier | NO | Data referensi |
| Lihat laporan | NO | Read-only |
| Cetak struk | NO | Read-only |

---

## 2. Konsep Multi-Unit Pricing (WAJIB DIPAHAMI)

### Prinsip Dasar
Satu produk dapat memiliki beberapa satuan dengan harga berbeda.
Semakin besar satuan, semakin murah harga efektif per satuan terkecil.

```
Kopi Kapal Api 165g:
  satuan_dasar = 'pcs'
  qty_stok     = 960 (selalu dalam satuan_dasar)

  produk_satuan:
    pcs      isi=1   harga_jual=3.500    -> Rp 3.500/pcs
    renteng  isi=10  harga_jual=31.500   -> Rp 3.150/pcs (hemat 10%)
    karton   isi=96  harga_jual=280.000  -> Rp 2.916/pcs (hemat 16%)
```

### Aturan Konversi Stok (KRITIS)
```
SETIAP operasi stok wajib menggunakan konversi:

qty_stok_berkurang = qty x isi_satuan   (transaksi jual, retur pembelian)
qty_stok_bertambah = qty x isi_satuan   (pembelian, retur penjualan)

Contoh konkret:
  Kasir jual 2 karton  -> stok berkurang: 2 x 96 = 192 pcs
  Admin beli 5 renteng -> stok bertambah: 5 x 10 = 50 pcs
```

### Mengapa Stok Disimpan dalam Satuan Terkecil?
Menyimpan stok dalam satuan_dasar memungkinkan:
1. Perbandingan stok yang konsisten (selalu dalam unit yang sama)
2. Penjualan 1 karton dan 3 pcs dari stok yang sama tanpa konflik
3. Laporan stok yang akurat tanpa konversi ulang

---

## 3. Aturan Mutlak (Jangan Dilanggar)

### 3.1 Zero Hallucination Database
- **DILARANG** membuat kolom atau tabel yang tidak ada di `docs/ERD.md`
- Tabel `produk` tidak punya kolom `harga_jual` atau `satuan ENUM` — semua ada di `produk_satuan`
- Tipe data wajib sesuai ERD: harga = `DECIMAL(10,2)`, bukan `FLOAT`
- Nama tabel: `snake_case` plural

### 3.2 Async First untuk Operasi Stok
- **DILARANG** menulis `Transaksi::create()` langsung di Controller untuk transaksi
- Alur wajib: `Controller → Service → KafkaProducerService → Kafka`
- Consumer yang menulis ke Database, **bukan** Controller

### 3.3 Konversi Satuan di Consumer (KRITIS)
- **DILARANG** update stok langsung dengan `qty` tanpa dikalikan `isi_satuan`
- Consumer wajib menggunakan: `qty_berubah = item.qty x item.isiSatuan`
- `isiSatuan` harus ada dalam payload Kafka event (dikirim dari Laravel)

### 3.4 Snapshot di Detail Transaksi
- `detail_transaksi` wajib menyimpan `nama_satuan`, `isi_satuan`, `harga_satuan` sebagai snapshot
- **DILARANG** join ke `produk_satuan` untuk menampilkan riwayat transaksi lama
- Snapshot melindungi data historis dari perubahan harga/satuan di masa depan

### 3.5 Status Lifecycle (Jangan Tambah Status Baru)
```
Transaksi:       PENDING -> COMPLETED | FAILED
Pembelian:       PENDING -> COMPLETED | CANCELLED
Retur Penjualan: PENDING -> COMPLETED | REJECTED
Retur Pembelian: PENDING -> COMPLETED | REJECTED
```

### 3.6 Konsistensi Naming
- Endpoint sesuai `docs/SEQUENCE_FLOWS.md`
- Method PHP sesuai `docs/CLASS_DIAGRAM.md`
- Event Kafka: `transaction.created`, `purchase.created`, `sale.return.requested`, `purchase.return.requested`

### 3.7 Soft Delete Pattern
- **DILARANG** hard delete jika entitas pernah dipakai di transaksi
- Cek `COUNT(*)` di tabel relasi sebelum hapus
- Gunakan kolom `status ENUM('aktif','nonaktif')`

### 3.8 Password Security
- **SELALU** hash dengan `bcrypt` sebelum simpan
- **JANGAN** kembalikan `password_hash` dalam response API

### 3.9 Docker — Hostname Container
- **DILARANG** `localhost` untuk koneksi antar container
- Gunakan nama service: `mysql`, `kafka`, `kafka-service`

---

## 4. Alur Kerja Implementasi Fitur Baru

```
1. Baca docs/ACTIVITY_FLOWS.md  -> pahami alur aktivitas
2. Baca docs/SEQUENCE_FLOWS.md  -> pahami endpoint dan payload
3. Baca docs/ERD.md             -> identifikasi tabel yang terlibat
4. Baca docs/CLASS_DIAGRAM.md   -> identifikasi method yang perlu dibuat
5. Cek docs/DOCKER.md           -> pastikan hostname Docker benar
6. Tulis kode: Migration -> Model -> Service -> Controller
7. Untuk fitur Kafka: Producer payload WAJIB sertakan isi_satuan
8. Consumer: pastikan konversi qty x isi_satuan sebelum UPDATE stok
```

---

## 5. Struktur Folder Project

```
pos-grosir/
├── CLAUDE.md
├── docker-compose.yml
├── .env / .env.example
├── docs/
│   ├── ERD.md              <- Struktur database + produk_satuan
│   ├── CLASS_DIAGRAM.md    <- Method semua class + ProdukSatuanService
│   ├── ACTIVITY_FLOWS.md   <- Alur per fitur
│   ├── SEQUENCE_FLOWS.md   <- Endpoint + payload Kafka
│   └── DOCKER.md           <- Topologi container
├── laravel-app/
│   ├── CLAUDE.md
│   └── app/Services/
│       ├── Auth/
│       ├── Transaction/
│       ├── Inventory/       <- Tambah: ProdukSatuanService.php
│       ├── Purchasing/
│       ├── Reporting/
│       └── Kafka/
├── kafka-service/
│   ├── CLAUDE.md
│   ├── consumers/           <- Wajib pakai konversi isi_satuan
│   └── ...
└── nginx/
```

---

## 6. Pedoman Teknologi

| Layer | Teknologi | Versi | Catatan |
|---|---|---|---|
| Backend API | Laravel | 11.x | Service Pattern |
| Frontend UI | Laravel Blade + Tailwind CSS | — | Dropdown satuan per produk |
| HTTP Client | Axios | latest | |
| Kafka Client (Node.js) | kafkajs | 2.x | |
| WebSocket | ws (npm) | latest | |
| Database | MySQL | 8.0 | |
| Container | Docker + Docker Compose | latest | |
| Reverse Proxy | Nginx | alpine | |

### Konvensi Bahasa
- UI (Blade): Bahasa Indonesia (`Stok`, `Satuan`, `Transaksi`, `Karton`)
- Variabel kode: Bahasa Inggris (`qty`, `unitSize`, `isi_satuan`)
- Komentar kode: Bahasa Indonesia

---

## 7. Topologi Docker — Hostname Container

| Dari Service | Tujuan | Host yang Benar | Port |
|---|---|---|---|
| laravel | MySQL | `mysql` | 3306 |
| laravel | Node.js Producer | `kafka-service` | 3000 |
| kafka-service | Kafka Broker | `kafka` | 9092 |
| kafka-service | MySQL | `mysql` | 3306 |
| nginx | Laravel (PHP-FPM) | `laravel` | 9000 |
| nginx | WebSocket | `kafka-service` | 3001 |
| Browser | Semua via nginx | `localhost` | 80 |

### Environment Variables Kritis:
```bash
# Laravel (.env)
DB_HOST=mysql
KAFKA_PRODUCER_URL=http://kafka-service:3000

# Node.js (docker-compose)
KAFKA_BROKERS=kafka:9092
DB_HOST=mysql
```

### Perintah Docker Harian:
```bash
docker compose up -d
docker compose --profile dev up -d        # + Kafka UI :8080
docker exec pos_laravel php artisan migrate
docker exec pos_laravel php artisan db:seed
docker compose logs -f kafka-service
docker compose up -d --build kafka-service
docker compose up -d --build laravel
```

---

## 8. Referensi Cepat

| Butuh Info | Baca File |
|---|---|
| Struktur tabel + produk_satuan | `docs/ERD.md` |
| Method semua class | `docs/CLASS_DIAGRAM.md` |
| Alur per fitur | `docs/ACTIVITY_FLOWS.md` |
| Endpoint + payload Kafka | `docs/SEQUENCE_FLOWS.md` |
| Hostname Docker | `docs/DOCKER.md` |
| Aturan Controller & Service | `laravel-app/CLAUDE.md` |
| Aturan Consumer & konversi satuan | `kafka-service/CLAUDE.md` |

---

## 9. Jika Ada Konflik Instruksi

Ingatkan saya jika instruksi saya:
- Menulis `harga_jual` langsung di tabel `produk` (harus di `produk_satuan`)
- Update stok dengan `qty` tanpa dikalikan `isi_satuan` di Consumer
- Membuat kolom/tabel yang tidak ada di ERD
- Menggunakan `localhost` untuk koneksi antar container
- Membuat `ENUM satuan` di tabel produk (sudah diganti VARCHAR + tabel produk_satuan)

**Tunjukkan bagian yang konflik SEBELUM menulis kode.**
