# BAB I
# PENDAHULUAN

## A. Latar Belakang Masalah

Perkembangan teknologi informasi telah mengubah cara pelaku usaha ritel mengelola
operasional bisnis mereka. Di era digital saat ini, konsumen menuntut layanan yang cepat
dan akurat, sehingga sistem pencatatan transaksi manual tidak lagi memadai untuk memenuhi
ekspektasi tersebut. Usaha kecil dan menengah (UKM) di sektor ritel, khususnya toko grosir,
semakin didorong untuk mengadopsi sistem informasi berbasis teknologi guna meningkatkan
efisiensi operasional dan mengurangi potensi kesalahan pencatatan (Marpaung dkk., 2025;
Salwa, 2023).

Toko Melati adalah salah satu toko grosir yang berlokasi di Jalan Pahlawan No. 330, Tanjung,
Purwokerto Selatan, milik Ibu Karyoko dan telah beroperasi sejak tahun 2004. Toko ini
melayani pelanggan eceran dan partai besar dengan menjual berbagai kebutuhan sehari-hari
seperti sembako (beras, gula, minyak, dan lain-lain), rokok, minuman, serta makanan ringan.
Hingga saat ini, proses transaksi dan pencatatan stok di Toko Melati masih dilakukan secara
manual menggunakan buku catatan dan kalkulator. Kondisi ini menyebabkan sejumlah
permasalahan operasional, di antaranya lambatnya proses pelayanan pelanggan di kasir, rawan
terjadi kesalahan hitung, serta sulitnya memantau ketersediaan stok barang secara akurat pada
saat transaksi sedang berlangsung.

Salah satu solusi yang umum diterapkan untuk mengatasi permasalahan pencatatan transaksi
pada usaha ritel adalah sistem Point of Sales (POS). POS merupakan sistem informasi yang
dirancang untuk mencatat transaksi penjualan, mengelola data produk, menghitung total
pembayaran secara otomatis, serta memperbarui data stok setiap kali terjadi transaksi.
Penerapan sistem POS berbasis web dapat meningkatkan kecepatan pelayanan, akurasi
perhitungan, dan efisiensi pencatatan dibandingkan dengan metode manual (Maridaningsih
dkk., 2025). Sistem ini memudahkan staf kasir dalam melayani transaksi karena seluruh data
produk, harga, dan ketersediaan barang tersedia dalam satu antarmuka yang terintegrasi.

Salah satu tantangan dalam operasional kasir yang ramai adalah memastikan bahwa informasi
stok yang tampil di layar kasir selalu mencerminkan kondisi terkini. Ketika staf kasir sedang
memproses transaksi sementara stok barang diperbarui dari bagian lain seperti bagian gudang,
data stok di layar kasir berpotensi menjadi tidak akurat apabila tidak diperbarui secara
otomatis. Untuk mengatasi hal ini, diperlukan mekanisme pemrosesan event transaksi yang
andal sekaligus kemampuan pengiriman data secara langsung ke antarmuka kasir. Apache Kafka
sebagai message broker dimanfaatkan untuk memproses setiap event transaksi secara
asinkron — setiap kali kasir mencatat penjualan, Kafka menerima event tersebut, meneruskannya
ke konsumer yang memperbarui stok di basis data, kemudian hasilnya dikirimkan ke peramban
(browser) melalui teknologi WebSocket tanpa perlu memuat ulang halaman (Lu dkk., 2025;
Eka Yulianti dkk., 2025). Dengan mekanisme ini, staf kasir selalu mendapatkan informasi
stok yang akurat dan terkini selama proses transaksi berlangsung, sehingga risiko menjual
barang yang stoknya sudah habis dapat diminimalkan.

Berdasarkan uraian permasalahan di atas, dipandang perlu untuk merancang dan
mengimplementasikan sebuah aplikasi kasir berbasis web pada Toko Melati yang dapat
memproses transaksi penjualan secara efisien sekaligus menampilkan pembaruan stok secara
real-time. Aplikasi ini dikembangkan menggunakan framework Laravel 11 sebagai backend
dan Blade template engine dengan Tailwind CSS sebagai antarmuka pengguna, memanfaatkan
Apache Kafka sebagai message broker untuk memproses event transaksi secara asinkron, serta
teknologi WebSocket untuk mengirimkan pembaruan stok secara langsung ke antarmuka kasir.
Pengujian sistem dilakukan menggunakan metode blackbox testing, yaitu metode pengujian
yang memverifikasi fungsionalitas sistem dari sisi pengguna dengan membandingkan keluaran
terhadap masukan yang diberikan tanpa memperhatikan proses internal program (Ismail &
Efendi, 2020; Permatasari dkk., 2023).

---

## B. Rumusan Masalah

Berdasarkan uraian pada latar belakang, rumusan masalah dalam Projek Mandiri ini adalah:

1. Bagaimana mengimplementasikan aplikasi kasir berbasis web pada Toko Melati yang dapat
   memproses transaksi penjualan dan menampilkan pembaruan stok secara real-time
   menggunakan framework Laravel 11 dan teknologi WebSocket?

---

## C. Batasan Masalah

Projek Mandiri ini dibatasi oleh hal-hal sebagai berikut:

1. Aplikasi yang diimplementasikan berfokus pada modul kasir (*point of sale*), yang mencakup
   fitur: input dan pemrosesan transaksi penjualan, tampilan daftar produk beserta ketersediaan
   stok, pemilihan jumlah dan satuan barang, perhitungan total pembayaran, serta pencetakan
   struk transaksi.
2. Pembaruan stok secara real-time diimplementasikan melalui mekanisme event-driven
   menggunakan Apache Kafka sebagai message broker untuk memproses event transaksi secara
   asinkron, dan teknologi WebSocket untuk mengirimkan perubahan stok secara langsung ke
   antarmuka kasir tanpa perlu memuat ulang halaman.
3. Sistem dikembangkan menggunakan framework Laravel 11 sebagai backend, Node.js sebagai
   layanan Kafka producer/consumer dan WebSocket server, serta Blade template engine dengan
   Tailwind CSS sebagai antarmuka pengguna.
4. Basis data yang digunakan adalah MySQL 8.
5. Fitur manajemen pembelian, retur barang, pengelolaan pemasok, dan laporan manajemen
   berada di luar cakupan modul kasir pada Projek Mandiri ini.
6. Studi kasus dilaksanakan pada Toko Melati, Jalan Pahlawan No. 330, Tanjung, Purwokerto
   Selatan.
7. Pengujian sistem dilakukan menggunakan metode blackbox testing untuk memverifikasi
   fungsionalitas aplikasi kasir.

---

## D. Tujuan Projek Mandiri

Tujuan dari pelaksanaan Projek Mandiri ini adalah sebagai berikut:

1. Mengimplementasikan aplikasi kasir berbasis web menggunakan framework Laravel 11 yang
   mampu memproses transaksi penjualan secara efisien dan akurat pada Toko Melati.
2. Mengimplementasikan fitur pembaruan stok secara real-time menggunakan Apache Kafka
   sebagai message broker untuk memproses event transaksi dan teknologi WebSocket sebagai
   mekanisme pengiriman data ke antarmuka kasir, sehingga staf kasir selalu mendapatkan
   informasi ketersediaan stok yang akurat dan terkini selama proses transaksi berlangsung.

---

## E. Manfaat Projek Mandiri

Projek Mandiri ini diharapkan dapat memberikan manfaat sebagai berikut:

### 1. Manfaat Teoretis

Memberikan gambaran teknis mengenai implementasi Apache Kafka sebagai message broker
dan teknologi WebSocket untuk pembaruan data secara real-time pada aplikasi kasir berbasis
web menggunakan framework Laravel 11, yang dapat menjadi referensi bagi pengembangan
sistem informasi ritel pada usaha kecil dan menengah serupa.

### 2. Manfaat Praktis

a. **Bagi Toko Melati**, tersedianya aplikasi kasir berbasis web yang menggantikan proses
   pencatatan manual sehingga mempercepat layanan transaksi, meningkatkan akurasi
   perhitungan, dan meminimalkan risiko kesalahan pencatatan stok.

b. **Bagi staf kasir**, kemudahan dalam melayani transaksi penjualan dengan antarmuka
   yang intuitif serta informasi stok yang selalu diperbarui secara otomatis, sehingga
   pelayanan kepada pelanggan dapat dilakukan dengan lebih cepat dan percaya diri.

c. **Bagi pengembang**, pengalaman praktis dalam mengimplementasikan aplikasi berbasis
   web menggunakan framework Laravel 11 dan teknologi WebSocket dalam konteks nyata
   sistem informasi ritel.
