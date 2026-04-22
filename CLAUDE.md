# CLAUDE.md — Sistem POS Toko Grosir (Event-Driven)

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
    │                    │              │
    │                    │         pos_mysql (MySQL 8)
    │                    │
    └── WebSocket ──► pos_kafka_service:3001 (WebSocket Push)
```

### Mana yang lewat Kafka, mana yang tidak:

| Operasi | Via Kafka? | Alasan |
|---|---|---|
| Input transaksi penjualan | ✅ YA | Berdampak stok real-time |
| Pembelian / restock | ✅ YA | Berdampak stok real-time |
| Retur penjualan | ✅ YA | Berdampak stok real-time |
| Retur pembelian | ✅ YA | Berdampak stok real-time |
| Login / Logout | ❌ TIDAK | Operasi sesi |
| CRUD produk / customer / supplier | ❌ TIDAK | Data referensi |
| Lihat laporan | ❌ TIDAK | Read-only |
| Cetak struk | ❌ TIDAK | Read-only |

---

## 2. Aturan Mutlak (Jangan Dilanggar)

### 2.1 Zero Hallucination Database
- **DILARANG** membuat kolom atau tabel yang tidak ada di `docs/ERD.md`
- Tipe data harus sesuai ERD: harga = `DECIMAL(10,2)`, bukan `FLOAT`
- Nama tabel: `snake_case` plural (transaksi, detail_transaksi, retur_penjualan)
- Nama kolom: `snake_case` (no_transaksi, harga_satuan, qty_stok)

### 2.2 Async First untuk Operasi Stok
- **DILARANG** menulis `Transaksi::create()` langsung di Controller untuk transaksi penjualan
- Alur wajib: `Controller → Service → KafkaProducerService → Kafka Topic`
- Consumer yang menulis ke Database, **bukan** Controller atau Service Laravel

### 2.3 Status Lifecycle (Jangan Tambah Status Baru)
```
Transaksi:       PENDING → COMPLETED | FAILED
Pembelian:       PENDING → COMPLETED | CANCELLED
Retur Penjualan: PENDING → COMPLETED | REJECTED
Retur Pembelian: PENDING → COMPLETED | REJECTED
```

### 2.4 Konsistensi Naming
- Endpoint: sesuai `docs/SEQUENCE_FLOWS.md`
  - ✅ `POST /transaksi`, `GET /customer/{id}/riwayat`
  - ❌ `POST /transactions`, `GET /customers/{id}/history`
- Method PHP: sesuai `docs/CLASS_DIAGRAM.md`
  - ✅ `calculateTotal()`, `generateStruk()`, `isStokCukup()`
- Event Kafka: sesuai docs
  - ✅ `transaction.created`, `purchase.created`, `sale.return.requested`

### 2.5 Soft Delete Pattern
- **DILARANG** hard delete jika entitas pernah dipakai di transaksi
- Cek dulu `COUNT(*)` di tabel relasi sebelum hapus
- Gunakan kolom `status ENUM('aktif','nonaktif')`, **bukan** Laravel `SoftDeletes` trait

### 2.6 Password Security
- **SELALU** hash dengan `bcrypt` sebelum simpan ke database
- **JANGAN** kembalikan `password_hash` dalam response API apapun

### 2.7 Docker — Hostname Antar Container
- **DILARANG** menulis `localhost` atau `127.0.0.1` untuk koneksi antar container
- Selalu gunakan nama service Docker (lihat tabel di seksi 7)

---

## 3. Peran Claude Code dalam Proyek Ini
Anda adalah **Senior Full-Stack Developer & Software Architect** yang membantu membangun sistem ini sesuai desain UML yang sudah ada. Kode harus:
- **Profesional dan modular** — gunakan Service Pattern dan Repository Pattern
- **Terdokumentasi dengan komentar teknis** — jelaskan *mengapa* sebuah logika dipilih, bukan *apa* yang dilakukan (berguna untuk bahan sidang)
- **Konsisten** — naming, struktur folder, dan pola kode harus seragam di seluruh project
- **Tidak berasumsi** — jika ada yang ambigu, tanya sebelum menulis kode

---

## 4. Alur Kerja Implementasi Fitur Baru

Setiap kali mengerjakan fitur baru, ikuti urutan ini:

```
1. Baca docs/ACTIVITY_FLOWS.md  → pahami alur aktivitas fitur
2. Baca docs/SEQUENCE_FLOWS.md  → pahami endpoint dan urutan pesan
3. Baca docs/ERD.md             → identifikasi tabel yang terlibat
4. Baca docs/CLASS_DIAGRAM.md   → identifikasi method yang perlu dibuat
5. Cek docs/DOCKER.md           → pastikan koneksi service benar
6. Tulis kode: Migration → Model → Service → Controller → View
7. Untuk fitur Kafka: tambah Producer → Consumer → WebSocket push
```

---

## 5. Struktur Folder Project

```
pos-grosir/
├── CLAUDE.md                    ← File ini
├── docker-compose.yml
├── .env / .env.example
├── docs/
│   ├── ERD.md                   ← Struktur lengkap database
│   ├── CLASS_DIAGRAM.md         ← Method setiap class
│   ├── ACTIVITY_FLOWS.md        ← Alur per fitur (swimlane)
│   ├── SEQUENCE_FLOWS.md        ← Endpoint & urutan pesan API
│   └── DOCKER.md                ← Topologi container
├── laravel-app/                 ← Laravel 11 (Backend API + Blade UI)
│   ├── CLAUDE.md
│   └── ...
├── kafka-service/               ← Node.js Producer + Consumer + WebSocket
│   ├── CLAUDE.md
│   └── ...
└── nginx/                       ← Reverse Proxy
```

---

## 6. Pedoman Teknologi

| Layer | Teknologi | Versi | Catatan |
|---|---|---|---|
| Backend API | Laravel | 11.x | Service Pattern, FormRequest validation |
| Frontend UI | Laravel Blade + Tailwind CSS | — | Responsif untuk layar kasir |
| HTTP Client | Axios | latest | Untuk request async dari Blade |
| Kafka Client (Node.js) | kafkajs | 2.x | Producer & Consumer |
| Kafka Client (Laravel) | Via HTTP ke Node.js | — | Laravel tidak langsung ke Kafka |
| WebSocket | ws (npm) | latest | Di Node.js service |
| Database | MySQL | 8.0 | |
| Container | Docker + Docker Compose | latest | |
| Reverse Proxy | Nginx | alpine | |

### Konvensi Bahasa
- **UI (Blade):** Bahasa Indonesia (`Stok`, `Transaksi`, `Pelanggan`)
- **Variabel & fungsi kode:** Bahasa Inggris (`stock`, `transaction_id`, `calculateTotal`)
- **Komentar kode:** Bahasa Indonesia (untuk kemudahan pembimbing membaca)

---

## 7. Topologi Docker — Hostname Antar Container

Lihat `docs/DOCKER.md` untuk peta lengkap. Ringkasan hostname:

| Dari Service | Tujuan | Host yang Dipakai | Port |
|---|---|---|---|
| laravel | MySQL | `mysql` | 3306 |
| laravel | Node.js Producer | `kafka-service` | 3000 |
| kafka-service | Kafka Broker | `kafka` | 9092 |
| kafka-service | MySQL | `mysql` | 3306 |
| nginx | Laravel (PHP-FPM) | `laravel` | 9000 |
| nginx | WebSocket | `kafka-service` | 3001 |
| Browser | Semua (via nginx) | `localhost` | 80 |

### Environment Variables Kritis:
```bash
# Laravel (.env)
DB_HOST=mysql                              # BUKAN localhost
KAFKA_PRODUCER_URL=http://kafka-service:3000

# Node.js (docker-compose env)
KAFKA_BROKERS=kafka:9092                   # BUKAN localhost:9092
DB_HOST=mysql                              # BUKAN localhost
```

### Perintah Docker Harian:
```bash
docker compose up -d                       # Start semua service
docker compose --profile dev up -d         # + Kafka UI di port 8080
docker exec pos_laravel php artisan migrate
docker exec pos_laravel php artisan db:seed
docker exec -it pos_mysql mysql -u pos_user -p pos_grosir
docker compose logs -f kafka-service
docker compose restart laravel
docker compose up -d --build laravel       # Rebuild setelah ubah Dockerfile
```

---

## 8. Referensi Cepat

| Butuh Info | Baca File |
|---|---|
| Struktur tabel & kolom | `docs/ERD.md` |
| Method setiap class | `docs/CLASS_DIAGRAM.md` |
| Alur per fitur (swimlane) | `docs/ACTIVITY_FLOWS.md` |
| Endpoint & urutan pesan | `docs/SEQUENCE_FLOWS.md` |
| Hostname Docker & topologi | `docs/DOCKER.md` |
| Aturan Controller & Service | `laravel-app/CLAUDE.md` |
| Aturan Kafka & WebSocket | `kafka-service/CLAUDE.md` |

---

## 9. Jika Ada Konflik Instruksi

Ingatkan saya jika instruksi saya:
- Melanggar arsitektur event-driven (misalnya: menulis langsung ke DB dari Controller untuk transaksi)
- Membuat kolom/tabel yang tidak ada di ERD
- Menggunakan `localhost` untuk koneksi antar container
- Menambah status baru pada ENUM tanpa diskusi

**Tunjukkan bagian mana yang konflik SEBELUM menulis kode.**
