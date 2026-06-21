# BAB IV — HASIL DAN PEMBAHASAN (lanjutan)

## B. Perancangan Sistem

> **Petunjuk Penggunaan Dokumen Ini**
> Teks dalam tanda kurung siku `[X]` adalah placeholder yang harus diganti dengan
> data aktual (nomor gambar, nomor tabel, nomor sub-bab referensi BAB II, dan
> data pengujian). Seluruh teks di luar tanda kurung siku sudah mengikuti kaidah
> penulisan ilmiah sesuai `panduan_bab4_gaya_bahasa.json`.

---

## B.1 Arsitektur Sistem

Perancangan arsitektur Sistem POS Toko Melati didasarkan pada pendekatan
_event-driven architecture_ (arsitektur berbasis kejadian), di mana perubahan
data yang berdampak pada stok tidak ditulis langsung ke basis data oleh komponen
yang memicu perubahan tersebut, melainkan dipublikasikan sebagai pesan ke dalam
sebuah _message broker_ untuk diproses secara asinkron. Gambar [B.1] menampilkan
topologi keseluruhan sistem yang terdiri dari lima komponen utama: NGINX sebagai
_reverse proxy_, Laravel 11 sebagai _backend_ API sekaligus antarmuka pengguna,
Node.js sebagai layanan Kafka dan WebSocket, Apache Kafka sebagai _message broker_,
dan MySQL 8 sebagai penyimpanan data persisten.

Komponen NGINX berfungsi sebagai satu-satunya titik masuk (_single entry point_)
untuk seluruh permintaan dari peramban. Permintaan HTTP diteruskan ke proses
PHP-FPM pada _port_ 9000, sementara koneksi WebSocket pada jalur `/ws` diteruskan
ke layanan Node.js pada _port_ 3001. Pemisahan perutean berdasarkan jenis protokol
ini memungkinkan dua teknologi _backend_ yang berbeda — PHP dan Node.js — beroperasi
secara bersamaan di balik satu alamat publik tanpa mengekspos port internal secara
langsung. Hal ini juga menjadikan NGINX sebagai titik penerapan terpusat untuk
kebijakan keamanan jaringan, sebagaimana diuraikan pada Sub-bab [2.x].

Laravel 11 menjalankan dua peran sekaligus: menyajikan antarmuka pengguna melalui
_template_ Blade dan menyediakan API RESTful untuk seluruh operasi sistem. Untuk
operasi yang berdampak pada stok — transaksi penjualan, pembelian, retur, void,
dan penyesuaian stok — Laravel tidak menulis langsung ke basis data, melainkan
mengirimkan _payload_ kejadian ke layanan Node.js melalui HTTP POST ke
`http://kafka-service:3000`. Pemisahan ini disengaja: validasi data bisnis tetap
berada di Laravel yang memiliki ekosistem validasi matang (`FormRequest`,
`Eloquent`), sementara penanganan Kafka yang memerlukan I/O non-blokir diserahkan
ke Node.js. Hasil implementasi menunjukkan bahwa pemisahan ini juga mempermudah
pengujian unit karena logika bisnis Laravel dapat diverifikasi secara independen
dari infrastruktur Kafka.

Node.js menjalankan tiga fungsi dalam satu proses: menerima _payload_ kejadian dari
Laravel (_mode producer_), mempublikasikan pesan ke Kafka _broker_, mengonsumsi
pesan dari Kafka (_mode consumer_), dan mendorong pembaruan ke peramban melalui
WebSocket pada _port_ 3001. Pemilihan Node.js sebagai _runtime_ untuk lapisan ini
didasarkan pada karakteristik _event loop_ (putaran kejadian non-blokir) yang sesuai
dengan sifat pekerjaannya: menunggu konfirmasi dari Kafka _broker_ dan mengelola
banyak koneksi WebSocket secara bersamaan tanpa membuat _thread_ baru per koneksi,
sebagaimana diuraikan pada Sub-bab [2.x]. Library `kafkajs` versi 2.x digunakan
karena dukungan _consumer group_ dan _offset management_ yang matang tanpa
ketergantungan pada pustaka _native_ yang memerlukan kompilasi ulang saat perubahan
lingkungan.

Apache Kafka berperan sebagai komponen yang memisahkan _producer_ dari _consumer_
secara temporal dan spasial. Pemisahan ini memiliki implikasi teknis yang signifikan:
apabila _consumer_ mengalami kegagalan sementara, pesan tidak hilang karena tersimpan
dalam Kafka _log_ hingga berhasil dikonsumsi — jaminan pengiriman ini tidak tersedia
pada mekanisme _publish-subscribe_ berbasis _in-memory_ seperti Redis Pub/Sub.
Sistem menggunakan dua topik Kafka: `transaction-events` untuk kejadian transaksi
penjualan dan void, serta `inventory-updates` untuk pembelian, retur, dan penyesuaian
stok. Pemisahan ke dalam dua topik ini memungkinkan penerapan kebijakan retensi dan
prioritas pemrosesan yang berbeda per kategori kejadian di masa mendatang.

**Tabel B.1. Komponen Arsitektur, Teknologi, dan Peran dalam Sistem**

| Komponen         | Teknologi             | Port       | Peran Utama                               | Catatan Desain                                 |
| ---------------- | --------------------- | ---------- | ----------------------------------------- | ---------------------------------------------- |
| Reverse Proxy    | NGINX Alpine          | 80         | Single entry point, routing protokol      | HTTP → Laravel, WebSocket → Node.js            |
| Backend API & UI | Laravel 11 + PHP-FPM  | 9000       | Validasi, business logic, Blade rendering | Tidak menulis stok langsung ke DB              |
| Layanan Kafka    | Node.js + kafkajs 2.x | 3000, 3001 | Producer, Consumer, WebSocket server      | Event loop non-blokir untuk WS concurrency     |
| Message Broker   | Apache Kafka          | 9092       | Persistensi pesan, decoupling komponen    | 2 topik: transaction-events, inventory-updates |
| Basis Data       | MySQL 8               | 3306       | Penyimpanan persisten                     | Hanya ditulis oleh Consumer Node.js            |

Tabel B.1 menunjukkan bahwa setiap komponen memiliki tanggung jawab yang terdefinisi
dengan jelas dan tidak tumpang tindih. Arsitektur ini menghasilkan pemisahan tegas
antara operasi sinkron dan asinkron: semua operasi yang mengubah stok bersifat
asinkron melalui Kafka, sementara operasi yang tidak mengubah stok — seperti
pembayaran hutang dagang, CRUD data master, dan pembacaan laporan — berjalan secara
sinkron langsung ke basis data. Dengan demikian, antarmuka kasir dapat merespons
dalam hitungan milidetik dengan kode status HTTP 202 (_Accepted_) tanpa menunggu
proses penulisan basis data selesai, sehingga kecepatan pelayanan kasir tidak terikat
pada latensi I/O penulisan.

---

## B.2 Perancangan Basis Data

Perancangan basis data Sistem POS Toko Melati menghasilkan [X] tabel yang
dikelompokkan dalam empat paket fungsional: autentikasi, transaksi penjualan,
inventaris dan pembelian, serta keuangan. Gambar [B.2] menampilkan _Entity
Relationship Diagram_ (ERD) lengkap. Bagian ini menguraikan lima keputusan desain
utama yang memiliki implikasi teknis signifikan terhadap konsistensi data dan
keandalan sistem, alih-alih mendaftar seluruh kolom yang tersedia pada Lampiran
[nomor].

### B.2.1 Multi-Unit Pricing melalui Tabel `produk_satuan`

Temuan observasi O-09 pada Sub-bab A.2.3 — di mana satu produk tersedia dalam
beberapa satuan dengan harga berbeda per satuan — ditangani melalui pemisahan data
satuan ke tabel `produk_satuan` yang berelasi _many-to-one_ dengan tabel `produk`.
Setiap baris `produk_satuan` menyimpan nama satuan, nilai konversi `isi_satuan`
(jumlah satuan dasar yang dikandung satu satuan tersebut), dan harga jual untuk
satuan itu. Stok produk disimpan dalam satu kolom `qty_stok` pada tabel `produk`
selalu dalam satuan dasar, sehingga perhitungan perubahan stok untuk semua operasi
menggunakan rumus tunggal: `qty_berubah = qty × isi_satuan`. Keputusan menyimpan
stok dalam satuan terkecil ini menghindari anomali konsistensi yang akan terjadi
apabila stok disimpan per satuan — penjualan 1 karton (isi 96) dan penjualan 96 pcs
dari produk yang sama harus mengacu pada reservasi stok yang identik, sebuah
kondisi yang hanya dapat dijamin dengan representasi tunggal dalam satuan dasar.

### B.2.2 Snapshot pada `detail_transaksi`

Tabel `detail_transaksi` dirancang untuk menyimpan _snapshot_ data satuan pada saat
transaksi berlangsung — kolom `nama_satuan`, `isi_satuan`, dan `harga_satuan`
menyimpan nilai aktual pada waktu transaksi, bukan referensi _foreign key_ ke tabel
`produk_satuan`. Keputusan desain ini melindungi integritas data historis dari
perubahan yang terjadi di masa mendatang: apabila admin mengubah harga satuan karton
setelah tanggal tertentu, seluruh transaksi sebelum tanggal tersebut tetap
menampilkan harga karton yang berlaku pada waktu transaksi terjadi. Tanpa mekanisme
_snapshot_, laporan penjualan historis akan menampilkan nilai yang berubah setiap
kali harga diperbarui — sebuah kondisi yang secara langsung merusak keandalan laporan
keuangan dan menyulitkan rekonsiliasi data.

### B.2.3 Pemisahan `penyesuaian_stok` dari `retur_pembelian`

Pencatatan barang rusak, hilang, dan kedaluwarsa ditempatkan pada tabel
`penyesuaian_stok` yang berdiri sendiri, alih-alih digabungkan dengan mekanisme
retur pembelian. Pemisahan ini mencerminkan perbedaan semantik yang fundamental:
retur pembelian mencatat pengembalian barang ke _supplier_ yang menghasilkan kredit
nota, sementara penyesuaian stok mencatat kerugian internal toko tanpa kompensasi
finansial dari pihak luar. Menggabungkan keduanya dalam satu tabel akan mempersulit
pembuatan laporan kerugian internal yang akurat karena kedua jenis kejadian harus
dibedakan melalui kolom _flag_ tambahan — kompleksitas yang dihindari dengan
pemisahan tabel sejak perancangan. Kondisi ini juga konsisten dengan temuan
observasi O-05 pada Sub-bab A.2.4, di mana barang rusak di gudang dan retur ke
_supplier_ merupakan dua proses bisnis yang sepenuhnya terpisah.

### B.2.4 Pengelolaan Hutang Dagang melalui `pembayaran_pembelian`

Pembelian kredit kepada _supplier_ menghasilkan hutang dagang yang dapat dilunasi
dalam satu atau lebih cicilan. Skema ini ditangani melalui kolom `sisa_hutang`,
`status_pembayaran`, dan `tanggal_jatuh_tempo` pada tabel `pembelian`, dikombinasikan
dengan tabel `pembayaran_pembelian` yang menyimpan riwayat setiap pembayaran.
Pendekatan ini memisahkan antara _current state_ (status hutang terkini di tabel
`pembelian`) dengan _event log_ (riwayat pembayaran historis di `pembayaran_pembelian`),
sehingga dua pertanyaan bisnis yang paling sering diajukan — "berapa sisa hutang PO
ini?" dan "apa riwayat pembayarannya?" — dapat dijawab melalui kueri yang sederhana
dan tidak saling bergantung. Nilai `sisa_hutang` diperbarui secara atomik setiap
kali pembayaran baru dilakukan, memastikan nilai ini selalu mencerminkan kondisi
terkini tanpa perlu menghitung ulang dari seluruh riwayat setiap kali data dimuat.

### B.2.5 Soft Delete melalui Kolom `status`

Seluruh entitas yang pernah terlibat dalam transaksi — produk, pelanggan, _supplier_,
dan pengguna — menggunakan kolom `status ENUM('aktif','nonaktif')` sebagai mekanisme
penghapusan lunak (_soft delete_), alih-alih menghapus baris dari basis data. Hapus
fisik (_hard delete_) pada produk yang pernah terjual akan memutus relasi _foreign key_
pada `detail_transaksi`, yang mengakibatkan data transaksi historis menjadi tidak
dapat ditampilkan dalam laporan. Lebih jauh, pendekatan ini memudahkan audit data
karena seluruh entitas yang pernah aktif tetap dapat ditelusuri melalui kueri dengan
filter `status = 'nonaktif'` — informasi yang berguna apabila terjadi perselisihan
historis antara toko dan _supplier_ atau pelanggan.

---

## B.3 Perancangan Alur Proses

Alur proses sistem dimodelkan menggunakan *activity diagram* (diagram aktivitas) dan
*sequence diagram* (diagram urutan). *Activity diagram* menggambarkan urutan aktivitas
dan percabangan logis menggunakan notasi *swimlane* per komponen, sementara *sequence
diagram* menggambarkan pertukaran pesan antar komponen secara kronologis beserta
format dan kode status yang digunakan. Keduanya saling melengkapi: *activity diagram*
menjawab "apa yang terjadi dan dalam urutan apa", sementara *sequence diagram* menjawab
"siapa mengirim apa kepada siapa dan dalam format apa".

---

### B.3.1 Activity Diagram Transaksi Penjualan (AD-03)

Gambar [B.3] menampilkan *activity diagram* transaksi penjualan yang melibatkan empat *swimlane*: *Frontend*, *Laravel Backend*, *Kafka Broker*, dan *Consumer & Database*. Kasir memilih produk dan menginput pembayaran di *Frontend*, kemudian mengirimkan data melalui `POST /api/kasir/transaksi`; Laravel memvalidasi permintaan, menyisipkan transaksi berstatus `PENDING`, menerbitkan kejadian `transaction.created` ke Kafka, dan langsung mengembalikan `HTTP 202 Accepted` tanpa menunggu proses selesai. *Consumer* Node.js mengonsumsi kejadian secara asinkron, memvalidasi kecukupan stok menggunakan konversi `qty x isi_satuan`, memperbarui stok dan status transaksi menjadi `COMPLETED` atau `FAILED`, lalu mendorong notifikasi kepada *Frontend* melalui WebSocket.

---

### B.3.2 Activity Diagram Void Transaksi (AD-17)

Gambar [B.4] menampilkan *activity diagram* void transaksi yang memungkinkan kasir membatalkan transaksi yang sudah selesai dalam batas waktu 30 menit. Sistem memvalidasi empat kondisi secara berurutan: status `COMPLETED`, belum melewati batas waktu, belum pernah di-*void*, dan belum diretur; apabila seluruh kondisi terpenuhi, kasir memilih alasan dan mengkonfirmasi. Laravel menerbitkan kejadian `transaction.void.requested` ke Kafka dan mengembalikan `HTTP 202 Accepted`; *Consumer* menambahkan kembali stok seluruh item menggunakan konversi `qty x isi_satuan`, memperbarui status transaksi menjadi `VOIDED`, dan mendorong konfirmasi melalui WebSocket.

---

### B.3.3 Activity Diagram Pembayaran Hutang Dagang (AD-19)

Gambar [B.5] menampilkan *activity diagram* pembayaran hutang pembelian yang dirancang sebagai operasi sinkron tanpa melalui Kafka karena tidak mengubah kuantitas stok. Admin Gudang membuka daftar hutang, memilih PO, mengisi jumlah pembayaran beserta metode dan tanggal, kemudian mengirimkan `POST /api/gudang/pembelian/{id}/bayar`. Laravel memvalidasi tiga kondisi -- jumlah lebih dari nol, status belum lunas, dan jumlah tidak melebihi sisa hutang -- lalu melakukan `INSERT` ke tabel `pembayaran_pembelian`, memperbarui `sisa_hutang`, dan menetapkan `status_pembayaran` menjadi `lunas` atau `sebagian` secara langsung ke basis data.

---

### B.3.4 Activity Diagram Login (AD-01)

Gambar [B.6] menampilkan *activity diagram* login yang melibatkan tiga *swimlane*: *Frontend*, *Backend Laravel*, dan *Database*. Pengguna mengisi formulir dan mengirimkan `POST /api/auth/login`; Laravel memvalidasi format input, melakukan kueri data pengguna, memverifikasi kata sandi menggunakan `bcrypt`, serta memeriksa status akun secara berurutan -- setiap kegagalan menghasilkan `HTTP 422` atau `HTTP 401` dengan pesan generik. Apabila seluruh validasi berhasil, Laravel membangkitkan token JWT, menyisipkan sesi baru, memperbarui `last_login`, dan mengembalikan `HTTP 200 OK` beserta token dan informasi peran; *Frontend* menyimpan token di `localStorage` dan mengarahkan pengguna ke *dashboard* sesuai perannya.

---

### B.3.5 Activity Diagram Logout (AD-02)

Gambar [B.7] menampilkan *activity diagram* logout yang mendeskripsikan proses pengakhiran sesi pengguna secara aman. Pengguna menekan tombol *Logout* dan *Frontend* mengirimkan `POST /api/auth/logout` beserta *Bearer Token*; Laravel memvalidasi tanda tangan JWT, masa kedaluwarsa, dan status sesi. Apabila token valid, Laravel memperbarui `sessions.status` menjadi `revoked` dan mengembalikan `HTTP 200 OK`; *Frontend* menghapus token dari `localStorage` dan mengarahkan pengguna ke halaman *login*, termasuk apabila token sudah tidak valid.

---

### B.3.6 Activity Diagram Cetak Struk (AD-04)

Gambar [B.8] menampilkan *activity diagram* cetak struk yang dipicu secara eksplisit oleh kasir setelah menerima konfirmasi transaksi berhasil melalui WebSocket. Kasir menekan tombol *Cetak Struk* dan *Frontend* mengirimkan `GET /api/kasir/transaksi/{id}/struk`; *Backend* memvalidasi autentikasi, kepemilikan sesi, dan status `COMPLETED` secara berurutan sebelum mengambil data transaksi melalui kueri `JOIN` terhadap `detail_transaksi`. *Backend* membangkitkan *template* HTML struk dari data *snapshot* satuan dan harga, kemudian mengembalikan `HTTP 200 OK`; *Frontend* me-*render* pratinjau dan memicu dialog cetak *browser*.

---

### B.3.7 Activity Diagram Retur Penjualan (AD-05)

Gambar [B.9] menampilkan *activity diagram* retur penjualan yang menggunakan pola dua tahap. Pada tahap pertama, kasir menginputkan nomor transaksi dan sistem memvalidasi tiga kondisi kelayakan: status `COMPLETED`, belum di-*void*, dan belum diretur sebelumnya. Pada tahap kedua, kasir memilih item yang akan diretur beserta kuantitas dan alasan; Laravel memvalidasi bahwa `qty_retur` tidak melebihi kuantitas asli, menyisipkan data retur dengan *snapshot* satuan, menerbitkan kejadian `sale.return.requested` ke Kafka, dan mengembalikan `HTTP 202 Accepted`. *Consumer* menambahkan stok menggunakan konversi `qty_retur x isi_satuan` dan mendorong `return.sale.completed` beserta nominal *refund* melalui WebSocket.

---

### B.3.8 Activity Diagram Lihat Stok Real-Time (AD-06)

Gambar [B.10] menampilkan *activity diagram* monitoring stok *real-time* yang menggunakan pola hibrida HTTP dan WebSocket. Saat halaman dibuka, *Frontend* mengirimkan `GET /api/kasir/stok` dengan parameter filter dan paginasi; *Backend* mengembalikan data stok beserta `satuan_tersedia[]` berisi stok per satuan hasil kalkulasi `floor(qty_stok / isi_satuan)`, serta menandai produk di bawah stok minimum dengan `is_rendah = true`. Setelah tabel di-*render*, *Frontend* membangun koneksi WebSocket persisten dan menunggu kejadian `stock.updated` yang dikirimkan oleh *Consumer* setiap kali terjadi perubahan stok; antarmuka diperbarui secara langsung tanpa memuat ulang halaman.

---

### B.3.9 Activity Diagram Cek Stok (AD-07)

Gambar [B.11] menampilkan *activity diagram* cek stok yang dirancang sebagai operasi cepat satu arah tanpa WebSocket. Kasir menginput atau memindai kode produk dan *Frontend* mengirimkan `GET /api/kasir/produk/{kode}/stok`; *Backend* mengembalikan data stok beserta `satuan_tersedia[]` yang menampilkan ketersediaan dalam setiap satuan. *Frontend* menentukan tampilan berdasarkan dua nilai -- `qty_stok` dan `is_rendah` -- dengan tiga kondisi visual: peringatan *Stok Habis* yang memblokir lanjutan transaksi, peringatan *Stok Menipis* yang hanya bersifat informatif, atau informasi stok tersedia.

---

### B.3.10 Activity Diagram Manajemen Data Produk (AD-08)

Gambar [B.12] menampilkan *activity diagram* manajemen data produk yang mencakup operasi tambah, edit, dan hapus yang dapat dilakukan Admin Gudang. Operasi tambah menyimpan data induk produk terlebih dahulu kemudian secara otomatis menyisipkan satuan dasar ke tabel `produk_satuan` dengan nilai `isi=1` dan `is_default=1`; minimal satu satuan wajib ada agar produk dapat digunakan dalam transaksi. Operasi hapus memeriksa `COUNT(detail_transaksi)` dan `COUNT(detail_pembelian)` terlebih dahulu sebelum menentukan *soft delete* dengan `status = nonaktif` apabila produk pernah digunakan, atau *hard delete* apabila belum pernah terlibat dalam transaksi maupun pembelian.

---

### B.3.11 Activity Diagram Manajemen Pembelian (AD-09)

Gambar [B.13] menampilkan *activity diagram* pembelian yang menggabungkan logika kondisional berdasarkan tipe pembayaran dengan alur asinkron Kafka. Admin Gudang mengisi formulir beserta daftar item; untuk tipe `kredit`, kolom `tanggal_jatuh_tempo` wajib diisi dan divalidasi secara eksplisit di *Backend*. Laravel menghitung `sisa_hutang` dan `status_pembayaran`, menyisipkan data berstatus `PENDING`, menerbitkan kejadian `purchase.created` ke Kafka, dan mengembalikan `HTTP 202 Accepted`; *Consumer* menambahkan stok menggunakan konversi `qty x isi_satuan`, secara kondisional menyisipkan `pembayaran_pembelian`, dan mendorong `purchase.completed` beserta informasi hutang melalui WebSocket.

---

### B.3.12 Activity Diagram Retur Pembelian (AD-10)

Gambar [B.14] menampilkan *activity diagram* retur pembelian yang secara struktural serupa dengan retur penjualan, namun dengan operasi stok yang berlawanan arah. Admin Gudang menginputkan ID pembelian dan sistem memvalidasi satu kondisi kelayakan: status pembelian harus `COMPLETED`. Setelah data pembelian ditampilkan, Admin memilih item yang akan diretur; Laravel memvalidasi kuantitas, menyisipkan data retur dengan *snapshot* satuan, menerbitkan kejadian `purchase.return.requested` ke Kafka, dan mengembalikan `HTTP 202 Accepted`. *Consumer* mengurangi stok menggunakan konversi `qty_retur x isi_satuan` dan mendorong `return.purchase.completed` beserta nilai kredit nota melalui WebSocket.

---

### B.3.13 Activity Diagram Manajemen Data Customer (AD-11)

Gambar [B.15] menampilkan *activity diagram* manajemen data pelanggan yang dapat diakses oleh Kasir dan mencakup tiga operasi: tambah, edit, dan hapus. Operasi tambah memvalidasi keunikan `no_telp` dan menyimpan data beserta `tipe_customer`; operasi edit memvalidasi keunikan nomor telepon hanya apabila nilai berubah. Operasi hapus menerapkan *smart delete*: *soft delete* dengan `status = nonaktif` apabila pelanggan memiliki riwayat transaksi, atau *hard delete* apabila belum pernah bertransaksi, sehingga integritas data historis laporan tetap terjaga.

---

### B.3.14 Activity Diagram Riwayat Belanja Customer (AD-12)

Gambar [B.16] menampilkan *activity diagram* riwayat belanja pelanggan yang menggunakan pola dua tahap. Kasir mencari pelanggan menggunakan nama atau nomor telepon melalui `GET /api/kasir/customer?search={keyword}`, kemudian memilih pelanggan dari hasil pencarian untuk memuat riwayat transaksi menggunakan paginasi. Dalam fase interaktif, kasir dapat membuka detail satu transaksi dalam jendela *modal* atau memuat halaman riwayat berikutnya secara *lazy loading*; data yang ditampilkan bersumber dari kolom *snapshot* `detail_transaksi` sehingga harga dan satuan historis selalu akurat.

---

### B.3.15 Activity Diagram Kelola Supplier (AD-13)

Gambar [B.17] menampilkan *activity diagram* manajemen data *supplier* yang diakses oleh Admin Gudang dan mencakup tiga operasi CRUD. Alur ini identik secara struktural dengan manajemen pelanggan, namun dengan perbedaan pada pemeriksaan *smart delete*: sistem memeriksa `COUNT(pembelian)` alih-alih `COUNT(transaksi)`. *Soft delete* dieksekusi apabila *supplier* memiliki riwayat pembelian untuk mempertahankan integritas laporan dan data hutang dagang historis; *hard delete* dieksekusi apabila *supplier* belum pernah digunakan.

---

### B.3.16 Activity Diagram Manajemen User dan Hak Akses (AD-15)

Gambar [B.18] menampilkan *activity diagram* manajemen pengguna yang hanya dapat diakses oleh Pemilik Toko melalui pemeriksaan peran di lapisan *middleware*. Alur mencakup empat operasi: tambah pengguna dengan *bcrypt hashing* dan respons tanpa `password_hash`, edit data pengguna, *reset password* yang hanya memperbarui kolom `password_hash`, dan nonaktifkan pengguna. Operasi nonaktifkan menjalankan dua pembaruan berurutan -- `UPDATE users SET status=nonaktif` kemudian `UPDATE sessions SET status=revoked` -- untuk memastikan pengguna yang dinonaktifkan tidak dapat melanjutkan sesi aktif yang sudah ada.

---

### B.3.17 Activity Diagram Manajemen Kategori Produk (AD-16)

Gambar [B.19] menampilkan *activity diagram* manajemen kategori produk yang memiliki pola penghapusan unik dengan *frontend-gated pre-check*. Sebelum menampilkan dialog konfirmasi hapus, *Frontend* mengambil data kategori terkini termasuk nilai `jumlah_produk`; apabila lebih dari nol, dialog yang muncul hanya bersifat informatif tanpa tombol konfirmasi. Apabila `jumlah_produk = 0`, dialog konfirmasi ditampilkan dan *Backend* melakukan pemeriksaan ulang sebelum `DELETE` untuk menangani *race condition* jika ada produk yang baru ditambahkan ke kategori tersebut dalam jeda waktu antara validasi *Frontend* dan eksekusi penghapusan.

---

### B.3.18 Activity Diagram Penyesuaian Stok (AD-20)

Gambar [B.20] menampilkan *activity diagram* penyesuaian stok untuk pencatatan kerugian inventaris internal berupa barang rusak, hilang, kedaluwarsa, dan koreksi *opname*. Admin Gudang mencari produk, memilih tipe penyesuaian, dan menginput `qty_satuan_dasar` yang selalu bernilai positif -- arah stok ditentukan oleh tipe, bukan tanda nilai. Laravel memvalidasi produk aktif, kuantitas lebih dari nol, dan kuantitas tidak melebihi stok saat ini untuk tipe yang mengurangi; setelah INSERT berstatus `PENDING` dan publikasi kejadian `stock.adjustment.requested` ke Kafka, *Consumer* menentukan arah pembaruan stok berdasarkan tipe dan mendorong `adjustment.completed` beserta `stock.updated` melalui WebSocket.

---



---

### B.3.19 Sequence Diagram Login (SD-01)

Gambar [B.21] menampilkan *sequence diagram* login empat komponen: Pengguna, Web UI, Laravel API, dan MySQL. Tiga blok `alt` bersarang merepresentasikan validasi bertingkat -- format input, eksistensi pengguna, dan verifikasi `bcrypt` beserta cek `status = aktif` -- di mana setiap kegagalan menghasilkan `HTTP 422` atau `HTTP 401` generik. Setelah validasi berhasil, Backend menjalankan `INSERT INTO sessions` diikuti `UPDATE users SET last_login`, lalu mengembalikan `HTTP 200 OK` beserta token JWT, data pengguna, dan informasi peran dalam satu respons; *Frontend* menyimpan token dan mengarahkan pengguna ke *dashboard* sesuai `nama_role`.

---

### B.3.20 Sequence Diagram Logout (SD-02)

Gambar [B.22] menampilkan *sequence diagram* logout tiga komponen. Backend memvalidasi tanda tangan JWT, masa kedaluwarsa, dan `sessions.status = aktif` secara bersamaan sebelum menjalankan `UPDATE sessions SET status = revoked`; respons `HTTP 200 OK` dikembalikan, token dihapus dari `localStorage`, dan pengguna diarahkan ke halaman *login*. Apabila token tidak valid atau sudah dicabut, sistem tetap mengarahkan ke *login* dengan `HTTP 401` tanpa membedakan penyebab kegagalan.

---

### B.3.21 Sequence Diagram Input Transaksi Penjualan (SD-03)

Gambar [B.23] menampilkan *sequence diagram* transaksi penjualan delapan komponen yang merupakan diagram dengan kompleksitas pertukaran pesan tertinggi dalam sistem. Blok `loop` merepresentasikan penambahan item ke keranjang dengan penyimpanan `isi_satuan` sebagai *snapshot* di sisi klien; setelah konfirmasi, Laravel menyisipkan transaksi `PENDING`, meneruskan *payload* ke Node.js Producer yang menerbitkan `transaction.created` ke Kafka menggunakan notasi asinkron `-->>`, dan mengembalikan `HTTP 202 Accepted`. *Consumer* memvalidasi stok (`qty x isi_satuan`), memperbarui stok dan menyisipkan `detail_transaksi` apabila cukup, mengubah status menjadi `COMPLETED` atau `FAILED`, dan mendorong hasilnya melalui WebSocket.

---

### B.3.22 Sequence Diagram Cetak Struk (SD-04)

Gambar [B.24] menampilkan *sequence diagram* cetak struk empat komponen dengan tiga blok `alt` berurutan. Backend memvalidasi autentikasi (`HTTP 401`), kepemilikan sesi dan status `COMPLETED` (`HTTP 403`), kemudian menjalankan kueri `JOIN` terhadap `transaksi` dan `detail_transaksi` untuk mengambil data *snapshot* satuan dan harga apabila semua validasi berhasil. *Template* HTML dibangkitkan di *Backend* dan dikembalikan dalam `HTTP 200 OK`; *Frontend* me-*render* pratinjau dan memicu dialog cetak *browser*.

---

### B.3.23 Sequence Diagram Retur Penjualan (SD-05)

Gambar [B.25] menampilkan *sequence diagram* retur penjualan delapan komponen dengan pola dua fase. Fase pertama mengambil data transaksi dan memvalidasi tiga kondisi kelayakan; fase kedua menyisipkan `retur_penjualan` dan `detail_retur_penjualan` dengan *snapshot* satuan sebelum menerbitkan `sale.return.requested` ke Kafka, kemudian mengembalikan `HTTP 202 Accepted`. *Consumer* menambahkan stok menggunakan konversi `qty_retur x isi_satuan` dan mendorong `return.sale.completed` beserta `total_refund` melalui WebSocket.

---

### B.3.24 Sequence Diagram Lihat Stok Real-Time (SD-06)

Gambar [B.26] menampilkan *sequence diagram* monitoring stok *real-time* lima komponen. Permintaan `HTTP GET` awal mengambil data stok beserta `satuan_tersedia[]` dengan kalkulasi `floor(qty_stok / isi)` dan sorotan `is_rendah`; setelah *render* awal, *Frontend* membangun koneksi WebSocket ke server Node.js via NGINX. Dalam blok `loop`, setiap kejadian `stock.updated` memicu kalkulasi ulang stok per satuan menggunakan data `satuan_tersedia[]` yang sudah dimuat, sehingga tampilan diperbarui tanpa permintaan HTTP tambahan dari sisi klien.

---

### B.3.25 Sequence Diagram Cek Stok (SD-07)

Gambar [B.27] menampilkan *sequence diagram* cek stok empat komponen dengan satu kueri `SELECT` yang mengembalikan `satuan_tersedia[]`. Backend melakukan kueri berdasarkan `kode_produk` (bukan `id` numerik) untuk memudahkan kasir yang menggunakan *barcode scanner*; respons berisi stok dalam satuan dasar beserta larik satuan konversi. *Frontend* menentukan satu dari tiga tampilan berdasarkan `qty_stok` dan `is_rendah`: peringatan habis, peringatan menipis, atau informasi stok tersedia.

---

### B.3.26 Sequence Diagram Manajemen Data Produk (SD-08)

Gambar [B.28] menampilkan *sequence diagram* manajemen produk empat komponen dengan tiga cabang operasi CRUD. Pada tambah produk, setelah `INSERT INTO produk`, Backend secara otomatis menjalankan `INSERT INTO produk_satuan` dengan `isi=1, is_default=1` untuk satuan dasar. Pada hapus produk, dua kueri `COUNT(*)` ke `detail_transaksi` dan `detail_pembelian` dieksekusi berurutan untuk menentukan *soft delete* atau *hard delete*; setiap operasi diakhiri dengan permintaan *refresh* yang mengambil daftar produk terbaru dengan filter `WHERE status=aktif`.

---

### B.3.27 Sequence Diagram Manajemen Pembelian (SD-09)

Gambar [B.29] menampilkan *sequence diagram* pembelian delapan komponen. Blok `loop` merekam setiap item beserta `isi_satuan` ke dalam daftar; setelah konfirmasi, Laravel menghitung `sisa_hutang` dan `status_pembayaran`, menyisipkan `pembelian` dan `detail_pembelian`, meneruskan *payload* ke Kafka, dan mengembalikan `HTTP 202 Accepted`. *Consumer* memperbarui `qty_stok` dan `harga_beli_dasar` setiap produk, secara kondisional menyisipkan `pembayaran_pembelian` hanya untuk *cash* atau kredit dengan DP, dan mendorong `purchase.completed` beserta `stock.updated` melalui WebSocket.

---

### B.3.28 Sequence Diagram Retur Pembelian (SD-10)

Gambar [B.30] menampilkan *sequence diagram* retur pembelian delapan komponen. Fase pertama mengambil data pembelian dan memvalidasi satu kondisi: `status = COMPLETED`; fase kedua menyisipkan `retur_pembelian` dan `detail_retur_pembelian` dengan *snapshot* satuan sebelum menerbitkan `purchase.return.requested` ke Kafka. *Consumer* mengurangi stok (`qty_retur x isi_satuan`) -- berlawanan dengan *consumer* pembelian yang menambahkan -- dan mendorong `return.purchase.completed` beserta nilai kredit nota melalui WebSocket.

---

### B.3.29 Sequence Diagram Manajemen Data Customer (SD-11)

Gambar [B.31] menampilkan *sequence diagram* manajemen pelanggan empat komponen dengan struktur isomorfis dengan SD-08. Seluruh kueri `SELECT` daftar menggunakan filter `WHERE status=aktif`; validasi tambah memeriksa keunikan `no_telp` dengan pesan *field-level error*. Pada hapus, satu `COUNT(*)` ke `transaksi` dieksekusi untuk menentukan *soft delete* atau *hard delete*; setiap operasi diakhiri dengan *refresh* daftar pelanggan aktif.

---

### B.3.30 Sequence Diagram Riwayat Belanja Customer (SD-12)

Gambar [B.32] menampilkan *sequence diagram* riwayat belanja pelanggan empat komponen. Pencarian menggunakan kondisi `WHERE (nama LIKE ? OR no_telp LIKE ?) AND status=aktif`; riwayat dikembalikan dengan agregasi `COUNT(detail_transaksi) as jumlah_item` dan metrik `total_belanja` serta `frekuensi`. Dua blok `opt` -- detail transaksi dalam *modal* dan muat halaman berikutnya -- dapat dilakukan secara bergantian tanpa mengakhiri tampilan riwayat.

---

### B.3.31 Sequence Diagram Kelola Supplier (SD-13)

Gambar [B.33] menampilkan *sequence diagram* manajemen *supplier* empat komponen yang strukturnya isomorfis dengan SD-11, namun dengan pemeriksaan *smart delete* menggunakan `COUNT(pembelian)` alih-alih `COUNT(transaksi)`. Kueri daftar menyertakan kolom `kontak_person` untuk identifikasi perwakilan *sales*; validasi tambah memeriksa keunikan `no_telp`. Setiap operasi diakhiri dengan *refresh* daftar *supplier* aktif.

---

### B.3.32 Sequence Diagram Lihat Laporan (SD-14)

Gambar [B.34] menampilkan *sequence diagram* laporan tiga komponen dengan satu *endpoint* `GET /api/owner/laporan/{jenis}` yang melayani enam jenis laporan melalui parameter `{jenis}`. Backend memvalidasi peran `owner` dan mengembalikan `HTTP 403 Forbidden` -- bukan `HTTP 401` -- apabila peran tidak sesuai; kueri agregat yang berbeda dieksekusi per jenis laporan dan hasilnya dikembalikan dalam `{laporan, metrik, grafik_data}`. Blok `opt` ekspor mengirimkan file biner dengan `Content-Type: application/pdf` atau `application/xlsx` yang memicu unduhan otomatis di *browser*.

---

### B.3.33 Sequence Diagram Manajemen User dan Hak Akses (SD-15)

Gambar [B.35] menampilkan *sequence diagram* manajemen pengguna empat komponen dengan akses eksklusif untuk peran `owner`. Kueri daftar menggunakan `JOIN roles` untuk menampilkan `nama_role` langsung dan menyertakan `last_login`; tambah pengguna menerapkan *bcrypt hashing* dan mengembalikan data tanpa `password_hash`. Nonaktifkan pengguna menjalankan `UPDATE users SET status=nonaktif` diikuti `UPDATE sessions SET status=revoked` secara berurutan untuk memastikan sesi aktif segera tidak dapat dilanjutkan.

---

### B.3.34 Sequence Diagram Manajemen Kategori Produk (SD-16)

Gambar [B.36] menampilkan *sequence diagram* manajemen kategori empat komponen. Kueri daftar menggunakan `LEFT JOIN produk` dengan `COUNT(produk.id)` untuk menampilkan `jumlah_produk`; validasi tambah dilakukan dalam dua tahap terpisah -- format terlebih dahulu, kemudian keunikan. Pada hapus, *Frontend* mengambil data kategori beserta `jumlah_produk` sebelum menampilkan dialog; apabila nilai lebih dari nol, dialog informatif tanpa tombol konfirmasi ditampilkan, dan *Backend* mengulangi pemeriksaan sebelum `DELETE` untuk menangani *race condition*.

---

### B.3.35 Sequence Diagram Void Transaksi (SD-17)

Gambar [B.37] menampilkan *sequence diagram* void transaksi delapan komponen yang dibagi dalam tiga fase. Fase pertama mengambil data transaksi melalui kueri `JOIN` empat tabel untuk memeriksa semua kondisi kelayakan sekaligus dan mengembalikan `sisa_waktu_void`; fase kedua menjalankan `SELECT COUNT(*)` sebagai *race condition guard* sebelum `INSERT void_transaksi`. *Consumer* membungkus seluruh operasi dalam `BEGIN TRANSACTION ... COMMIT` -- memperbarui stok setiap item, mengubah status transaksi menjadi `VOIDED`, dan memperbarui `void_transaksi` menjadi `COMPLETED` -- kemudian mendorong `void.completed` dan `stock.updated` sebagai dua peristiwa WebSocket terpisah.

---

### B.3.36 Sequence Diagram Pembayaran Hutang Pembelian (SD-19)

Gambar [B.38] menampilkan *sequence diagram* pembayaran hutang tiga komponen sebagai operasi sinkron. Kueri daftar hutang menggunakan `LEFT JOIN pembayaran_pembelian` dengan `SUM(jumlah)` dan `DATEDIFF(tanggal_jatuh_tempo, CURDATE())` untuk menghasilkan `hari_tersisa`, diurutkan berdasarkan `tanggal_jatuh_tempo ASC`; respons menyertakan objek `ringkasan` berisi agregat total hutang. Setelah validasi tiga lapis, Backend menjalankan `INSERT pembayaran_pembelian`, `UPDATE sisa_hutang`, dan `UPDATE status_pembayaran` secara berurutan; *Frontend* menghapus baris apabila hutang lunas, atau memperbarui nilai sisa apabila masih ada cicilan.

---

### B.3.37 Sequence Diagram Penyesuaian Stok (SD-20)

Gambar [B.39] menampilkan *sequence diagram* penyesuaian stok delapan komponen yang dibagi dalam tiga fase. Riwayat penyesuaian dimuat menggunakan `JOIN` ke `produk` dan `users` untuk menampilkan nama produk dan nama pengguna yang membuat penyesuaian; pencarian produk menggunakan *endpoint* yang sama dengan SD-07 (`GET /api/kasir/produk/{kode}/stok`). *Consumer* membungkus operasi dalam `BEGIN TRANSACTION ... COMMIT`, menentukan arah `UPDATE produk` berdasarkan `tipe` dalam *payload*, memperbarui status penyesuaian menjadi `COMPLETED`, dan mendorong dua peristiwa WebSocket: `adjustment.completed` dan `stock.updated`.

---

## B.4 Perancangan Kelas dan Modul

Perancangan kelas Sistem POS Toko Melati dimodelkan menggunakan *class diagram* UML
yang menggambarkan struktur internal kelas, atribut, metode, dan relasi antar kelas.
Sistem menerapkan *Service Pattern* sebagai konvensi arsitektur utama: setiap domain
fungsional memiliki kelas *Service* yang bertanggung jawab atas seluruh logika bisnis,
sementara kelas *Model* hanya mendefinisikan atribut, validasi, dan relasi basis data
tanpa logika operasional. Pemisahan ini memungkinkan pengujian unit logika bisnis
secara terisolasi dari infrastruktur basis data, serta memudahkan penambahan fitur
baru tanpa memodifikasi model yang sudah berjalan. Bagian ini menguraikan lima *class
diagram* yang memetakan enam paket utama sistem.

---

### B.4.1 Gambaran Arsitektur Paket (CD-1)

Gambar [B.40] menampilkan diagram gambaran arsitektur paket (*package overview*) yang
mendeskripsikan enam paket utama sistem beserta arah ketergantungan antar paket:
`Auth`, `Transaction`, `Inventory`, `Purchasing`, `EventDriven` (Node.js), dan
`Reporting`. Diagram ini berfungsi sebagai peta navigasi tingkat tinggi yang
menunjukkan batas tanggung jawab setiap kelompok fungsional sebelum setiap paket
diuraikan secara rinci pada diagram-diagram berikutnya.

Paket `Auth` berfungsi sebagai fondasi keamanan yang menjadi acuan bagi paket lain —
paket `Transaction` dan `Purchasing` masing-masing bergantung pada `Auth` untuk data
identitas pengguna (`User`), ditunjukkan melalui panah ketergantungan putus-putus
(*dependency*). Ketergantungan bersifat penggunaan (*usage*), bukan kepemilikan
struktural, karena kedua paket hanya mengonsumsi identitas pengguna sebagai referensi
tanpa mengontrol siklus hidup entitas `User`. Paket `Transaction` juga bergantung pada
`Inventory` untuk data `Produk` dan `Customer`, sementara `Purchasing` bergantung pada
`Inventory` untuk data `Produk` dan `Supplier` — ketergantungan ini mencerminkan fakta
bahwa transaksi dan pembelian merupakan konsumen data referensi inventaris, bukan
pemiliknya.

Paket `EventDriven` memiliki posisi unik: ia menjadi tujuan *publish event* dari tiga
paket sekaligus — `Transaction`, `Purchasing`, dan `Inventory` — namun tidak memiliki
ketergantungan balik kepada ketiganya. Ketiadaan panah dari `EventDriven` ke paket
lain merupakan keputusan desain yang disengaja: *Consumer* Node.js mengakses basis
data MySQL secara langsung melalui *connection pool*, bukan melalui Model atau Service
Laravel. Pemisahan ini mempertahankan kemampuan setiap sisi untuk berkembang secara
independen — perubahan pada Service Laravel tidak berdampak pada Consumer Node.js
selama *event payload contract* Kafka tetap konsisten.

Paket `Reporting` adalah satu-satunya paket yang bersifat murni *read-only*: seluruh
panah ketergantungannya menunjuk ke `Transaction`, `Purchasing`, dan `Inventory` sebagai
sumber agregasi, tanpa ketergantungan sebaliknya dari ketiga paket tersebut ke
`Reporting`. Karakteristik ini memiliki implikasi arsitektur yang penting — kegagalan
pada fitur laporan (misalnya *timeout* kueri agregat yang kompleks) tidak berdampak
pada kemampuan sistem memproses transaksi. Isolasi paket `Reporting` dari jalur kritis
operasional kasir mencerminkan prinsip pemisahan antara operasi yang mengubah data
(*write path*) dan operasi yang hanya membaca (*read path*), sebagaimana diuraikan
dalam karakteristik arsitektur pada Sub-bab B.1.

---

### B.4.2 Paket Auth dan Transaction (CD-2)

Gambar [B.41] menampilkan *class diagram* paket `Auth` dan `Transaction`. Paket `Auth`
terdiri dari tiga model (`Role`, `User`, `Session`) dan dua *service* (`UserService`,
`SessionService`), sementara paket `Transaction` terdiri dari lima model dan tiga
*service*. Seluruh *service* menggunakan panah ketergantungan (`..>`) ke model yang
dikelola dan ke `KafkaProducerService` untuk operasi yang mengubah stok — pola ini
konsisten di seluruh sistem dan memperlihatkan secara visual mana operasi yang masuk
jalur asinkron Kafka.

Pada kelas `User`, atribut `passwordHash` dideklarasikan dengan visibilitas *private*
(prefiks `-`) — satu-satunya atribut privat dalam seluruh *class diagram* sistem.
Perbedaan visibilitas ini bukan sekadar konvensi keamanan; ia mencerminkan kontrak
implementasi yang dapat diverifikasi: tidak ada metode publik pada kelas `User` yang
mengembalikan nilai `passwordHash`, sehingga nilai ini tidak pernah hadir dalam
respons API, *log* sistem, atau representasi objek yang diekspos ke lapisan presentasi.
Kehadiran kelas `Session` sebagai entitas tersendiri — bukan sekadar klaim dalam JWT
— memungkinkan metode `revokeAllByUser(userId)` pada `SessionService` untuk mencabut
seluruh sesi aktif milik satu pengguna sekaligus, sebuah kemampuan yang dieksploitasi
oleh operasi nonaktifkan pengguna pada AD-15 dan SD-15.

Pada paket `Transaction`, kelas `DetailTransaksi` memiliki tiga atribut *snapshot*:
`namaSatuan`, `isiSatuan`, dan `hargaSatuan`. Ketiganya menyimpan nilai aktual pada
saat transaksi terjadi, bukan referensi *foreign key* ke tabel `produk_satuan`. Dari
perspektif kelas, `DetailTransaksi` dengan demikian berperan sebagai *value object*
yang tidak bergantung pada konsistensi relasional dengan `ProdukSatuan` untuk
menghasilkan laporan atau struk yang akurat — sebuah properti yang sudah dianalisis
dari perspektif basis data pada Sub-bab B.2.2 dan kini terlihat implementasinya di
level kelas. Relasi komposisi (`*--`) antara `Transaksi` dan `DetailTransaksi`,
`ReturPenjualan`, serta `VoidTransaksi` menyatakan bahwa ketiga entitas turunan tidak
memiliki eksistensi independen di luar konteks transaksi induknya.

Kelas `VoidTransaksi` memiliki atribut `transaksiBaruId` — referensi opsional ke
transaksi pengganti yang dapat dibuat kasir setelah pembatalan. Atribut ini bersifat
opsional karena kasir dapat memilih untuk tidak membuat transaksi baru setelah void,
konsisten dengan alur opsional di akhir SD-17. Kelas `VoidTransaksiService` mendefinisikan
metode `getSisaWaktuVoid(transaksi)` yang menghitung sisa waktu dalam menit berdasarkan
selisih `transaksi.createdAt` dengan waktu sekarang — nilai yang dikembalikan dalam
respons fase pertama SD-17 sebagai `sisa_waktu_void: int` untuk menampilkan *countdown*
kepada kasir.

---

### B.4.3 Paket Inventory (CD-3)

Gambar [B.42] menampilkan *class diagram* paket `Inventory` yang merupakan paket
dengan jumlah kelas terbanyak dalam sistem: delapan model dan delapan *service*.
Kepadatan ini mencerminkan cakupan tanggung jawab inventaris yang meluas — tidak hanya
produk dan stok, tetapi juga pelanggan, *supplier*, kategori, promosi, dan penyesuaian
stok sebagai domain yang secara logis berkaitan dengan inventaris toko.

Kelas `Produk` tidak memiliki atribut `hargaJual` secara langsung. Harga jual
disimpan di kelas `ProdukSatuan` yang berelasi *one-to-many* dengan `Produk` —
implementasi *multi-unit pricing* yang dianalisis pada Sub-bab B.2.1. Kelas
`ProdukSatuan` mendefinisikan dua metode komputasi: `getHargaEfektifPerDasar()` yang
menghitung harga per satuan dasar untuk perbandingan efisiensi antar satuan, dan
`getStokDalamSatuan()` yang menghasilkan stok dalam satuan tersebut menggunakan
`floor(qtyStok / isi)`. Penempatan kedua metode komputasi di dalam model — alih-alih
di *service* — didasarkan pada prinsip enkapsulasi: objek yang mengetahui datanya
sendiri lebih tepat menanggung tanggung jawab kalkulasi yang hanya bergantung pada
data internal tersebut, sehingga pemanggil tidak perlu mengetahui formula konversi.

Kelas `ProdukPromo` mendefinisikan dua set atribut untuk dua tipe promosi: atribut
berbasis diskon (`diskonNilai`) dan atribut berbasis bonus (`bonusQty`, `bonusProdukId`,
`bonusSatuanId`). Dua set ini secara logika bersifat *mutually exclusive* — promosi
diskon tidak menggunakan atribut bonus, dan sebaliknya — namun keduanya ditempatkan
dalam satu kelas yang memanfaatkan nilai `NULL` untuk kolom yang tidak relevan. Atribut
`berlakuDari`, `berlakuSampai`, dan `isActive` memungkinkan manajemen siklus hidup
promosi yang fleksibel: promosi dapat dijadwalkan untuk periode tertentu secara otomatis
dan dinonaktifkan secara manual sebelum masa berlaku habis melalui metode `toggle(id)`
pada `PromoService`.

Kelas `PenyesuaianStok` mendefinisikan dua metode komputasi `getIsTipeTambah()` dan
`getIsTipeKurang()` yang mengembalikan boolean berdasarkan nilai kolom `tipe`. Kedua
metode ini mengeliminasi kebutuhan `if-else` atau `switch` yang berulang di lapisan
*service* dan *consumer* — komponen lain cukup memanggil `penyesuaian.getIsTipeTambah()`
tanpa perlu mengetahui daftar nilai enum yang termasuk kategori tambah. Pola enkapsulasi
ini juga memastikan bahwa apabila tipe penyesuaian baru ditambahkan di masa mendatang,
hanya kedua metode ini yang perlu diperbarui, bukan semua titik dalam kode yang
mengandung kondisi tipe.

---

### B.4.4 Paket Purchasing (CD-4)

Gambar [B.43] menampilkan *class diagram* paket `Purchasing` yang mengelola seluruh
siklus pengadaan barang dari *supplier* beserta pengelolaan hutang dagang. Paket ini
terdiri dari lima model dan tiga *service*, dengan satu karakteristik yang
membedakannya dari paket `Transaction`: tidak semua *service* bergantung pada
`KafkaProducerService` — khususnya `PembayaranPembelianService` yang merupakan operasi
finansial murni tanpa perubahan stok.

Kelas `Pembelian` memiliki kepadatan atribut finansial tertinggi di antara seluruh
model sistem: `tipePembayaran`, `jumlahDp`, `sisaHutang`, `tanggalJatuhTempo`,
`statusPembayaran`, dan `catatanPembayaran`. Keenam atribut ini merepresentasikan
seluruh dimensi hutang dagang yang diperlukan untuk mengelola siklus pembayaran kredit.
Selain atribut data, `Pembelian` juga mendefinisikan tiga metode komputasi: `getTotalDibayar()`
yang menjumlahkan seluruh pembayaran dari relasi `pembayaran()`, `getHariTersisa()`
yang menghitung selisih antara `tanggalJatuhTempo` dan tanggal sekarang, dan
`getIsJatuhTempo()` yang mengembalikan boolean. Ketiga metode ini mengenkapsulasi
logika kalkulasi yang digunakan oleh `LaporanService` untuk menghasilkan laporan hutang
dan oleh SD-19 untuk menampilkan kolom `hari_tersisa` dalam daftar hutang.

Catatan dalam diagram menyatakan secara eksplisit bahwa *pembayaran hutang tidak
melalui Kafka*. Pernyataan ini merupakan kontrak arsitektur yang terdokumentasi dalam
diagram: `PembayaranPembelianService` tidak memiliki panah ketergantungan ke
`KafkaProducerService`, sementara `PembelianService` dan `ReturPembelianService`
memilikinya. Pola ini menghasilkan aturan yang dapat diverifikasi secara visual dari
diagram: setiap *service* yang memiliki panah ke `KafkaProducerService` mengelola
operasi yang mengubah stok, sementara yang tidak memilikinya adalah operasi finansial
murni. Konsistensi aturan ini memudahkan evaluasi kode selama *code review* — setiap
penambahan ketergantungan baru ke `KafkaProducerService` dari sebuah *service* harus
dapat dijustifikasi dengan dampak perubahan stok yang nyata.

Kelas `PembayaranPembelian` eksis sebagai model tersendiri karena hutang dapat dilunasi
dalam beberapa cicilan yang masing-masing harus tercatat dengan metode, tanggal, dan
catatan yang berbeda. Relasi komposisi `Pembelian *-- PembayaranPembelian` menyatakan
bahwa riwayat pembayaran tidak memiliki eksistensi independen dari PO-nya. Pola
*snapshot* pada `DetailReturPembelian` — yang menyimpan `namaSatuan`, `isiSatuan`, dan
`hargaSatuan` saat retur — konsisten dengan pola yang sama pada `DetailTransaksi`,
`DetailReturPenjualan`, dan `DetailPembelian`, memastikan seluruh dokumen historis
dapat ditampilkan dengan nilai yang berlaku pada saat dokumen dibuat.

---

### B.4.5 Paket Event-Driven dan Reporting (CD-5)

Gambar [B.44] menampilkan *class diagram* paket `EventDriven` dan `Reporting`. Paket
`EventDriven` adalah satu-satunya paket dalam sistem yang berisi kelas dari dua
*runtime* berbeda: `KafkaProducerService` berjalan di PHP/Laravel, sementara seluruh
`Consumer` dan `WebSocketService` berjalan di Node.js. Penggabungan keduanya dalam
satu paket mencerminkan kesatuan fungsional sebagai lapisan integrasi asinkron,
meskipun secara implementasi mereka berada di proses dan kontainer Docker yang terpisah.

`KafkaProducerService` mendefinisikan enam metode `publish*` yang masing-masing
berkorespondensi dengan satu jenis kejadian Kafka. Pemisahan menjadi enam metode yang
berbeda — alih-alih satu metode `publish(eventName, payload)` generik — memiliki
manfaat dari perspektif keamanan tipe dan dokumentasi: setiap pemanggil di Laravel
mengetahui persis *payload* apa yang dibutuhkan untuk setiap jenis kejadian, dan
perubahan format *payload* satu jenis kejadian tidak berisiko memengaruhi jenis
kejadian lain. Atribut privat `producerUrl` menyimpan alamat *endpoint* Node.js
(`http://kafka-service:3000`) — penggunaan nama *service* Docker (bukan `localhost`)
yang ditetapkan sebagai aturan arsitektur pada Sub-bab B.1 kini terlihat keterikatannya
di level atribut kelas.

Lima kelas *Consumer* masing-masing beroperasi pada topik dan *consumer group* yang
berbeda. `ReturnConsumer` merupakan satu-satunya *consumer* yang menangani dua jenis
kejadian: `processSaleReturn()` dan `processPurchaseReturn()`. Konsolidasi dua jenis
retur dalam satu *consumer* didasarkan pada kesamaan pola pemrosesan yang tinggi —
keduanya mengambil `isiSatuan`, menghitung perubahan stok, dan memperbarui status —
meskipun arah perubahan stok berlawanan. Diagram menampilkan dua topik Kafka yang
digunakan: `transaction-events` untuk kejadian transaksi penjualan dan void, serta
`inventory-updates` untuk pembelian, retur, dan penyesuaian stok. Pemisahan ke dua
topik memungkinkan kebijakan retensi dan prioritas *consumer group* yang berbeda per
kategori kejadian — kejadian transaksi yang mempengaruhi pengalaman kasir secara
langsung dapat diprioritaskan terpisah dari kejadian inventaris yang lebih toleran
terhadap latensi.

Kelas `WebSocketService` mendefinisikan dua struktur data internal: `channels` bertipe
`Map` dan `wsChannels` bertipe `WeakMap`. `Map` digunakan untuk `channels` karena kunci
berupa string *channel name* yang membutuhkan iterasi dan penghapusan eksplisit. `WeakMap`
dipilih untuk `wsChannels` — yang memetakan setiap objek koneksi WebSocket ke *channel*
yang dilangganinya — karena `WeakMap` memungkinkan *garbage collector* JavaScript
membebaskan memori secara otomatis ketika objek koneksi WebSocket tidak lagi
direferensikan (misalnya ketika *browser* kasir ditutup), tanpa memerlukan panggilan
`unsubscribe()` yang eksplisit dari sisi klien. Dua metode distribusi `push()` dan
`pushAll()` mencerminkan dua pola yang digunakan: notifikasi ke *channel* spesifik
untuk kejadian yang relevan bagi kasir tertentu, dan *broadcast* ke semua *channel*
untuk pembaruan stok yang relevan bagi seluruh terminal aktif sekaligus.

Paket `Reporting` hanya berisi satu kelas — `LaporanService` — yang mendefinisikan
tujuh metode `generate*` dan dua metode ekspor. Ketujuh jenis laporan didefinisikan
sebagai metode terpisah karena masing-masing mengimplementasikan kueri agregat yang
berbeda secara signifikan: laporan penjualan meng-*query* `transaksi + detail_transaksi`,
laporan stok meng-*query* `produk + produk_satuan`, laporan hutang meng-*query*
`pembelian` dengan filter `status_pembayaran`, dan seterusnya. Pendekatan satu metode
per laporan lebih mudah dipelihara dibandingkan satu metode generik dengan banyak
percabangan internal. Dua metode ekspor — `exportCsv()` dan `exportHtml()` — menghasilkan
format yang berbeda untuk kebutuhan berbeda: format HTML digunakan untuk laporan yang
dicetak melalui mekanisme cetak *browser*, sementara format CSV cocok untuk analisis
lanjutan menggunakan *spreadsheet* eksternal.

---

## B.5 Perancangan Antarmuka Pengguna

Antarmuka Sistem POS Toko Melati dirancang menggunakan Laravel Blade sebagai
_template engine_ dengan Tailwind CSS sebagai _framework_ CSS berbasis utilitas.
Perancangan antarmuka diturunkan dari tiga prinsip yang berakar pada temuan
observasi dan wawancara yang telah diuraikan pada Sub-bab A.2 dan A.3, bukan
sekadar pertimbangan estetika.

Prinsip pertama adalah **minimalisasi langkah per transaksi**. Kasir tunggal yang
melayani rata-rata [X] transaksi per jam tidak memiliki toleransi terhadap antarmuka
yang memerlukan banyak navigasi antar halaman. Halaman kasir dirancang sebagai
_single-page_ dengan panel produk, keranjang belanja, dan ringkasan pembayaran yang
seluruhnya terlihat dalam satu tampilan tanpa gulir (_scroll_) vertikal pada resolusi
monitor standar (1366×768 piksel). Penambahan produk ke keranjang dilakukan melalui
pencarian _real-time_ menggunakan Axios tanpa memuat ulang halaman, sehingga kasir
dapat menambahkan item berikutnya segera setelah item sebelumnya ditambahkan.
Gambar [B.6] menampilkan tata letak halaman kasir beserta distribusi panel-panel
fungsionalnya.

Prinsip kedua adalah **umpan balik status yang eksplisit**. Karena transaksi bersifat
asinkron, antarmuka harus selalu menampilkan status terkini secara jelas kepada
kasir. Koneksi WebSocket dipertahankan secara persisten selama sesi kasir aktif;
setiap perubahan status transaksi — dari `PENDING` ke `COMPLETED` atau `FAILED` —
mendorong pembaruan visual yang berbeda tanpa memerlukan tindakan apa pun dari kasir.
Gambar [B.7] menampilkan tiga kondisi visual antarmuka kasir: status menunggu
(_processing_), konfirmasi berhasil, dan konfirmasi gagal beserta pesan alasannya.
Pemisahan visual yang tegas antara ketiga kondisi ini bertujuan mengurangi risiko
kasir salah menginterpretasikan status transaksi saat kondisi ramai.

Prinsip ketiga adalah **pemisahan akses berdasarkan peran**. Antarmuka yang tersedia
berbeda berdasarkan peran pengguna yang terautentikasi: kasir hanya mengakses modul
transaksi, retur, dan informasi stok; admin gudang mengakses modul pembelian,
penyesuaian stok, dan manajemen produk; pemilik toko mengakses seluruh laporan dan
manajemen pengguna. Pembatasan ini diterapkan di dua lapisan secara bersamaan —
_middleware_ Laravel yang memeriksa peran sebelum permintaan mencapai _controller_,
dan templat Blade yang tidak merender tautan navigasi ke halaman yang tidak diizinkan.
Dua lapisan ini memastikan bahwa pembatasan akses tidak bergantung semata pada
elemen antarmuka yang dapat dimanipulasi oleh pengguna dengan pengetahuan teknis.

---

_Dokumen ini merupakan bagian dari:_
_Skripsi: "Perancangan Sistem Point of Sales Toko Grosir dengan Antarmuka Real-Time_
_Berbasis Arsitektur Event Streaming"_
_Terakhir diperbarui: Mei 2026_
