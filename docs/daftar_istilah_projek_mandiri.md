# DAFTAR ISTILAH

| Istilah | Definisi |
|---------|----------|
| **Aplikasi Kasir** | Perangkat lunak yang digunakan staf kasir untuk memproses transaksi penjualan, menampilkan daftar produk, menghitung total pembayaran, dan mencetak struk secara otomatis. |
| **Apache Kafka** | Platform *event streaming* terdistribusi yang berfungsi sebagai *message broker* untuk mengelola aliran pesan antar layanan secara andal dan efisien dengan model *publish-subscribe*. |
| **API** | *Application Programming Interface* — antarmuka pemrograman yang memungkinkan dua sistem perangkat lunak saling berkomunikasi dan bertukar data menggunakan format yang telah disepakati. |
| **Blackbox Testing** | Metode pengujian perangkat lunak yang mengevaluasi fungsionalitas sistem dari perspektif pengguna akhir tanpa memperhatikan struktur atau logika internal (*source code*) program. |
| **Blade** | *Template engine* bawaan Laravel yang digunakan untuk membangun antarmuka halaman web secara dinamis menggunakan sintaks PHP yang ringkas. |
| **Container** | Unit terisolasi yang membungkus aplikasi beserta seluruh dependensinya (pustaka, konfigurasi, *runtime*) agar dapat berjalan secara konsisten di berbagai lingkungan tanpa konflik. |
| **Docker** | Platform kontainerisasi *open-source* yang memungkinkan aplikasi dikemas, didistribusikan, dan dijalankan dalam *container* terisolasi. |
| **Docker Compose** | Alat bantu Docker untuk mendefinisikan dan menjalankan beberapa *container* sekaligus menggunakan satu file konfigurasi (`docker-compose.yml`). |
| **Event** | Peristiwa atau perubahan data yang terjadi dalam sistem, misalnya transaksi penjualan yang berhasil dikonfirmasi oleh kasir. |
| **Event-Driven** | Pendekatan arsitektur sistem di mana setiap komponen bereaksi terhadap *event* yang terjadi secara otomatis tanpa memerlukan intervensi manual. |
| **Framework** | Kerangka kerja perangkat lunak yang menyediakan struktur, komponen, dan konvensi dasar untuk mempercepat dan menyeragamkan proses pengembangan aplikasi. |
| **Full-Duplex** | Mode komunikasi di mana data dapat mengalir secara bersamaan di kedua arah (klien ke server dan server ke klien) dalam satu koneksi. |
| **Kafka Consumer** | Komponen dalam arsitektur Kafka yang bertugas membaca dan memproses pesan dari suatu *topic*. Dalam sistem ini, Consumer memperbarui stok di basis data setelah event transaksi diterima. |
| **Kafka Producer** | Komponen dalam arsitektur Kafka yang bertugas mengirimkan pesan ke suatu *topic*. Dalam sistem ini, Producer mempublikasikan event transaksi dari Laravel ke Kafka. |
| **Kafka Topic** | Kategori atau saluran bernama tempat pesan diorganisasikan dalam Kafka. Producer mengirim pesan ke *topic*, dan Consumer membaca dari *topic* yang sama. |
| **Laravel** | *Framework* pengembangan aplikasi web berbasis PHP yang mengikuti pola arsitektur MVC (*Model-View-Controller*) dan menyediakan berbagai fitur bawaan seperti Eloquent ORM, routing, dan migrasi basis data. |
| **Message Broker** | Perangkat lunak perantara yang menerima pesan dari pengirim (*producer*), menyimpannya sementara, dan meneruskannya kepada penerima (*consumer*) yang relevan. |
| **Migrasi** | Fitur Laravel untuk membuat dan mengelola perubahan skema basis data secara terstruktur menggunakan kode PHP sehingga dapat dilacak melalui *version control*. |
| **MVC** | *Model-View-Controller* — pola arsitektur perangkat lunak yang memisahkan aplikasi menjadi tiga komponen: Model (data), View (tampilan), dan Controller (logika pengendali). |
| **MySQL** | Sistem manajemen basis data relasional (*RDBMS*) berbasis SQL yang bersifat *open-source*, digunakan untuk menyimpan dan mengelola data terstruktur dalam bentuk tabel. |
| **Nginx** | *Web server* dan *reverse proxy* yang mengarahkan (*routing*) permintaan HTTP dari peramban pengguna ke layanan *backend* yang sesuai (Laravel atau WebSocket). |
| **Node.js** | *Runtime environment* JavaScript sisi server berbasis mesin V8 yang efisien dalam menangani banyak koneksi secara bersamaan menggunakan model pemrosesan non-blocking I/O. |
| **PHP** | *Hypertext Preprocessor* — bahasa pemrograman *server-side* yang banyak digunakan dalam pengembangan aplikasi web. Laravel dibangun di atas bahasa ini. |
| **POS** | *Point of Sales* — sistem informasi yang difungsikan untuk mencatat transaksi penjualan, mengelola data produk, menghitung total pembayaran, serta memperbarui data stok secara otomatis. |
| **Real-Time** | Kemampuan sistem untuk memproses dan menampilkan data secara langsung segera setelah perubahan terjadi, tanpa jeda yang signifikan antara peristiwa dan tampilannya di antarmuka pengguna. |
| **Reverse Proxy** | Server perantara yang menerima permintaan dari klien dan meneruskannya ke server *backend* yang sesuai, sehingga klien tidak berkomunikasi langsung dengan server aplikasi. |
| **Seeder** | Skrip pada Laravel yang digunakan untuk mengisi data awal (*initial data*) ke dalam basis data, seperti akun pengguna default dan data produk contoh. |
| **Service Pattern** | Pola desain perangkat lunak yang memisahkan logika bisnis ke dalam kelas *Service* tersendiri agar Controller tetap ringkas dan kode mudah diuji serta dipelihara. |
| **Snapshot** | Salinan data pada suatu titik waktu tertentu yang disimpan secara permanen. Dalam sistem ini, nama produk, satuan, dan harga disimpan sebagai *snapshot* di tabel detail transaksi untuk melindungi data historis dari perubahan di masa depan. |
| **Stok** | Jumlah ketersediaan barang yang tersimpan dalam satuan terkecil (*satuan dasar*) pada suatu waktu tertentu. |
| **Tailwind CSS** | *Framework* CSS berbasis utilitas yang menyediakan kelas-kelas siap pakai untuk membangun antarmuka web yang responsif dan konsisten secara visual. |
| **Waterfall** | Metode pengembangan perangkat lunak yang dilaksanakan secara bertahap dan berurutan: analisis kebutuhan → perancangan → implementasi → pengujian. |
| **WebSocket** | Protokol komunikasi berbasis standar RFC 6455 yang menyediakan saluran komunikasi dua arah (*full-duplex*) persisten antara server dan peramban, memungkinkan server mengirim data ke klien kapan saja tanpa permintaan terlebih dahulu. |
