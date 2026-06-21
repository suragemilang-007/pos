# BAB IV — HASIL DAN PEMBAHASAN (lanjutan)

## B.3.4 Perancangan Alur Proses Operasi CRUD

> **Petunjuk Penggunaan Dokumen Ini**
> Teks `[Gambar X.X]` adalah placeholder yang harus diganti dengan nomor gambar aktual
> sesuai urutan penyisipan diagram dalam dokumen skripsi. Seluruh teks di luar
> placeholder sudah mengikuti kaidah penulisan ilmiah sesuai `panduan_bab4_gaya_bahasa.json`.

---

Operasi *Create, Read, Update, Delete* (CRUD) pada modul manajemen produk, pelanggan,
*supplier*, dan pengguna dirancang menggunakan prinsip satu diagram per kasus
penggunaan (*one diagram per use case*) — setiap operasi tambah, edit, dan hapus
dimodelkan dalam *activity diagram* dan *sequence diagram* yang berdiri sendiri,
alih-alih disatukan dalam satu diagram dengan percabangan berlapis. Keputusan ini
didasarkan pada dua pertimbangan: pertama, ketiga operasi memiliki prasyarat,
kondisi validasi, dan implikasi terhadap integritas data yang berbeda sehingga
penyatuan diagram akan menghasilkan struktur *nested* yang sulit dibaca pada format
cetak; kedua, pemisahan memungkinkan analisis keputusan desain per operasi dilakukan
secara independen dan lebih mendalam. Seluruh operasi CRUD bersifat sinkron dan
tidak melalui Kafka karena tidak satu pun dari operasi tersebut mengubah kuantitas
stok secara langsung, sebagaimana ditetapkan dalam kebijakan arsitektur pada
Sub-bab B.1.

**Tabel B.3. Daftar Diagram CRUD dan Karakteristik Teknis**

| Kode AD | Kode SD | Operasi              | Aktor         | Endpoint                                    | Metode | Kode HTTP |
| ------- | ------- | -------------------- | ------------- | ------------------------------------------- | ------ | --------- |
| AD-08b  | SD-08b  | Tambah Produk        | Admin Gudang  | `POST /api/gudang/produk`                   | POST   | 201       |
| AD-08c  | SD-08c  | Edit Produk          | Admin Gudang  | `PUT /api/gudang/produk/{id}`               | PUT    | 200       |
| AD-08d  | SD-08d  | Hapus Produk         | Admin Gudang  | `DELETE /api/gudang/produk/{id}`            | DELETE | 200       |
| AD-11b  | SD-11b  | Tambah Customer      | Kasir         | `POST /api/kasir/customer`                  | POST   | 201       |
| AD-11c  | SD-11c  | Edit Customer        | Kasir         | `PUT /api/kasir/customer/{id}`              | PUT    | 200       |
| AD-11d  | SD-11d  | Hapus Customer       | Kasir         | `DELETE /api/kasir/customer/{id}`           | DELETE | 200       |
| AD-13b  | SD-13b  | Tambah Supplier      | Admin Gudang  | `POST /api/gudang/supplier`                 | POST   | 201       |
| AD-13c  | SD-13c  | Edit Supplier        | Admin Gudang  | `PUT /api/gudang/supplier/{id}`             | PUT    | 200       |
| AD-13d  | SD-13d  | Hapus Supplier       | Admin Gudang  | `DELETE /api/gudang/supplier/{id}`          | DELETE | 200       |
| AD-15b  | SD-15b  | Tambah User          | Pemilik Toko  | `POST /api/owner/user`                      | POST   | 201       |
| AD-15c  | SD-15c  | Edit User            | Pemilik Toko  | `PUT /api/owner/user/{id}`                  | PUT    | 200       |
| AD-15d  | SD-15d  | Reset Password       | Pemilik Toko  | `PATCH /api/owner/user/{id}/reset-password` | PATCH  | 200       |
| AD-15e  | SD-15e  | Nonaktifkan User     | Pemilik Toko  | `PATCH /api/owner/user/{id}/nonaktifkan`    | PATCH  | 200       |

Tabel B.3 menunjukkan bahwa seluruh 13 operasi CRUD menggunakan metode HTTP yang
sesuai dengan semantik REST: POST untuk penciptaan sumber daya baru yang menghasilkan
HTTP 201 *Created*, PUT untuk pembaruan penuh yang menghasilkan HTTP 200 *OK*,
PATCH untuk pembaruan parsial (reset password dan nonaktifkan), serta DELETE untuk
penghapusan. Konsistensi pemilihan metode HTTP ini memungkinkan implementasi
*middleware* autentikasi dan otorisasi berbasis metode HTTP yang seragam di seluruh
rute Laravel.

---

### B.3.4.1 Manajemen Produk

#### Tambah Produk (AD-08b, SD-08b)

Gambar [Gambar X.X] menampilkan *activity diagram* penambahan produk (AD-08b) yang
dirancang dengan dua *swimlane*: Frontend dan Backend Laravel. Formulir tambah
produk menerima dua kelompok data sekaligus: data produk utama (`kode_produk`,
`nama_produk`, `kategori_id`, `harga_beli_dasar`, `qty_stok`, `stok_minimum`,
`satuan_dasar`) dan data satuan dasar pertama (`nama_satuan`, `isi=1`, `harga_beli`,
`harga_jual`). Setelah validasi `FormRequest` berhasil, Backend menjalankan dua
operasi penyisipan secara berurutan: INSERT ke tabel `produk` untuk memperoleh
`id_produk` baru, kemudian INSERT ke tabel `produk_satuan` menggunakan `id_produk`
tersebut dengan nilai `is_default=1`.

Keharusan melakukan dua INSERT yang berurutan ini merupakan konsekuensi langsung
dari desain *multi-unit pricing* yang diuraikan pada Sub-bab B.2.1 — tabel `produk`
tidak menyimpan `harga_jual` secara langsung, melainkan mendelegasikannya ke tabel
`produk_satuan`. Setiap produk yang baru dibuat wajib memiliki minimal satu entri
`produk_satuan` sebagai satuan dasar (`isi=1`); tanpa entri ini, produk tidak
memiliki harga dan tidak dapat diproses dalam antarmuka transaksi. *Sequence diagram*
SD-08b pada Gambar [Gambar X.X] memperlihatkan urutan dua pesan ke basis data —
`INSERT INTO produk` diikuti `INSERT INTO produk_satuan` — yang hanya dieksekusi
setelah validasi `FormRequest` berhasil dilewati, memastikan tidak ada produk tanpa
satuan yang tersimpan ke sistem.

#### Edit Produk (AD-08c, SD-08c)

Gambar [Gambar X.X] menyajikan *activity diagram* edit produk (AD-08c) yang
memerlukan dua perjalanan HTTP: `GET /api/gudang/produk/{id}` untuk memuat data
*existing* ke formulir (*pre-fill*), kemudian `PUT /api/gudang/produk/{id}` untuk
mengirimkan perubahan yang ditetapkan. Backend memvalidasi keunikan `kode_produk`
dengan pengecualian untuk produk yang sedang diedit (`unique:produk,kode_produk,{id}`)
sehingga Admin Gudang dapat menyimpan perubahan meski tidak mengubah kode produk,
tanpa sistem salah mendeteksi duplikasi dari produk itu sendiri. Setelah validasi
berhasil, `UPDATE produk SET ... WHERE id = ?` dieksekusi untuk memperbarui
kolom-kolom yang berubah.

Operasi edit produk dirancang untuk tidak menyentuh tabel `produk_satuan` sama
sekali, karena pengelolaan satuan dan harga per satuan merupakan operasi terpisah
yang dilayani oleh endpoint `/api/gudang/produk/{id}/satuan`. Pemisahan ini
mencerminkan prinsip *single responsibility* di mana satu endpoint hanya menangani
satu entitas — mencampurkan pembaruan `produk` dan `produk_satuan` dalam satu
endpoint PUT akan memperumit validasi dan meningkatkan risiko pembaruan parsial
yang tidak konsisten apabila salah satu operasi gagal.

#### Hapus Produk (AD-08d, SD-08d)

Gambar [Gambar X.X] menampilkan *activity diagram* hapus produk (AD-08d) yang
merupakan operasi dengan alur paling kompleks di antara ketiga operasi manajemen
produk. Setelah Admin Gudang mengkonfirmasi penghapusan, Backend menjalankan dua
kueri `COUNT` secara terpisah: `SELECT COUNT(*) FROM detail_transaksi WHERE produk_id = ?`
dan `SELECT COUNT(*) FROM detail_pembelian WHERE produk_id = ?`. Hasil kedua kueri
ini secara bersama-sama menentukan jenis operasi yang dieksekusi — *soft delete*
(`UPDATE produk SET status='nonaktif'`) apabila produk pernah digunakan, atau
*hard delete* apabila belum pernah digunakan.

Pengecekan terhadap dua tabel sekaligus — bukan hanya `detail_transaksi` — didasarkan
pada pertimbangan integritas referensial yang lebih luas: produk yang pernah masuk
ke pembelian memiliki relasi ke `detail_pembelian` yang menyimpan `harga_beli_satuan`
historis, dan menghapusnya secara fisik akan memutus jejak audit pembelian meski
produk tersebut tidak pernah terjual. Apabila kedua COUNT mengembalikan nol, *hard
delete* dieksekusi dalam urutan yang tidak dapat dibalik: `DELETE FROM produk_satuan
WHERE produk_id = ?` wajib dieksekusi terlebih dahulu sebelum `DELETE FROM produk
WHERE id = ?`. Urutan ini diwajibkan oleh *foreign key constraint* antara
`produk_satuan.produk_id` dan `produk.id`; mengabaikannya akan mengakibatkan
*constraint violation* pada basis data meski kedua operasi secara logika valid.
Hal ini konsisten dengan prinsip *soft delete* yang diuraikan pada Sub-bab B.2.5.

**Tabel B.4. Ringkasan Keputusan Desain Manajemen Produk**

| Operasi | Endpoint                         | Kode HTTP | Keputusan Desain Kritis                                      |
| ------- | -------------------------------- | --------- | ------------------------------------------------------------ |
| Tambah  | `POST /api/gudang/produk`        | 201       | Dual INSERT: `produk` lalu `produk_satuan` (wajib ada satuan)|
| Edit    | `PUT /api/gudang/produk/{id}`    | 200       | Tidak menyentuh `produk_satuan` — endpoint terpisah          |
| Hapus   | `DELETE /api/gudang/produk/{id}` | 200       | COUNT dua tabel; DELETE `produk_satuan` sebelum `produk`     |

---

### B.3.4.2 Manajemen Customer

#### Tambah Customer (AD-11b, SD-11b)

Gambar [Gambar X.X] menampilkan *activity diagram* penambahan pelanggan (AD-11b).
Kasir mengisi formulir dengan empat *field* — `nama`, `no_telp`, `alamat`, dan
`tipe_customer` — yang dikirimkan melalui `HTTP POST /api/kasir/customer`. Validasi
`FormRequest` di Backend memastikan bahwa `no_telp` belum terdaftar di tabel
`customers`, karena kolom ini memiliki *constraint* `UNIQUE` dalam skema basis data.
Apabila validasi gagal, HTTP 422 dikembalikan beserta detail galat per kolom sehingga
Kasir dapat langsung mengidentifikasi *field* yang bermasalah tanpa mengirimkan ulang
seluruh formulir.

Perlu ditekankan bahwa validasi keunikan hanya diterapkan pada kolom `no_telp`,
bukan pada kolom `nama` — dua pelanggan berbeda dapat memiliki nama yang sama,
tetapi tidak boleh berbagi nomor telepon yang sama karena nomor telepon berfungsi
sebagai identitas kontak yang dapat dihubungi. Desain ini mencerminkan realitas
operasional toko grosir di mana pelanggan pedagang pasar yang berbelanja atas nama
keluarga sering memiliki nama yang serupa, namun dapat dibedakan melalui nomor
telepon yang unik. *Sequence diagram* SD-11b memperlihatkan bahwa seluruh alur
tambah pelanggan ini menggunakan endpoint di bawah `/api/kasir/` yang hanya dapat
diakses oleh pengguna dengan peran Kasir, sesuai dengan kebijakan kontrol akses
berbasis peran sistem.

#### Edit Customer (AD-11c, SD-11c)

Gambar [Gambar X.X] menyajikan *activity diagram* edit pelanggan (AD-11c) yang
mengikuti pola dua perjalanan HTTP identik dengan edit produk: `GET /api/kasir/customer/{id}`
untuk memuat data *existing* ke formulir, kemudian `PUT /api/kasir/customer/{id}`
untuk mengirimkan perubahan. Validasi keunikan `no_telp` pada operasi edit
menggunakan pengecualian untuk ID pelanggan yang sedang diedit, sehingga seorang
Kasir dapat menyimpan perubahan data lain tanpa mengubah nomor telepon, tanpa sistem
salah mendeteksi duplikasi dari pelanggan itu sendiri. Hasil implementasi menunjukkan
bahwa penggunaan pengecualian `unique:customers,no_telp,{id}` pada `FormRequest`
merupakan cara yang tepat untuk menangani pembaruan data yang memuat *constraint*
unik, sebagaimana didukung oleh fitur validasi bawaan Laravel.

#### Hapus Customer (AD-11d, SD-11d)

Gambar [Gambar X.X] menampilkan *activity diagram* hapus pelanggan (AD-11d). Backend
melakukan `SELECT COUNT(*) FROM transaksi WHERE customer_id = ?` sebelum menentukan
jenis operasi hapus yang dieksekusi. Apabila pelanggan memiliki riwayat transaksi,
*soft delete* diterapkan dengan `UPDATE customers SET status='nonaktif'` untuk
memastikan seluruh data riwayat belanja tetap dapat ditampilkan pada laporan
historis. Apabila pelanggan belum pernah bertransaksi, *hard delete* dieksekusi
langsung karena tidak ada relasi yang perlu dilindungi.

Keputusan untuk memproteksi pelanggan yang memiliki riwayat transaksi dari *hard
delete* bertumpu pada pertimbangan bahwa laporan penjualan per pelanggan dan analisis
perilaku pembelian pelanggan grosir merupakan informasi bisnis yang bernilai bagi
pemilik toko. Menghapus pelanggan secara fisik akan memutus relasi `transaksi.customer_id`
dan mengakibatkan seluruh riwayat belanjanya tidak dapat dikaitkan dengan identitas
pelanggan yang bersangkutan — kondisi yang merusak akurasi laporan pelanggan
sebagaimana didefinisikan pada fitur AD-12 (Riwayat Belanja Customer).

**Tabel B.5. Ringkasan Keputusan Desain Manajemen Customer**

| Operasi | Endpoint                          | Kode HTTP | Keputusan Desain Kritis                                |
| ------- | --------------------------------- | --------- | ------------------------------------------------------ |
| Tambah  | `POST /api/kasir/customer`        | 201       | Validasi unik hanya `no_telp` (bukan `nama`)           |
| Edit    | `PUT /api/kasir/customer/{id}`    | 200       | Unique check dengan pengecualian ID pelanggan sendiri  |
| Hapus   | `DELETE /api/kasir/customer/{id}` | 200       | *Soft delete* jika pernah bertransaksi; *hard delete* jika belum |

---

### B.3.4.3 Manajemen Supplier

#### Tambah dan Edit Supplier (AD-13b, AD-13c, SD-13b, SD-13c)

Gambar [Gambar X.X] dan [Gambar X.X] menampilkan *activity diagram* tambah dan edit
*supplier* (AD-13b dan AD-13c). Formulir tambah *supplier* menerima `nama_supplier`
dan `no_telp` sebagai *field* wajib, serta `kontak_person` dan `alamat` sebagai
*field* opsional, yang dikirimkan melalui `HTTP POST /api/gudang/supplier`. Validasi
keunikan hanya diterapkan pada kolom `no_telp` — kolom `nama_supplier` tidak
memiliki *constraint* `UNIQUE` dalam ERD karena dua distributor yang berlokasi di
wilayah berbeda dapat menggunakan nama perusahaan yang sama. Operasi edit (AD-13c)
mengikuti pola dua perjalanan HTTP yang identik dengan edit produk dan pelanggan,
dengan pengecualian ID *supplier* pada validasi `no_telp`.

Perbedaan utama manajemen *supplier* dibandingkan manajemen pelanggan terletak pada
kewenangan akses: seluruh endpoint *supplier* berada di bawah `/api/gudang/` dan
hanya dapat diakses oleh Admin Gudang, bukan Kasir. Hal ini mencerminkan pemisahan
tanggung jawab operasional — pengelolaan relasi bisnis dengan *supplier* merupakan
domain Admin Gudang yang menangani pengadaan barang, sementara Kasir hanya
berinteraksi dengan pelanggan di sisi penjualan.

#### Hapus Supplier (AD-13d, SD-13d)

Gambar [Gambar X.X] menyajikan *activity diagram* hapus *supplier* (AD-13d) yang
memiliki logika penghapusan paling bertingkat di antara seluruh operasi hapus CRUD
dalam sistem. Backend memeriksa `SELECT COUNT(*) FROM pembelian WHERE supplier_id = ?`
untuk menentukan apakah *supplier* masih memiliki riwayat pembelian yang perlu
dilindungi. Apabila riwayat pembelian ada, *soft delete* dieksekusi dengan
`UPDATE suppliers SET status='nonaktif'`. Apabila tidak ada riwayat pembelian,
*hard delete* dilaksanakan dalam dua langkah berurutan: `DELETE FROM produk_supplier
WHERE supplier_id = ?` terlebih dahulu, kemudian `DELETE FROM suppliers WHERE id = ?`.

Pilihan tabel `pembelian` sebagai referensi pengecekan — bukan tabel `produk_supplier`
— didasarkan pada pertimbangan bahwa data di tabel `pembelian` mengandung nilai
finansial, informasi hutang dagang, dan riwayat pembayaran yang berdampak langsung
pada laporan keuangan toko. Menghapus *supplier* yang masih memiliki catatan
pembelian aktif akan memutus relasi tersebut dan menyebabkan data hutang dagang
kehilangan konteks siapa *supplier*-nya — sebuah kondisi yang merusak akurasi
laporan hutang sebagaimana didefinisikan pada AD-19. Adapun urutan dua langkah
*hard delete* — membersihkan `produk_supplier` sebelum `suppliers` — diwajibkan
oleh *foreign key constraint* `produk_supplier.supplier_id → suppliers.id`;
mengabaikan urutan ini akan memicu *constraint violation* meski kedua operasi
secara logika valid.

**Tabel B.6. Ringkasan Keputusan Desain Manajemen Supplier**

| Operasi | Endpoint                           | Kode HTTP | Keputusan Desain Kritis                                           |
| ------- | ---------------------------------- | --------- | ----------------------------------------------------------------- |
| Tambah  | `POST /api/gudang/supplier`        | 201       | Validasi unik hanya `no_telp` (bukan `nama_supplier`)             |
| Edit    | `PUT /api/gudang/supplier/{id}`    | 200       | Unique check dengan pengecualian ID *supplier* sendiri            |
| Hapus   | `DELETE /api/gudang/supplier/{id}` | 200       | Cek `pembelian`; DELETE `produk_supplier` sebelum `suppliers`     |

---

### B.3.4.4 Manajemen User

Manajemen pengguna memiliki pola yang paling berbeda dibandingkan tiga modul
manajemen lainnya: tidak terdapat operasi hapus (*delete*) sama sekali — baik
*soft delete* maupun *hard delete*. Sebagai gantinya, terdapat dua operasi khusus
yang mencerminkan kebutuhan operasional lebih kompleks: reset password dan
nonaktifkan. Keempat operasi tambah, edit, reset password, dan nonaktifkan
menggunakan endpoint yang berbeda-beda di bawah `/api/owner/user`, mencerminkan
prinsip bahwa setiap operasi dengan logika validasi dan dampak berbeda layak
memiliki kontrak API tersendiri.

#### Tambah User (AD-15b, SD-15b)

Gambar [Gambar X.X] menampilkan *activity diagram* tambah pengguna (AD-15b). Pemilik
Toko mengisi formulir dengan `nama`, `username`, `password`, dan `role_id`, yang
dikirimkan melalui `HTTP POST /api/owner/user`. Setelah validasi `FormRequest`
berhasil memastikan keunikan `username` dan kekuatan minimum password, Backend
menjalankan fungsi `bcrypt::hash()` pada nilai password sebelum melakukan INSERT ke
tabel `users` dengan kolom `password_hash`. Response HTTP 201 yang dikembalikan
tidak menyertakan nilai `password_hash` — hanya `id`, `nama`, `username`, `role_id`,
dan `status`.

Keputusan untuk tidak pernah mengembalikan `password_hash` dalam response API
apapun diterapkan secara konsisten di seluruh sistem. Hal ini mencegah kebocoran
*hash* yang dapat dimanfaatkan dalam serangan *offline brute force* — meski bcrypt
dirancang lambat, pemaparan *hash* tetap memberikan penyerang basis komputasi untuk
percobaan. *Sequence diagram* SD-15b pada Gambar [Gambar X.X] memperlihatkan
secara eksplisit bahwa pesan `INSERT INTO users` menggunakan kolom `password_hash`
(bukan `password`), dan response `HTTP 201` yang dikembalikan ke Frontend secara
eksplisit dikecualikan dari kolom tersebut.

#### Edit User (AD-15c, SD-15c)

Gambar [Gambar X.X] menyajikan *activity diagram* edit pengguna (AD-15c) yang
dirancang untuk mengubah `nama` dan `role_id` saja, tanpa menyentuh password.
Pemisahan operasi edit profil dari reset password ke dalam endpoint berbeda — `PUT`
untuk edit dan `PATCH` untuk reset — didasarkan pada perbedaan logika validasi yang
fundamental: edit profil memerlukan validasi `role_id` dan opsional keunikan
`username`, sementara reset password memerlukan validasi kekuatan password dan
*confirmation matching*. Menggabungkan keduanya dalam satu endpoint PUT akan memaksa
implementasi menangani kondisi opsional yang rawan kesalahan — misalnya, apakah
`password_hash` diperbarui atau tidak bergantung pada kehadiran *field* tersebut
dalam *request body*.

#### Reset Password (AD-15d, SD-15d)

Gambar [Gambar X.X] menampilkan *activity diagram* reset password (AD-15d) yang
menggunakan metode HTTP PATCH pada endpoint `/api/owner/user/{id}/reset-password`.
*Request body* hanya menerima `password_baru` dan `password_confirmation`; Backend
memvalidasi kesesuaian antara keduanya dan panjang minimum password menggunakan
`FormRequest`. Setelah validasi berhasil, password baru di-*hash* menggunakan
`bcrypt` sebelum `UPDATE users SET password_hash = ? WHERE id = ?` dieksekusi.
*Sequence diagram* SD-15d pada Gambar [Gambar X.X] memperlihatkan bahwa operasi ini
hanya menyentuh kolom `password_hash` — tidak ada kolom lain yang diperbarui —
sehingga perubahan password terisolasi dan tidak berpotensi menimpa perubahan data
profil yang mungkin terjadi secara bersamaan.

Pemilihan metode PATCH (bukan PUT) untuk reset password didasarkan pada semantik
HTTP yang tepat: PUT menyiratkan pembaruan representasi sumber daya secara penuh,
sementara PATCH menyiratkan pembaruan sebagian. Karena hanya satu kolom yang
diubah dan *request body* tidak merepresentasikan seluruh objek pengguna, PATCH
adalah pilihan yang lebih semantis dan konsisten dengan standar REST. Pendekatan
ini juga memudahkan otorisasi berbasis metode HTTP di lapisan *middleware* Laravel
— PATCH `/reset-password` dapat dikonfigurasi dengan kebijakan otorisasi yang
berbeda dari PUT tanpa mengubah logika utama.

#### Nonaktifkan User (AD-15e, SD-15e)

Gambar [Gambar X.X] menyajikan *activity diagram* nonaktifkan pengguna (AD-15e)
yang menggantikan operasi hapus konvensional. Setelah Pemilik Toko mengkonfirmasi
tindakan, Backend menjalankan dua operasi ke basis data dengan urutan yang tidak
dapat dibalik: pertama `UPDATE users SET status='nonaktif' WHERE id = ?`, kemudian
`UPDATE sessions SET status='revoked' WHERE user_id = ? AND status='active'`. Urutan
ini ditetapkan secara eksplisit karena memiliki alasan keamanan yang konkret — apabila
revoke *session* dieksekusi sebelum status pengguna diperbarui, terdapat celah waktu
(*time window*) di mana *session* sudah tidak valid di tabel tetapi pengguna masih
tercatat aktif, sebuah inkonsistensi yang dapat mempengaruhi logika validasi login
selanjutnya.

Tidak adanya operasi hapus pada modul pengguna mencerminkan pertimbangan audit dan
akuntabilitas yang lebih dalam. Setiap transaksi, pembelian, dan penyesuaian stok
yang pernah dibuat oleh seorang kasir tercatat dengan `user_id` yang merujuk ke
tabel `users`. Menghapus pengguna secara fisik akan memutus jejak audit tersebut
dan mengakibatkan data historis kehilangan konteks tentang siapa yang melakukan
operasi — sebuah kondisi yang merusak nilai laporan operasional bagi pemilik toko.
Dengan menonaktifkan pengguna alih-alih menghapusnya, seluruh riwayat aktivitas
tetap dapat ditelusuri, sementara pengguna yang dinonaktifkan tidak lagi dapat masuk
ke sistem karena validasi login memeriksa `status='aktif'` sebelum menghasilkan
*session* baru.

**Tabel B.7. Ringkasan Keputusan Desain Manajemen User**

| Kode      | Operasi         | Endpoint                                    | Metode | Kode HTTP | Keputusan Desain Kritis                                             |
| --------- | --------------- | ------------------------------------------- | ------ | --------- | ------------------------------------------------------------------- |
| AD/SD-15b | Tambah User     | `POST /api/owner/user`                      | POST   | 201       | `bcrypt` hash; response tidak sertakan `password_hash`             |
| AD/SD-15c | Edit User       | `PUT /api/owner/user/{id}`                  | PUT    | 200       | Hanya ubah `nama` dan `role_id`, tidak menyentuh `password_hash`   |
| AD/SD-15d | Reset Password  | `PATCH /api/owner/user/{id}/reset-password` | PATCH  | 200       | Endpoint terpisah; PATCH karena pembaruan parsial satu kolom       |
| AD/SD-15e | Nonaktifkan     | `PATCH /api/owner/user/{id}/nonaktifkan`    | PATCH  | 200       | UPDATE `users` dulu lalu revoke `sessions`; tidak ada *hard delete* |

---

### B.3.5 Pola Desain yang Konsisten Lintas Modul CRUD

Analisis terhadap keempat modul manajemen CRUD mengungkapkan tiga pola desain
yang diterapkan secara konsisten di seluruh sistem dan merupakan keputusan arsitektur
yang disengaja, bukan sekadar konvensi.

Pola pertama adalah ***fail-fast validation*** pada lapisan `FormRequest`. Seluruh
operasi tambah dan edit memvalidasi input di Backend sebelum menyentuh basis data —
apabila validasi gagal, HTTP 422 dikembalikan segera dengan detail galat per kolom
tanpa eksekusi kueri apapun. Hal ini mengurangi beban basis data pada kasus input
tidak valid dan memberikan umpan balik yang actionable kepada pengguna tanpa
mengharuskan halaman dimuat ulang.

Pola kedua adalah ***conditional delete*** berbasis riwayat penggunaan. Seluruh
operasi hapus pada entitas yang dapat terlibat dalam relasi historis — produk,
pelanggan, dan *supplier* — menjalankan `COUNT(*)` pada tabel relasi sebelum
memutuskan jenis penghapusan. *Soft delete* (`UPDATE status='nonaktif'`) dipilih
apabila entitas memiliki riwayat, *hard delete* dipilih apabila belum. Konsistensi
pola ini memastikan bahwa laporan historis tidak pernah kehilangan konteks akibat
penghapusan data master, sebagaimana diuraikan pada Sub-bab B.2.5.

Pola ketiga adalah ***urutan operasi yang deterministik*** pada operasi yang melibatkan
lebih dari satu tabel. Tambah produk selalu INSERT ke `produk` sebelum `produk_satuan`;
hapus produk selalu DELETE `produk_satuan` sebelum `produk`; hapus *supplier* selalu
DELETE `produk_supplier` sebelum `suppliers`; nonaktifkan pengguna selalu UPDATE
`users` sebelum UPDATE `sessions`. Urutan deterministik ini bukan konvensi arbitrer
— masing-masing memiliki justifikasi teknis berupa *foreign key constraint* atau
pertimbangan keamanan sesi yang apabila dilanggar akan menghasilkan galat basis data
atau celah keamanan.

---

_Dokumen ini merupakan bagian dari:_
_Skripsi: "Perancangan Sistem Point of Sales Toko Grosir dengan Antarmuka Real-Time_
_Berbasis Arsitektur Event Streaming"_
_Terakhir diperbarui: Mei 2026_
