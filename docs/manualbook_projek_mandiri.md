# MANUAL BOOK

# IMPLEMENTASI APLIKASI KASIR DENGAN PEMBARUAN STOK SECARA REAL-TIME

# PADA TOKO MELATI MENGGUNAKAN LARAVEL DAN WEBSOCKET

---

## 1. Spesifikasi Komputer yang Memungkinkan

Sebelum melakukan instalasi, pastikan perangkat yang digunakan memenuhi spesifikasi
minimum berikut agar seluruh layanan (Laravel, Kafka, MySQL, Node.js, Nginx) dapat
berjalan secara bersamaan di dalam Docker.

**Tabel MB.1. Spesifikasi Perangkat Keras Minimum**

| Komponen       | Spesifikasi Minimum                                   | Keterangan                                         |
| -------------- | ----------------------------------------------------- | -------------------------------------------------- |
| Prosesor       | Intel Core i5 Gen 8 / AMD Ryzen 5 (64-bit)            | Arsitektur 64-bit wajib untuk Docker               |
| RAM            | 8 GB                                                  | Kafka membutuhkan minimal 2 GB secara mandiri      |
| Penyimpanan    | 20 GB tersedia                                        | Untuk image Docker, volume MySQL, dan kode sumber  |
| Sistem Operasi | Windows 10/11 (64-bit), macOS 12+, atau Ubuntu 20.04+ | —                                                  |
| Jaringan       | LAN / Wi-Fi (lokal)                                   | Diperlukan antar-container dan akses browser kasir |

**Tabel MB.2. Perangkat Lunak yang Wajib Terinstal**

| No  | Perangkat Lunak         | Versi       | Unduhan                                        |
| --- | ----------------------- | ----------- | ---------------------------------------------- |
| 1   | Docker Desktop          | 4.x ke atas | https://www.docker.com/products/docker-desktop |
| 2   | Git                     | 2.x ke atas | https://git-scm.com/downloads                  |
| 3   | Google Chrome / Firefox | Terbaru     | Untuk mengakses antarmuka kasir                |

> **Catatan:** Seluruh dependensi lain (PHP, Laravel, Node.js, Kafka, MySQL, Nginx) sudah
> terbundel di dalam Docker container — tidak perlu diinstal secara manual di komputer.

---

## 2. Tata Cara Penginstalan Program

### Langkah 1 — Instal Docker Desktop

1. Unduh Docker Desktop dari alamat resminya (lihat Tabel MB.2).
2. Jalankan installer dan ikuti panduan instalasi sesuai sistem operasi Anda.
3. Setelah instalasi selesai, buka Docker Desktop dan pastikan statusnya menunjukkan
   **"Docker Desktop is running"** (ikon Docker di _system tray_ berwarna hijau/aktif).

_[Gambar MB.1. Tampilan Docker Desktop saat berjalan — sisipkan screenshot di sini]_

### Langkah 2 — Instal Git

1. Unduh Git dari alamat resminya (lihat Tabel MB.2).
2. Jalankan installer dengan pengaturan default.
3. Verifikasi instalasi dengan membuka **Command Prompt** atau **Terminal** dan
   mengetikkan perintah berikut:

```
git --version
```

Jika instalasi berhasil, akan muncul output seperti:

```
git version 2.45.0.windows.1
```

### Langkah 3 — Clone Repositori dari GitHub

Buka **Command Prompt** (Windows) atau **Terminal** (macOS/Linux), kemudian arahkan
ke folder tempat Anda ingin menyimpan proyek, lalu jalankan perintah berikut:

```
git clone https://github.com/[username]/[nama-repositori].git
```

> **Catatan:** Ganti `[username]` dan `[nama-repositori]` dengan URL repositori GitHub
> yang sebenarnya. Jika repositori bersifat privat, Anda akan diminta memasukkan
> kredensial GitHub.

Setelah perintah selesai, masuk ke folder proyek:

```
cd [nama-repositori]
```

Struktur folder proyek yang berhasil di-clone akan terlihat seperti berikut:

```
[nama-repositori]/
├── docker-compose.yml
├── .env.example
├── laravel-app/
├── kafka-service/
├── nginx/
└── docs/
```

### Langkah 4 — Konfigurasi File Environment (`.env`)

File `.env` berisi konfigurasi sensitif seperti kata sandi database. File ini **tidak ikut
ter-clone** dari GitHub karena sengaja diabaikan (`.gitignore`) demi keamanan.
Anda perlu membuatnya secara manual dari template yang tersedia.

**a. Salin file template:**

```
copy .env.example .env
```

_(Pada macOS/Linux gunakan: `cp .env.example .env`)_

**b. Buka file `.env`** menggunakan teks editor (Notepad, VS Code, atau teks editor lainnya),
kemudian isi nilai yang diperlukan:

```
# ── Application ─────────────────────────────────────────────
APP_KEY=                          ← akan diisi otomatis pada Langkah 6

# ── Database ─────────────────────────────────────────────────
DB_ROOT_PASSWORD=isi_password_root_anda
DB_USERNAME=pos_user
DB_PASSWORD=isi_password_anda
```

> **Penting:** Gunakan kata sandi yang kuat dan mudah Anda ingat. Hindari menggunakan
> spasi pada nilai kata sandi.

**c. Simpan file `.env`** setelah selesai diisi.

### Langkah 5 — Bangun dan Jalankan Seluruh Container Docker

Pastikan Docker Desktop sedang berjalan, kemudian jalankan perintah berikut di terminal
(dari dalam folder proyek):

```
docker compose up -d --build
```

Penjelasan parameter:

- `up` — membuat dan menjalankan container
- `-d` — mode _detach_ (berjalan di latar belakang)
- `--build` — membangun ulang _image_ Docker dari Dockerfile

Proses ini memerlukan waktu beberapa menit pada pertama kali dijalankan karena Docker
perlu mengunduh _image_ dasar (MySQL, Kafka, PHP, dll). Tunggu hingga seluruh proses
selesai.

_[Gambar MB.2. Proses build Docker di terminal — sisipkan screenshot di sini]_

Periksa status container dengan perintah:

```
docker compose ps
```

Pastikan semua container menunjukkan status **Up** atau **running**, seperti berikut:

```
NAME                  STATUS          PORTS
pos_nginx             Up              0.0.0.0:80->80/tcp
pos_laravel           Up              9000/tcp
pos_kafka_service     Up              0.0.0.0:3001->3001/tcp
pos_kafka             Up              9092/tcp
pos_mysql             Up (healthy)    0.0.0.0:3306->3306/tcp
```

> **Jika ada container yang statusnya `Exit` atau `Restarting`**, tunggu beberapa saat
> karena Kafka dan MySQL membutuhkan waktu inisialisasi. Jalankan `docker compose ps`
> kembali setelah 30 detik.

### Langkah 6 — Generate Application Key Laravel

Jalankan perintah berikut untuk menghasilkan kunci enkripsi aplikasi secara otomatis:

```
docker exec pos_laravel php artisan key:generate
```

Perintah ini akan mengisi nilai `APP_KEY` di file `.env` secara otomatis. Anda akan
melihat output:

```
INFO  Application key set successfully.
```

### Langkah 7 — Jalankan Migrasi Basis Data

Buat seluruh tabel basis data dengan menjalankan perintah migrasi berikut:

```
docker exec pos_laravel php artisan migrate
```

Perintah ini akan membaca file-file migrasi Laravel dan membuat tabel-tabel yang
diperlukan (produk, transaksi, detail_transaksi, users, dan lain-lain) di dalam MySQL.

Output yang diharapkan:

```
INFO  Running migrations.
  ... create_users_table ................. 15ms DONE
  ... create_produk_table ................ 12ms DONE
  ... create_transaksi_table ............. 18ms DONE
  [dan seterusnya]
```

### Langkah 8 — Isi Data Awal (_Seeder_)

Jalankan perintah berikut untuk mengisi data awal ke dalam basis data, termasuk akun
kasir default, kategori produk, dan contoh data produk:

```
docker exec pos_laravel php artisan db:seed
```

Output yang diharapkan:

```
INFO  Seeding database.
  UserSeeder ............................. DONE
  KategoriSeeder ......................... DONE
  ProdukSeeder ........................... DONE
```

### Langkah 9 — Akses Aplikasi

Buka peramban (Google Chrome atau Firefox) dan ketikkan alamat berikut:

```
http://localhost
```

Jika instalasi berhasil, halaman login aplikasi kasir akan muncul di layar.

_[Gambar MB.3. Tampilan halaman login aplikasi kasir — sisipkan screenshot di sini]_

**Tabel MB.3. Akun Default Setelah Seeder**

| Role         | Email                 | Password  |
| ------------ | --------------------- | --------- |
| Kasir        | kasir@tokomelati.com  | kasir123  |
| Admin Gudang | gudang@tokomelati.com | gudang123 |
| Owner        | owner@tokomelati.com  | owner123  |

> **Keamanan:** Segera ubah kata sandi default setelah pertama kali login pada lingkungan
> produksi atau demonstrasi.

---

### Ringkasan Perintah Instalasi

Untuk kemudahan referensi, seluruh perintah instalasi dirangkum pada Tabel MB.4.

**Tabel MB.4. Ringkasan Perintah Instalasi**

| No  | Perintah                                           | Keterangan                                     |
| --- | -------------------------------------------------- | ---------------------------------------------- |
| 1   | `git clone <url>`                                  | Clone repositori dari GitHub                   |
| 2   | `cd <nama-folder>`                                 | Masuk ke folder proyek                         |
| 3   | `copy .env.example .env`                           | Buat file konfigurasi                          |
| 4   | _(edit .env)_                                      | Isi DB_ROOT_PASSWORD, DB_USERNAME, DB_PASSWORD |
| 5   | `docker compose up -d --build`                     | Bangun dan jalankan semua container            |
| 6   | `docker exec pos_laravel php artisan key:generate` | Generate kunci aplikasi Laravel                |
| 7   | `docker exec pos_laravel php artisan migrate`      | Buat tabel basis data                          |
| 8   | `docker exec pos_laravel php artisan db:seed`      | Isi data awal                                  |
| 9   | Buka `http://localhost`                            | Akses aplikasi di peramban                     |

---

## 3. Penjelasan Penggunaan Program

### 3.1. Login

1. Buka peramban dan akses `http://localhost`.
2. Pada halaman login, masukkan **Email** dan **Kata Sandi** akun kasir.
3. Klik tombol **"Masuk"**.
4. Jika kredensial benar, Anda akan diarahkan ke halaman utama kasir.
5. Jika kredensial salah, sistem menampilkan pesan kesalahan dan Anda dapat
   mencoba kembali.

_[Gambar MB.4. Halaman login — sisipkan screenshot di sini]_

---

### 3.2. Halaman Utama Kasir

Setelah berhasil login, halaman utama kasir akan tampil. Halaman ini terdiri atas dua
panel utama:

- **Panel Kiri — Daftar Produk:** Menampilkan seluruh produk yang tersedia beserta
  informasi stok terkini. Stok diperbarui secara otomatis (_real-time_) setiap kali ada
  transaksi yang berhasil diproses — tanpa perlu memuat ulang halaman. Produk dengan
  stok nol akan ditandai dengan indikator merah.

- **Panel Kanan — Keranjang Belanja:** Menampilkan daftar item yang telah dipilih
  beserta subtotal per item, total keseluruhan, kolom nominal pembayaran, dan informasi
  kembalian.

_[Gambar MB.5. Halaman utama kasir — sisipkan screenshot di sini]_

---

### 3.3. Melakukan Transaksi Penjualan

Berikut adalah langkah-langkah memproses satu transaksi penjualan:

**Langkah 1 — Cari dan Pilih Produk**

Gunakan kolom pencarian di panel kiri untuk mencari produk berdasarkan nama atau
kode barang. Klik pada nama produk yang ingin ditambahkan ke keranjang.

_[Gambar MB.6. Pencarian dan pemilihan produk — sisipkan screenshot di sini]_

**Langkah 2 — Pilih Satuan dan Masukkan Jumlah**

Setelah produk dipilih, sistem menampilkan jendela konfirmasi item yang berisi:

- Nama produk
- Dropdown pemilihan **satuan** (contoh: pcs, renteng, karton)
- Kolom **jumlah** yang ingin dibeli
- Harga satuan yang menyesuaikan otomatis sesuai satuan yang dipilih

Isi jumlah yang diinginkan, pilih satuan yang sesuai, kemudian klik **"Tambah ke Keranjang"**.

_[Gambar MB.7. Dialog pemilihan satuan dan jumlah — sisipkan screenshot di sini]_

> **Catatan:** Jika stok yang tersedia tidak mencukupi jumlah yang dimasukkan, sistem
> akan menampilkan peringatan dan item tidak akan ditambahkan ke keranjang.

**Langkah 3 — Periksa Keranjang**

Item yang berhasil ditambahkan akan muncul di panel kanan (keranjang belanja) beserta:

- Nama produk dan satuan
- Jumlah item
- Subtotal (jumlah × harga satuan)
- Total keseluruhan seluruh item di keranjang

Untuk menghapus item dari keranjang, klik ikon hapus (✕) di samping item yang bersangkutan.

_[Gambar MB.8. Tampilan keranjang belanja — sisipkan screenshot di sini]_

**Langkah 4 — Proses Pembayaran**

Setelah seluruh item selesai dimasukkan ke keranjang:

1. Masukkan **nominal uang yang diberikan pelanggan** pada kolom "Uang Bayar".
2. Sistem akan otomatis menghitung dan menampilkan **kembalian** yang harus
   diberikan kepada pelanggan.
3. Pastikan nominal uang bayar tidak kurang dari total belanja.
4. Klik tombol **"Bayar & Konfirmasi"** untuk memproses transaksi.

_[Gambar MB.9. Proses input pembayaran — sisipkan screenshot di sini]_

**Langkah 5 — Konfirmasi dan Pembaruan Stok Real-Time**

Setelah tombol "Bayar & Konfirmasi" diklik:

1. Sistem mengirimkan data transaksi ke server untuk diproses.
2. Apache Kafka memproses event transaksi secara asinkron di latar belakang.
3. Stok seluruh produk yang terjual diperbarui secara otomatis di basis data.
4. **Tanpa perlu memuat ulang halaman**, stok yang tampil di panel kiri langsung
   berubah mencerminkan kondisi terkini — ini adalah fitur pembaruan real-time via
   WebSocket.
5. Sistem menampilkan halaman struk transaksi.

_[Gambar MB.10. Stok berubah secara real-time di panel kiri — sisipkan screenshot di sini]_

---

### 3.4. Melihat dan Mencetak Struk

Setelah transaksi berhasil diproses, sistem secara otomatis menampilkan halaman struk
yang memuat informasi berikut:

- Nama toko (POS Toko Melati)
- Nomor transaksi (contoh: `TRX-20260531-001`)
- Tanggal dan waktu transaksi
- Nama kasir
- Daftar item: nama produk, satuan, jumlah, harga satuan, dan subtotal
- Total belanja
- Uang bayar
- Kembalian

**Untuk mencetak struk:**
Klik tombol **"Cetak Struk"**. Sistem akan membuka dialog cetak bawaan peramban.
Pilih printer yang tersedia atau simpan sebagai PDF, kemudian klik "Cetak".

**Untuk kembali ke halaman kasir tanpa mencetak:**
Klik tombol **"Transaksi Baru"** untuk mengosongkan keranjang dan memulai
transaksi berikutnya.

_[Gambar MB.11. Halaman struk transaksi — sisipkan screenshot di sini]_

---

### 3.5. Logout

Untuk keluar dari sistem:

1. Klik nama pengguna atau ikon profil di pojok kanan atas halaman.
2. Pilih menu **"Logout"**.
3. Sistem mengakhiri sesi dan mengarahkan kembali ke halaman login.

> **Penting:** Selalu lakukan logout setelah selesai menggunakan aplikasi, terutama jika
> perangkat kasir digunakan bersama atau berada di area yang dapat diakses umum.

_[Gambar MB.12. Tombol logout — sisipkan screenshot di sini]_

---

## 4. Troubleshooting (Penanganan Masalah Umum)

**Tabel MB.5. Daftar Masalah dan Solusi**

| No  | Gejala                                           | Kemungkinan Penyebab           | Solusi                                                                            |
| --- | ------------------------------------------------ | ------------------------------ | --------------------------------------------------------------------------------- |
| 1   | Halaman `http://localhost` tidak dapat dibuka    | Container Nginx belum berjalan | Jalankan `docker compose ps` dan pastikan `pos_nginx` statusnya _Up_              |
| 2   | Halaman tampil tapi stok tidak berubah real-time | Koneksi WebSocket terputus     | Muat ulang halaman kasir (F5); periksa log `docker compose logs -f kafka-service` |
| 3   | Login gagal meskipun password benar              | Seeder belum dijalankan        | Jalankan `docker exec pos_laravel php artisan db:seed`                            |
| 4   | Error "Database connection refused"              | Container MySQL belum siap     | Tunggu ±30 detik lalu coba lagi; cek `docker compose ps`                          |
| 5   | Container `pos_kafka` terus restart              | Memori tidak mencukupi         | Pastikan RAM tersedia minimal 4 GB; tutup aplikasi lain yang berat                |
| 6   | Perubahan kode tidak terbaca                     | Cache Laravel                  | Jalankan `docker exec pos_laravel php artisan optimize:clear`                     |

---

## 5. Menghentikan dan Menjalankan Ulang Aplikasi

**Menghentikan semua container** (data tersimpan):

```
docker compose down
```

**Menjalankan ulang** (setelah dihentikan):

```
docker compose up -d
```

> Data MySQL dan Kafka tersimpan di Docker Volume (`mysql_data`, `kafka_data`) sehingga
> tidak hilang meskipun container dihentikan.

**Menghapus seluruh data dan memulai dari awal** _(hati-hati, tidak dapat dibatalkan)_:

```
docker compose down -v
```
