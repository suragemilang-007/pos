# RINGKASAN

Perkembangan teknologi informasi menuntut pelaku usaha ritel untuk meningkatkan
efisiensi operasional, namun Toko Melati yang berlokasi di Jalan Pahlawan No. 330,
Purwokerto Selatan, hingga saat ini masih mengelola proses transaksi dan pencatatan
stok secara manual menggunakan buku catatan dan kalkulator. Kondisi ini menyebabkan
lambatnya pelayanan di kasir, tingginya risiko kesalahan hitung, serta ketidakakuratan
informasi stok yang tampil saat transaksi berlangsung — terutama ketika beberapa kasir
beroperasi secara bersamaan atau stok diperbarui dari bagian gudang. Projek Mandiri ini
bertujuan mengimplementasikan aplikasi kasir berbasis web pada Toko Melati yang mampu
memproses transaksi penjualan secara efisien sekaligus menampilkan pembaruan stok secara
real-time kepada staf kasir tanpa perlu memuat ulang halaman. Sistem dikembangkan
menggunakan framework Laravel 11 sebagai backend, Apache Kafka sebagai message broker
untuk memproses event transaksi secara asinkron, teknologi WebSocket sebagai mekanisme
pengiriman pembaruan stok langsung ke antarmuka kasir, serta MySQL 8 sebagai basis data
yang semuanya dikontainerisasi menggunakan Docker. Pengembangan dilaksanakan dengan
metode Waterfall melalui empat tahap berurutan, yaitu analisis kebutuhan, perancangan
sistem (arsitektur, basis data, dan antarmuka), implementasi, serta pengujian menggunakan
metode blackbox testing berdasarkan dua belas skenario uji yang dipetakan dari kebutuhan
fungsional sistem. Hasil yang diharapkan dari Projek Mandiri ini adalah tersedianya aplikasi
kasir yang menggantikan proses pencatatan manual di Toko Melati, mempercepat layanan
transaksi, meningkatkan akurasi perhitungan, serta memberikan informasi ketersediaan stok
yang selalu terkini kepada kasir secara otomatis — sehingga risiko penjualan barang dengan
stok habis dapat diminimalkan dan kepercayaan staf terhadap akurasi data sistem meningkat.

Kata kunci: Aplikasi Kasir, Laravel, WebSocket, Apache Kafka, Real-Time
