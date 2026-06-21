# BAB II
# TINJAUAN PUSTAKA

## A. Landasan Teori

### 1. Sistem Point of Sales (POS)

Sistem Point of Sales (POS) merupakan sistem informasi yang dirancang untuk memfasilitasi
proses transaksi penjualan di titik interaksi langsung antara penjual dan pembeli. Secara
fungsional, sistem POS mencakup pencatatan transaksi penjualan, pengelolaan data produk dan
harga, perhitungan total pembayaran secara otomatis, pencetakan struk, serta pembaruan data
stok setiap kali transaksi terjadi (Maridaningsih dkk., 2025). Penerapan sistem POS pada usaha
ritel terbukti dapat meningkatkan kecepatan pelayanan, akurasi perhitungan, dan efisiensi
pencatatan secara signifikan dibandingkan dengan metode manual.

Perkembangan sistem POS telah bergeser dari perangkat keras khusus (*dedicated terminal*)
menuju aplikasi berbasis web yang dapat diakses melalui peramban (*browser*). Sistem POS
berbasis web menawarkan keunggulan dalam hal kemudahan pemeliharaan, aksesibilitas
multi-perangkat, dan kemampuan integrasi dengan sistem lain seperti manajemen inventori dan
pelaporan keuangan (Eka Yulianti dkk., 2025). Dalam konteks toko grosir, sistem POS juga
harus mampu menangani berbagai satuan barang (eceran, renteng, karton) serta menyediakan
informasi stok yang akurat dan terkini bagi staf kasir.

### 2. Aplikasi Kasir

Aplikasi kasir merupakan komponen antarmuka (*front-end*) dari sistem POS yang secara
langsung digunakan oleh staf kasir dalam proses pelayanan transaksi. Berbeda dengan modul
manajemen yang digunakan oleh manajer atau pemilik toko, aplikasi kasir dirancang dengan
antarmuka yang sederhana, cepat, dan responsif agar staf kasir dapat memproses transaksi
dengan efisien tanpa memerlukan keahlian teknis khusus (Salwa, 2023).

Fitur utama yang harus dimiliki oleh sebuah aplikasi kasir meliputi: (1) pencarian dan
pemilihan produk berdasarkan nama atau kode barang; (2) input jumlah barang dan pemilihan
satuan; (3) kalkulasi otomatis subtotal dan total pembayaran; (4) pemrosesan berbagai metode
pembayaran; (5) pencetakan atau tampilan struk transaksi; serta (6) tampilan ketersediaan
stok yang selalu diperbarui. Ketersediaan informasi stok secara real-time pada aplikasi kasir
menjadi hal yang kritis, terutama pada toko dengan volume transaksi tinggi atau dengan
beberapa kasir yang beroperasi secara bersamaan (Marpaung dkk., 2025).

### 3. Sistem Real-Time pada Aplikasi Web

Sistem real-time dalam konteks aplikasi web merujuk pada kemampuan sistem untuk
memproses dan menampilkan data secara langsung (*immediately*) segera setelah perubahan
terjadi, tanpa jeda yang signifikan antara peristiwa (*event*) dan tampilan hasilnya di antarmuka
pengguna. Berbeda dengan sistem berbasis *polling* yang meminta data baru secara periodik,
sistem real-time menggunakan mekanisme *push* di mana server secara aktif mengirimkan
pembaruan ke klien begitu data berubah (Lu dkk., 2025).

Dalam konteks kasir toko grosir, penerapan sistem real-time memberikan manfaat konkret
berupa: (1) stok yang tampil di layar kasir selalu mencerminkan kondisi terkini; (2) transaksi
dari kasir berbeda langsung tereflek di semua layar secara bersamaan; (3) pembaruan stok oleh
bagian gudang langsung terlihat oleh kasir tanpa harus memuat ulang halaman. Hal ini
meminimalkan risiko penjualan barang yang stoknya sudah habis dan meningkatkan kepercayaan
staf terhadap akurasi data yang ditampilkan (Jose, 2025).

### 4. Teknologi WebSocket

WebSocket adalah protokol komunikasi berbasis standar RFC 6455 yang menyediakan saluran
komunikasi dua arah (*full-duplex*) melalui koneksi TCP tunggal yang persisten antara klien
(peramban) dan server. Berbeda dengan protokol HTTP yang bersifat *request-response*
(klien harus meminta terlebih dahulu sebelum menerima data), WebSocket memungkinkan server
untuk mengirimkan data ke klien kapan saja tanpa perlu ada permintaan dari klien terlebih
dahulu (Eka Yulianti dkk., 2025).

Mekanisme kerja WebSocket dimulai dengan proses *handshake* HTTP yang kemudian
ditingkatkan (*upgrade*) menjadi koneksi WebSocket. Setelah koneksi terbentuk, data dapat
mengalir secara bebas di kedua arah dengan *overhead* yang jauh lebih kecil dibandingkan
permintaan HTTP berulang. Karakteristik ini menjadikan WebSocket sebagai pilihan tepat untuk
implementasi fitur pembaruan data real-time pada aplikasi kasir, di mana setiap perubahan stok
akibat transaksi perlu segera ditampilkan kepada semua kasir yang sedang aktif (Lu dkk., 2025).

Dalam arsitektur sistem yang dibangun pada Projek Mandiri ini, WebSocket berperan sebagai
mekanisme *delivery* akhir — yaitu saluran yang menyampaikan notifikasi pembaruan stok dari
server langsung ke antarmuka kasir di peramban pengguna.

### 5. Apache Kafka

Apache Kafka adalah platform *event streaming* terdistribusi yang berfungsi sebagai
*message broker* untuk mengelola aliran data bervolume tinggi secara andal dan efisien.
Kafka dirancang berdasarkan model *publish-subscribe*, di mana komponen *producer*
mempublikasikan pesan ke dalam *topic* tertentu, dan komponen *consumer* membaca serta
memproses pesan tersebut secara asinkron (Alang & Kushwaha, 2025).

Komponen utama dalam arsitektur Kafka terdiri atas: (1) *Producer*, yang bertugas
mempublikasikan event ke Kafka topic; (2) *Broker*, yaitu server Kafka yang menyimpan
dan mendistribusikan pesan; (3) *Topic*, sebagai kategori atau saluran tempat pesan
diorganisasikan; dan (4) *Consumer*, yang membaca dan memproses pesan dari topic
(Gundla, 2024). Kafka memiliki keunggulan berupa *fault tolerance* (data tidak hilang
meskipun terjadi kegagalan), kemampuan *replay* pesan, serta skalabilitas tinggi untuk
menangani lonjakan volume transaksi.

Dalam konteks Projek Mandiri ini, Kafka berperan sebagai lapisan pemrosesan event
antara Laravel dan layanan WebSocket. Setiap transaksi penjualan yang dicatat oleh kasir
dikirimkan sebagai event oleh Laravel ke Kafka topic, kemudian dikonsumsi oleh Node.js
consumer yang memperbarui stok di basis data dan mendorong notifikasi pembaruan stok
ke semua klien kasir yang terhubung melalui WebSocket. Pendekatan ini memastikan
pemrosesan transaksi bersifat asinkron sehingga respons kasir tetap cepat meskipun
operasi basis data belum selesai (Fragkoulis dkk., 2024).

### 6. Framework Laravel 11

Laravel adalah *framework* pengembangan aplikasi web berbasis PHP yang mengikuti
pola arsitektur Model-View-Controller (MVC). Laravel menyediakan berbagai fitur bawaan
yang mempercepat pengembangan aplikasi, antara lain sistem *routing* yang ekspresif,
Eloquent ORM untuk interaksi basis data, sistem *migration* untuk manajemen skema
basis data, serta *Blade template engine* untuk membangun antarmuka pengguna yang
dinamis (Spasova & Raiu, 2022).

Laravel versi 11 yang digunakan dalam Projek Mandiri ini menghadirkan beberapa peningkatan
dibandingkan versi sebelumnya, di antaranya struktur aplikasi yang lebih ramping (*slim
application skeleton*), peningkatan performa, serta dukungan PHP 8.2 ke atas yang
memungkinkan penulisan kode yang lebih modern dan efisien. Dalam sistem yang dibangun,
Laravel bertugas menangani seluruh logika bisnis modul kasir, validasi input, autentikasi
pengguna, serta komunikasi dengan layanan Kafka melalui HTTP untuk mempublikasikan
event transaksi.

### 7. Node.js

Node.js adalah *runtime environment* JavaScript berbasis mesin V8 yang memungkinkan
eksekusi kode JavaScript di sisi server. Keunggulan utama Node.js terletak pada model
pemrosesan non-blocking I/O dan arsitektur berbasis event (*event-driven*), yang
membuatnya sangat efisien dalam menangani banyak koneksi secara bersamaan (Liu & Li, 2024).

Dalam arsitektur sistem Projek Mandiri ini, Node.js berperan sebagai layanan tersendiri yang
menjalankan dua fungsi sekaligus: (1) Kafka *consumer*, yang membaca event transaksi dari
Kafka topic dan melakukan pembaruan stok di basis data MySQL; dan (2) WebSocket *server*,
yang mengelola koneksi persisten dari peramban kasir dan mendorong notifikasi pembaruan
stok secara real-time. Pemisahan layanan ini antara Laravel (API) dan Node.js (event processing
+ WebSocket) memungkinkan masing-masing komponen bekerja secara independen dan skalabel.

### 8. MySQL

MySQL adalah sistem manajemen basis data relasional (*Relational Database Management
System* / RDBMS) yang bersifat *open-source* dan banyak digunakan pada aplikasi web.
MySQL mengorganisasikan data dalam bentuk tabel-tabel yang saling berelasi, dan mendukung
bahasa kueri SQL (*Structured Query Language*) untuk operasi pembacaan dan penulisan data.
MySQL versi 8.0 yang digunakan dalam sistem ini mendukung fitur transaksi ACID, indeks
*full-text*, serta peningkatan performa pada operasi kueri yang kompleks.

Dalam Projek Mandiri ini, MySQL menjadi penyimpanan tunggal bagi seluruh data operasional
aplikasi kasir, meliputi data produk, satuan barang, data kasir, riwayat transaksi, serta stok
barang yang diperbarui secara real-time oleh Kafka consumer setiap kali transaksi baru berhasil
diproses.

### 9. Blackbox Testing

Blackbox testing adalah metode pengujian perangkat lunak yang mengevaluasi fungsionalitas
sistem dari perspektif pengguna akhir, tanpa memperhatikan struktur atau logika internal
(*source code*) program. Pengujian ini berfokus pada kesesuaian antara keluaran (*output*)
yang dihasilkan sistem terhadap masukan (*input*) yang diberikan berdasarkan spesifikasi
kebutuhan fungsional yang telah ditetapkan (Ismail & Efendi, 2020).

Metode ini dipilih karena relevan untuk memverifikasi apakah seluruh fitur aplikasi kasir
— mulai dari proses login, input transaksi, perhitungan total, hingga tampilan pembaruan
stok secara real-time — berjalan sesuai dengan kebutuhan operasional Toko Melati.
Pengujian blackbox testing dilakukan dengan menyusun skenario uji (*test case*) berdasarkan
alur penggunaan normal (*happy path*) maupun kondisi tak terduga (Permatasari dkk., 2023;
Wulandari dkk., 2022). Setiap skenario uji mencatat kondisi awal, langkah pengujian, hasil
yang diharapkan, dan hasil aktual untuk menentukan status lulus atau gagal.

---

## B. Penelitian Terdahulu

Tinjauan terhadap penelitian terdahulu dilakukan untuk memetakan posisi Projek Mandiri ini
di antara penelitian yang telah ada, sekaligus mengidentifikasi celah (*gap*) yang menjadi
dasar pengembangan sistem. Beberapa penelitian relevan yang berkaitan dengan implementasi
sistem kasir berbasis web, teknologi real-time, Apache Kafka, dan WebSocket dirangkum pada
Tabel 2.1 berikut.

**Tabel 2.1. Penelitian Terdahulu**

| No | Peneliti (Tahun) | Topik / Fokus | Teknologi | Hasil | Perbedaan dengan PM Ini |
|----|-----------------|---------------|-----------|-------|------------------------|
| 1  | Maridaningsih dkk. (2025) | Implementasi sistem POS berbasis web untuk usaha ritel | PHP, MySQL | Sistem POS mampu meningkatkan efisiensi pencatatan transaksi dan akurasi data stok dibandingkan metode manual | PM ini menambahkan mekanisme pembaruan stok real-time menggunakan Kafka dan WebSocket, tidak hanya pencatatan transaksi statis |
| 2  | Eka Yulianti dkk. (2025) | Pengembangan sistem kasir berbasis web dengan tampilan data dinamis | Laravel, MySQL | Sistem kasir berbasis web berhasil mempercepat proses transaksi dan menyediakan informasi produk yang terintegrasi | PM ini mengimplementasikan push notification stok via WebSocket sehingga kasir tidak perlu memuat ulang halaman untuk melihat stok terbaru |
| 3  | Daeli dkk. (2025) | Implementasi arsitektur microservices ritel berbasis Kafka | Apache Kafka, Microservices | Kafka mampu mendukung pengelolaan data yang modular dan scalable pada sistem ritel, namun tanpa evaluasi kuantitatif performa | PM ini mengintegrasikan Kafka langsung pada skenario nyata modul kasir toko grosir dengan pengujian fungsional menggunakan blackbox testing |
| 4  | Gundla (2024) | Sistem manajemen stok real-time berbasis Apache Kafka | Apache Kafka, event streaming | Latency pembaruan stok di bawah satu detik tercapai dengan arsitektur event streaming berbasis Kafka | PM ini menerapkan konsep serupa namun pada skala UKM dengan penambahan WebSocket untuk notifikasi langsung ke antarmuka kasir |
| 5  | Permatasari dkk. (2023) | Pengujian aplikasi sistem informasi berbasis web menggunakan blackbox testing | Blackbox Testing | Metode blackbox testing efektif untuk memverifikasi kesesuaian fungsionalitas sistem terhadap kebutuhan pengguna | PM ini menggunakan blackbox testing sebagai metode validasi akhir untuk seluruh skenario penggunaan modul kasir |

Berdasarkan kajian terhadap penelitian terdahulu pada Tabel 2.1, dapat diidentifikasi bahwa
sebagian besar penelitian sebelumnya membahas sistem POS atau kasir secara umum tanpa
mengintegrasikan mekanisme pembaruan stok real-time yang langsung terlihat di antarmuka
kasir. Penelitian yang membahas Kafka umumnya berfokus pada skalabilitas arsitektur atau
studi teoritis, bukan pada implementasi langsung untuk modul kasir skala UKM. Projek
Mandiri ini mengisi celah tersebut dengan mengintegrasikan Apache Kafka sebagai pemroses
event transaksi dan WebSocket sebagai mekanisme pengiriman pembaruan stok secara langsung
ke antarmuka kasir, sehingga staf kasir selalu mendapatkan informasi stok yang akurat tanpa
interupsi dalam alur pelayanan.
