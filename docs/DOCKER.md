# DOCKER.md — Topologi Container Sistem POS

## Penting untuk Claude Code
Dokumen ini adalah **referensi wajib** saat menulis kode yang melibatkan
koneksi antar service. Selalu gunakan nama service Docker, bukan `localhost`.

---

## Peta Container

```
Browser (port 80)
        │
        ▼
┌───────────────────────┐
│   pos_nginx           │  nginx:1.25-alpine
│   Reverse Proxy       │  Port 80 (exposed ke host)
└──────┬────────────────┘
       │                          ┌─────────────────────────────┐
       ├─ fastcgi:9000 ─────────► │  pos_laravel                │
       │  (PHP-FPM)               │  php:8.3-fpm-alpine         │
       │                          │  Port 9000 (internal)        │
       │                          └──────────┬──────────────────┘
       │                                     │ HTTP POST
       │                                     │ http://kafka-service:3000
       │                          ┌──────────▼──────────────────┐
       │                          │  pos_kafka_service          │
       │                          │  node:20-alpine              │
       ├─ WebSocket ─────────────►│  :3000 HTTP Producer         │
       │  ws://localhost/ws        │  :3001 WebSocket Server     │
       │  (proxy via /ws)         └──────────┬──────────────────┘
       │                                     │ :9092
       │                          ┌──────────▼──────────────────┐
       │                          │  pos_kafka                  │
       │                          │  bitnami/kafka:3.7           │
       │                          │  KRaft (tanpa Zookeeper)    │
       │                          └──────────┬──────────────────┘
       │                                     │ :3306
       │                          ┌──────────▼──────────────────┐
       │                          │  pos_mysql                  │
       │                          │  mysql:8.0                   │
       │                          │  Database: pos_grosir        │
       │                          └─────────────────────────────┘
       │
       └─ :8080 (dev profile) ──► pos_kafka_ui (Kafka Dashboard)
```

---

## Tabel Hostname Antar Container

| Dari Container | Tujuan | Host yang BENAR | Port | DILARANG |
|---|---|---|---|---|
| pos_laravel | MySQL | `mysql` | 3306 | `localhost` |
| pos_laravel | Node.js Producer | `kafka-service` | 3000 | `localhost` |
| pos_kafka_service | Kafka Broker | `kafka` | 9092 | `localhost` |
| pos_kafka_service | MySQL | `mysql` | 3306 | `localhost` |
| pos_nginx | Laravel PHP-FPM | `laravel` | 9000 | `localhost` |
| pos_nginx | WebSocket | `kafka-service` | 3001 | `localhost` |
| Browser | Semua (via nginx) | `localhost` | 80 | — |
| Browser | WebSocket path | `localhost/ws` | 80 | `kafka-service:3001` langsung |

---

## Port yang Diekspos ke Host (untuk Development)

| Port Host | Container | Kegunaan |
|---|---|---|
| **80** | pos_nginx | Akses utama aplikasi (web + API) |
| **3306** | pos_mysql | Akses langsung DB (DBeaver, TablePlus, MySQL Workbench) |
| **3001** | pos_kafka_service | WebSocket debug langsung (opsional) |
| **8080** | pos_kafka_ui | Kafka monitoring dashboard (**hanya `--profile dev`**) |

Port 9000 (PHP-FPM), 9092 (Kafka), 3000 (Node.js HTTP) **tidak diekspos** ke host —
hanya bisa diakses dalam Docker network `pos_network`.

---

## Kafka Topics

Topics dibuat otomatis karena `KAFKA_CFG_AUTO_CREATE_TOPICS_ENABLE=true`.
Untuk membuat manual atau memeriksa:

```bash
# List semua topic
docker exec pos_kafka kafka-topics.sh \
  --bootstrap-server localhost:9092 --list

# Buat topic manual (jika auto-create dimatikan)
docker exec pos_kafka kafka-topics.sh \
  --bootstrap-server localhost:9092 \
  --create --topic transaction-events \
  --partitions 3 --replication-factor 1

docker exec pos_kafka kafka-topics.sh \
  --bootstrap-server localhost:9092 \
  --create --topic inventory-updates \
  --partitions 3 --replication-factor 1

# Monitor pesan masuk ke topic (real-time)
docker exec pos_kafka kafka-console-consumer.sh \
  --bootstrap-server localhost:9092 \
  --topic transaction-events --from-beginning
```

---

## Consumer Groups

| Consumer Group ID | Topic | File |
|---|---|---|
| `pos-transaction-group` | `transaction-events` | consumers/transactionConsumer.js |
| `pos-inventory-group` | `inventory-updates` | consumers/purchaseConsumer.js |
| `pos-return-group` | `inventory-updates` | consumers/returnConsumer.js |

```bash
# Lihat status consumer groups
docker exec pos_kafka kafka-consumer-groups.sh \
  --bootstrap-server localhost:9092 --list

# Lihat lag (keterlambatan processing) per group
docker exec pos_kafka kafka-consumer-groups.sh \
  --bootstrap-server localhost:9092 \
  --describe --group pos-transaction-group
```

---

## Perintah Harian Development

```bash
# ── Start / Stop ─────────────────────────────────────────────

# Start semua service
docker compose up -d

# Start dengan Kafka UI (http://localhost:8080)
docker compose --profile dev up -d

# Stop semua (data volume tetap ada)
docker compose down

# Stop dan hapus SEMUA data (reset total)
docker compose down -v

# ── Monitoring ───────────────────────────────────────────────

# Lihat status semua container
docker compose ps

# Lihat log semua service (real-time)
docker compose logs -f

# Lihat log service tertentu
docker compose logs -f laravel
docker compose logs -f kafka-service
docker compose logs -f kafka

# ── Laravel ──────────────────────────────────────────────────

# Jalankan migration
docker exec pos_laravel php artisan migrate

# Jalankan migration + seed (reset database)
docker exec pos_laravel php artisan migrate:fresh --seed

# Buat file baru
docker exec pos_laravel php artisan make:controller NamaController
docker exec pos_laravel php artisan make:model NamaModel -m
docker exec pos_laravel php artisan make:request NamaRequest

# Masuk ke shell container Laravel
docker exec -it pos_laravel sh

# ── MySQL ─────────────────────────────────────────────────────

# Masuk ke MySQL CLI
docker exec -it pos_mysql mysql -u pos_user -p pos_grosir

# Backup database
docker exec pos_mysql mysqldump -u pos_user -p pos_grosir > backup.sql

# Restore database
docker exec -i pos_mysql mysql -u pos_user -p pos_grosir < backup.sql

# ── Node.js Kafka Service ─────────────────────────────────────

# Lihat log consumer secara real-time
docker compose logs -f kafka-service

# Masuk ke shell container
docker exec -it pos_kafka_service sh

# ── Rebuild ───────────────────────────────────────────────────

# Rebuild satu service (setelah ubah Dockerfile atau code)
docker compose up -d --build laravel
docker compose up -d --build kafka-service

# Rebuild semua
docker compose up -d --build
```

---

## Urutan Startup yang Benar

Docker Compose `depends_on` + `healthcheck` memastikan urutan:

```
1. pos_mysql     → tunggu hingga healthcheck PASSED (mysqladmin ping)
2. pos_kafka     → tunggu hingga healthcheck PASSED (kafka-topics list)
3. pos_laravel   → start setelah mysql & kafka healthy
   pos_kafka_service → start setelah mysql & kafka healthy
4. pos_nginx     → start terakhir, setelah laravel & kafka-service up
```

Jika ada container yang crash loop, periksa dengan:
```bash
docker compose logs [nama-service]
docker inspect pos_[nama-service] | grep -A 10 '"Health"'
```

---

## Troubleshooting Umum

| Masalah | Kemungkinan Penyebab | Solusi |
|---|---|---|
| Laravel tidak bisa connect ke DB | `DB_HOST` masih `localhost` | Ubah ke `mysql` |
| Kafka Consumer tidak menerima pesan | Kafka belum sepenuhnya ready | Tunggu 30-60 detik setelah start |
| WebSocket tidak terkoneksi | Path `/ws` di nginx salah | Periksa `nginx.conf` bagian `/ws` |
| Container restart terus | Dependencies belum healthy | Periksa `docker compose logs` |
| Port 80 sudah dipakai | Ada web server lain di host | Ubah port di `docker-compose.yml` ke misal `8000:80` |
