# BAB IV — HASIL DAN PEMBAHASAN

## A. Analisis Kebutuhan Sistem

> **Petunjuk Penggunaan Dokumen Ini**
> Teks dalam tanda kurung siku `[X]` adalah placeholder yang harus diganti dengan
> data aktual dari observasi dan wawancara. Seluruh teks di luar tanda kurung siku
> sudah mengikuti kaidah penulisan ilmiah sesuai `panduan_bab4_gaya_bahasa.json`.

---

## A.1 Metode Pengumpulan Data

Pengumpulan data dilaksanakan melalui dua metode yang saling melengkapi: observasi
langsung terhadap proses operasional toko dan wawancara terstruktur dengan tiga
narasumber yang mewakili seluruh peran pengguna sistem. Observasi dilakukan selama
[X] hari kerja pada [bulan] [tahun] untuk memperoleh gambaran nyata tentang alur
transaksi, pengelolaan stok, dan hambatan operasional yang terjadi di lapangan.
Wawancara dilaksanakan secara terpisah kepada pemilik toko, kasir, dan admin gudang
menggunakan panduan pertanyaan terstruktur yang telah disiapkan sebelumnya, dengan
durasi masing-masing sesi berkisar antara [X] hingga [Y] menit. Transkrip lengkap
hasil wawancara disertakan pada Lampiran [nomor].

---

## A.2 Hasil Observasi

Observasi langsung dilaksanakan di Toko Melati, [alamat], selama [X] hari kerja
pada [bulan, tahun]. Pengamatan difokuskan pada tiga area utama: proses transaksi
penjualan di meja kasir, pengelolaan stok di area gudang, dan kondisi infrastruktur
teknologi yang tersedia. Seluruh temuan observasi dirangkum pada Tabel A.1 sebelum
diuraikan secara naratif pada sub-bagian berikutnya.

**Tabel A.1. Ringkasan Hasil Observasi Toko Melati**

| Kode | Aspek yang Diamati              | Kondisi Saat Ini                                                             | Dampak Operasional                               | Kebutuhan yang Ditimbulkan         |
| ---- | ------------------------------- | ---------------------------------------------------------------------------- | ------------------------------------------------ | ---------------------------------- |
| O-01 | Proses transaksi penjualan      | Manual menggunakan buku nota dan kalkulator                                  | Antrean panjang pada jam sibuk                   | KF-01 s.d. KF-07                   |
| O-02 | Pengecekan stok saat transaksi  | Kasir harus menuju gudang secara fisik                                       | Meja kasir ditinggalkan, pelayanan terhenti      | KF-02, KF-03, KNF-02               |
| O-03 | Perhitungan total dan kembalian | Manual dengan kalkulator                                                     | Rentan kesalahan saat kondisi ramai              | KF-05, KNF-01                      |
| O-04 | Pencatatan stok                 | Tidak ada pencatatan stok menyeluruh                                         | Posisi stok terkini tidak diketahui              | KF-05, KF-15, KF-16                |
| O-05 | Pencatatan barang rusak         | Tidak ada formulir atau catatan khusus                                       | Kerugian tidak terdokumentasi                    | KF-32 s.d. KF-36                   |
| O-06 | Retur ke supplier/distributor   | Tidak ada pencatatan retur yang sistematis                                   | Retur terlupakan saat sales berkunjung           | KF-10, KF-23                       |
| O-07 | Pendataan pelanggan             | Tidak ada pendataan pelanggan                                                | Tidak ada riwayat pembelian pelanggan            | KF-11, KF-12, KF-13                |
| O-08 | Infrastruktur digital           | Belum ada sistem digital terintegrasi                                        | Data tidak tahan gangguan                        | KNF-03, KNF-13                     |
| O-09 | Variasi satuan produk           | Satu produk dijual dalam beberapa satuan tanpa standar harga yang konsisten  | Harga satuan besar tidak konsisten antar kasir   | KF-03, KF-04, KNF-04               |
| O-10 | Proses pembelian ke supplier    | Pemesanan lisan, nota disimpan di laci, sebagian kredit tanpa dokumen hutang | Hutang tidak terpantau, nota mudah hilang        | KF-20 s.d. KF-24, KF-25 s.d. KF-31 |
| O-11 | Proses pembuatan laporan        | Rekap nota manual, bulanan, membutuhkan waktu [X] jam                        | Laporan tidak tersedia secara instan             | KF-26 s.d. KF-30                   |
| O-12 | Perangkat keras tersedia        | [X] unit komputer, WiFi tersedia, tanpa printer struk dan barcode scanner    | Semua pencarian dan pencatatan sepenuhnya manual | KA-01, KO-01, Spek Hardware        |
| O-13 | Volume operasional              | Rata-rata [X] transaksi/hari, ±[X] produk aktif, [X] kasir per shift         | Beban puncak tidak tertangani sistem manual      | KNF-01, KNF-02, KF-02              |

---

### A.2.1 Proses Transaksi Penjualan

Observasi terhadap proses transaksi penjualan menunjukkan bahwa seluruh pencatatan
dilakukan secara manual menggunakan buku nota dan kalkulator, tanpa dukungan sistem
digital apapun. Pada jam sibuk, kondisi ini mengakibatkan antrean pelanggan yang
memanjang karena setiap transaksi membutuhkan waktu lebih lama dari yang seharusnya
— proses pencatatan nama barang, perhitungan harga, dan penghitungan kembalian
dilakukan satu per satu secara berurutan tanpa otomasi. Hambatan yang lebih kritis
ditemukan pada tahap pengecekan ketersediaan stok: ketika pelanggan menanyakan
ketersediaan barang tertentu, kasir harus meninggalkan meja kasir dan berjalan menuju
gudang untuk memastikan stok secara fisik, sehingga pelayanan kepada pelanggan lain
terhenti selama proses tersebut berlangsung. Kondisi ini mengindikasikan bahwa
ketiadaan informasi stok _real-time_ di titik transaksi merupakan hambatan operasional
paling signifikan yang dihadapi toko, yang secara langsung mendasari penetapan KF-02
dan KF-03 — sistem harus mampu menampilkan ketersediaan stok per satuan tanpa
mengharuskan kasir meninggalkan antarmuka transaksi.

### A.2.2 Keakuratan Perhitungan Transaksi

Perhitungan total belanja dan uang kembalian yang dilakukan secara manual menggunakan
kalkulator menimbulkan risiko kesalahan yang meningkat secara proporsional dengan
tekanan waktu. Hasil observasi menunjukkan bahwa pada kondisi antrean panjang, kasir
kerap terburu-buru dalam melakukan perhitungan — sebuah kondisi yang berpotensi
menghasilkan selisih antara uang yang diterima dan kembalian yang diberikan. Kesalahan
semacam ini tidak hanya merugikan secara finansial, tetapi juga berpotensi menurunkan
kepercayaan pelanggan apabila terjadi berulang kali. Temuan ini menjadi justifikasi
penetapan KF-05 yang mewajibkan sistem menghitung total harga dan kembalian secara
otomatis, serta KNF-01 yang menetapkan bahwa respons kalkulasi harus tersedia dalam
waktu kurang dari 2 detik sehingga kecepatan pelayanan tidak bergantung pada
kemampuan hitung manual kasir.

### A.2.3 Variasi Satuan Produk

Observasi terhadap proses penjualan mengungkapkan bahwa sejumlah produk tersedia
dalam lebih dari satu satuan jual dengan harga yang berbeda per satuan — misalnya,
produk mie instan yang dijual satuan pcs, renteng isi 10, maupun karton isi 40.
Namun, penetapan harga untuk satuan besar tidak memiliki standar yang konsisten:
sebagian kasir menggunakan hafalan, sebagian lainnya menghitung ulang menggunakan
kalkulator, dan sebagian bertanya langsung kepada pemilik toko apabila tidak yakin.

**Tabel A.2. Contoh Produk dengan Variasi Satuan yang Ditemukan saat Observasi**

| No  | Nama Produk     | Satuan Tersedia        | Isi per Satuan | Cara Kasir Menentukan Harga |
| --- | --------------- | ---------------------- | -------------- | --------------------------- |
| 1   | [Nama Produk 1] | Pcs / Renteng / Karton | 1 / [X] / [X]  | Hafalan / kalkulator        |
| 2   | [Nama Produk 2] | Kg / Sak               | 1 / [X]        | Timbang manual + hitung     |
| 3   | [Nama Produk 3] | Liter / Jerigen        | 1 / [X]        | Kalkulator / tanya pemilik  |
| 4   | [Nama Produk 4] | Pcs / Karton           | 1 / [X]        | Hafalan                     |

Ketidakkonsistenan ini berpotensi merugikan toko apabila harga yang diberikan kasir
lebih rendah dari seharusnya, atau merugikan pelanggan dalam situasi sebaliknya. Hal
ini disebabkan tidak adanya acuan harga per satuan yang terpusat dan dapat diakses
kasir secara instan. Kondisi ini secara langsung mendasari penetapan kebutuhan
multi-satuan pada sistem: KF-03 mewajibkan tampilan stok dan harga per satuan sebelum
transaksi dikonfirmasi, KF-04 mengharuskan perhitungan harga dilakukan otomatis
berdasarkan satuan yang dipilih, dan KNF-04 menetapkan bahwa konversi kuantitas ke
satuan dasar harus dilakukan secara konsisten di seluruh operasi stok.

### A.2.4 Pengelolaan Stok dan Barang Rusak

Observasi terhadap pengelolaan inventaris mengungkapkan ketiadaan sistem pencatatan
stok yang menyeluruh — posisi stok terkini hanya diketahui melalui penghitungan
fisik yang dilakukan secara insidental, bukan secara terjadwal. Akibatnya, toko
beroperasi tanpa visibilitas yang akurat terhadap kondisi inventarisnya sendiri:
tidak diketahui produk mana yang hampir habis, produk mana yang pergerakannya lambat,
dan berapa nilai total barang yang tersimpan di gudang pada waktu tertentu.
Lebih jauh, ditemukan bahwa barang rusak yang ditemukan di gudang tidak memiliki
mekanisme pencatatan formal — kerugian akibat kerusakan barang tidak terdokumentasi
dan tidak pernah dilaporkan kepada pemilik toko sebagai bagian dari evaluasi
operasional. Kedua kondisi ini mendasari penetapan dua kelompok kebutuhan yang
berbeda: KF-14 hingga KF-16 untuk pengelolaan dan pemantauan stok secara _real-time_,
serta KF-32 hingga KF-36 yang mendefinisikan fitur penyesuaian stok dengan lima tipe
pencatatan — barang rusak, barang hilang, barang kedaluwarsa, koreksi tambah, dan
koreksi kurang.

### A.2.5 Pengelolaan Pembelian dan Hutang ke Supplier

Observasi terhadap proses pengadaan barang menunjukkan bahwa pemesanan ke _supplier_
dilakukan secara lisan — baik saat perwakilan _supplier_ berkunjung maupun melalui
telepon — tanpa dokumen pemesanan formal yang dapat dijadikan acuan penerimaan barang.
Nota pembelian yang diterima dari _supplier_ disimpan di laci tanpa pengarsipan yang
sistematis, sehingga sulit ditelusuri apabila terjadi perselisihan jumlah atau harga.

**Tabel A.3. Hasil Observasi Proses Pembelian ke Supplier**

| Tahap                | Kondisi Saat Ini                             | Dokumen yang Digunakan          | Masalah yang Ditemukan                       |
| -------------------- | -------------------------------------------- | ------------------------------- | -------------------------------------------- |
| Pemesanan            | Lisan saat sales datang atau via telepon     | Tidak ada                       | Tidak ada catatan barang yang dipesan        |
| Penerimaan barang    | Pemeriksaan fisik kasar oleh admin/pemilik   | Nota pengiriman supplier        | Tidak dicocokkan dengan pemesanan            |
| Pencatatan pembelian | Tidak sistematis, nota disimpan di laci      | Nota beli dari supplier         | Nota mudah hilang atau rusak                 |
| Pembayaran           | Sebagian tunai, sebagian kredit [X]–[Y] hari | Tidak ada dokumen hutang formal | Hutang hanya diingat atau dicatat buku kecil |
| Pemantauan hutang    | Mengandalkan ingatan atau tagihan dari sales | Catatan informal                | Pernah terjadi keterlambatan pembayaran      |

Kondisi pembelian kredit tanpa pencatatan yang terstruktur berpotensi mengakibatkan
keterlambatan pembayaran yang dapat merusak hubungan dengan _supplier_. Temuan ini
menjadi landasan penetapan KF-20 hingga KF-24 yang mendefinisikan modul pembelian,
serta KF-25 hingga KF-31 yang secara khusus mengatur pengelolaan hutang dagang —
mencakup pencatatan uang muka, sisa hutang, tanggal jatuh tempo, dan notifikasi
peringatan mendekati jatuh tempo.

Observasi juga mengonfirmasi bahwa proses retur barang ke _supplier_ tidak memiliki
pencatatan yang sistematis. Barang yang perlu diretur kerap disisihkan namun belum
diproses, dan informasi tersebut hanya tersimpan dalam ingatan pemilik toko —
sehingga ketika perwakilan _supplier_ datang berkunjung, retur kerap terlupakan.
Kondisi ini mendasari penetapan KF-10 yang mewajibkan sistem menyediakan fitur
retur pembelian yang terdokumentasi dan dapat diakses kapan pun.

### A.2.6 Pendataan Pelanggan

Observasi terhadap interaksi dengan pelanggan menunjukkan bahwa tidak ada mekanisme
pendataan pelanggan dalam bentuk apapun — identitas pelanggan, riwayat pembelian,
maupun frekuensi kunjungan tidak pernah dicatat. Kondisi ini membatasi kemampuan
pemilik toko untuk menganalisis perilaku pembelian pelanggan setia, yang pada toko
grosir umumnya merupakan sumber pendapatan berulang yang signifikan. Tanpa data
pelanggan, toko tidak dapat membedakan antara pembeli eceran dan pembeli grosir,
sehingga tidak memungkinkan penerapan kebijakan harga yang berbeda secara sistematis
di masa mendatang. Temuan ini mendasari penetapan KF-11 hingga KF-13 yang
mendefinisikan fitur manajemen data pelanggan, pencatatan jenis pelanggan (reguler
atau grosir), dan tampilan riwayat belanja per pelanggan.

### A.2.7 Proses Pembuatan Laporan

Observasi dan konfirmasi pemilik toko mengungkapkan bahwa laporan penjualan dibuat
secara manual dengan merekap seluruh nota penjualan, sebuah proses yang membutuhkan
waktu hingga [X] jam per bulan dan hanya dapat dilakukan di akhir periode — bukan
secara instan kapan pun dibutuhkan.

**Tabel A.4. Hasil Observasi Proses Pembuatan Laporan**

| Jenis Laporan     | Cara Saat Ini                  | Frekuensi       | Waktu Dibutuhkan | Masalah Utama                           |
| ----------------- | ------------------------------ | --------------- | ---------------- | --------------------------------------- |
| Laporan penjualan | Rekap nota manual + kalkulator | Bulanan         | ± [X] jam        | Nota tidak selalu lengkap               |
| Laporan stok      | Penghitungan fisik insidental  | Tidak terjadwal | ± [X] jam        | Sering terdapat selisih                 |
| Laporan pembelian | Tidak ada laporan formal       | Tidak ada       | —                | Total pengeluaran tidak diketahui       |
| Laporan hutang    | Tidak ada; berdasarkan ingatan | Tidak ada       | —                | Posisi hutang tidak terpantau           |
| Evaluasi produk   | Tidak ada; berdasarkan intuisi | Tidak ada       | —                | Keputusan pembelian tidak berbasis data |

Ketiadaan laporan yang tersedia secara instan mengakibatkan keputusan manajerial
toko bergantung pada intuisi dan ingatan, bukan pada data yang terverifikasi. Hal
ini disebabkan oleh absennya sistem yang mampu mengagregasi data transaksi secara
otomatis tanpa rekap manual. Temuan ini menjadi justifikasi utama penetapan KF-26
hingga KF-30 yang mendefinisikan lima jenis laporan dalam sistem — penjualan, stok,
pembelian, hutang dagang, dan penyesuaian stok — yang seluruhnya dapat dihasilkan
secara instan berdasarkan rentang tanggal yang dipilih.

### A.2.8 Infrastruktur dan Volume Operasional

Observasi terhadap infrastruktur teknologi menunjukkan bahwa jaringan WiFi tersedia
dan berfungsi stabil di seluruh area toko, namun belum ada sistem penyimpanan data
digital yang terintegrasi.

**Tabel A.5. Hasil Observasi Perangkat Keras di Toko Melati**

| No  | Perangkat         | Jumlah    | Kondisi | Lokasi        | Catatan                            |
| --- | ----------------- | --------- | ------- | ------------- | ---------------------------------- |
| 1   | Komputer/PC       | [X] unit  | Baik    | Meja kasir    | Belum ada aplikasi POS             |
| 2   | Laptop            | [X] unit  | Baik    | Ruang pemilik | Untuk administrasi umum            |
| 3   | _Router_ WiFi     | 1 unit    | Stabil  | Ruang tengah  | Jangkauan seluruh area toko        |
| 4   | Printer struk     | Tidak ada | —       | —             | Nota ditulis tangan                |
| 5   | _Barcode scanner_ | Tidak ada | —       | —             | Pencarian produk sepenuhnya manual |
| 6   | UPS/_stabilizer_  | Tidak ada | —       | —             | Listrik PLN dinilai stabil         |
| 7   | Kalkulator        | [X] unit  | Baik    | Meja kasir    | Alat hitung utama saat ini         |

**Tabel A.6. Data Volume Operasional Toko Melati**

| Indikator                    | Nilai Terukur     | Keterangan                    |
| ---------------------------- | ----------------- | ----------------------------- |
| Rata-rata transaksi per hari | [X] transaksi     | Hari kerja normal             |
| Transaksi pada jam sibuk     | [X] transaksi/jam | Pukul [XX.00]–[XX.00]         |
| Jumlah produk aktif          | ± [X] produk      | Estimasi pemilik toko         |
| Jumlah kasir per shift       | [X] orang         | Shift tunggal sepanjang hari  |
| Rata-rata item per transaksi | [X] item          | Hasil pengamatan langsung     |
| Hari dengan volume tertinggi | [nama hari]       | Berdasarkan informasi pemilik |

Pengukuran volume operasional menunjukkan bahwa volume transaksi tertinggi terjadi
pada dua rentang waktu: pagi hari antara pukul [XX.00] hingga [XX.00] ketika pedagang
pasar melakukan pembelian grosir, dan sore hari antara pukul [XX.00] hingga [XX.00]
ketika pelanggan eceran berbelanja sepulang kerja. Pada rentang waktu tersebut, satu
kasir harus melayani rata-rata [X] transaksi per jam secara berurutan — sebuah beban
yang tidak dapat dipenuhi secara efisien dengan sistem manual yang saat ini digunakan.
Kondisi ini menjadi dasar penetapan KNF-01 yang menetapkan target respons sistem
tidak lebih dari 2 detik per transaksi, agar kecepatan sistem tidak menjadi hambatan
baru yang menggantikan hambatan lama.

Data jumlah produk aktif sebanyak ± [X] produk, di mana sebagian besar tersedia
dalam lebih dari satu satuan, mengindikasikan bahwa sistem harus mampu menangani
basis data produk berskala ratusan entri satuan tanpa penurunan performa pada
antarmuka pencarian. Jumlah kasir yang hanya satu orang per shift menegaskan bahwa
antarmuka kasir harus dirancang untuk meminimalkan jumlah langkah yang diperlukan
per transaksi, karena tidak ada kasir kedua yang dapat membantu menangani antrean
apabila satu transaksi memakan waktu lebih lama dari biasanya. Ketersediaan jaringan
WiFi di seluruh area toko mengonfirmasi bahwa sistem berbasis web yang diakses melalui
_browser_ merupakan pilihan yang paling sesuai dengan infrastruktur yang ada, tanpa
memerlukan instalasi perangkat lunak tambahan pada setiap terminal.

---

## A.3 Hasil Wawancara

Wawancara terstruktur dilaksanakan pada [tanggal] di Toko Melati dengan tiga
narasumber: pemilik toko, kasir, dan admin gudang. Panduan wawancara terdiri dari
[X] pertanyaan yang dikelompokkan berdasarkan peran masing-masing narasumber.
Berikut disajikan temuan utama dari setiap sesi wawancara; transkrip lengkap
disertakan pada Lampiran [nomor].

### A.3.1 Wawancara dengan Pemilik Toko

Wawancara dengan pemilik toko mengungkapkan bahwa kebutuhan utama dari sisi
manajerial adalah kemampuan memperoleh laporan penjualan tanpa harus merekap nota
secara manual — proses yang saat ini memakan waktu hingga [X] jam setiap akhir
bulan (Lampiran [nomor], pertanyaan nomor [X]). Pemilik toko juga menyatakan bahwa
dua informasi yang paling sering dibutuhkan untuk pengambilan keputusan pembelian
adalah data produk terlaris dan posisi hutang ke _supplier_, namun keduanya saat ini
tidak tersedia secara instan. Terkait pengelolaan hutang, pemilik toko mengakui
bahwa keterlambatan retur barang ke _supplier_ terjadi karena tidak adanya pencatatan
yang mengingatkan kapan dan barang apa yang perlu diretur (Lampiran [nomor],
pertanyaan nomor [X]). Temuan ini menjadi landasan ditetapkannya KF-26 hingga
KF-30 yang mendefinisikan lima jenis laporan dalam sistem, serta KF-10 dan KF-23
yang mengatur pencatatan retur pembelian dan pengelolaan hutang dagang.

### A.3.2 Wawancara dengan Kasir

Wawancara dengan kasir mengungkapkan dua hambatan utama dalam proses transaksi
harian. Hambatan pertama adalah ketiadaan cara cepat untuk mengetahui stok yang
tersedia sebelum transaksi dikonfirmasi — kasir harus menuju gudang secara fisik
untuk memastikan ketersediaan barang, khususnya untuk produk yang stoknya diperkirakan
hampir habis (Lampiran [nomor], pertanyaan nomor [X]). Hambatan kedua adalah
ketidakpastian harga untuk pembelian satuan besar seperti karton atau dus, di mana
kasir menggunakan hafalan atau bertanya kepada pemilik toko apabila tidak yakin
(Lampiran [nomor], pertanyaan nomor [X]). Kasir juga menyatakan bahwa pada kondisi
antrean panjang, terburu-buru dalam menghitung kembalian merupakan situasi yang
kerap dialami dan berpotensi menimbulkan selisih kas. Ketiga hambatan ini secara
langsung mengarahkan pada kebutuhan KF-02 (tampilkan stok per satuan sebelum
transaksi), KF-04 (harga dihitung otomatis berdasarkan satuan), dan KF-05
(perhitungan total dan kembalian otomatis).

### A.3.3 Wawancara dengan Admin Gudang

Wawancara dengan admin gudang mengonfirmasi bahwa pencatatan hutang ke _supplier_
dilakukan pada buku terpisah yang tidak selalu diperbarui tepat waktu, sehingga
pernah terjadi pembayaran yang terlambat akibat tanggal jatuh tempo terlewat
(Lampiran [nomor], pertanyaan nomor [X]). Admin gudang juga menyatakan bahwa barang
rusak dan kedaluwarsa yang ditemukan saat pengecekan gudang tidak memiliki formulir
pencatatan resmi — kerugian tersebut hanya dicatat secara informal dan tidak pernah
dilaporkan kepada pemilik toko dalam bentuk dokumen (Lampiran [nomor], pertanyaan
nomor [X]). Terkait pengelolaan stok, admin gudang mengungkapkan bahwa selisih antara
stok catatan dan stok fisik kerap ditemukan saat penghitungan insidental, namun tidak
ada mekanisme untuk melacak kapan dan mengapa selisih tersebut terjadi. Temuan ini
menjadi dasar penetapan KF-25 hingga KF-31 (hutang dagang), KF-32 hingga KF-36
(penyesuaian stok), dan kebutuhan KNF-19 yang mewajibkan pencatatan audit untuk
setiap penyesuaian stok.

---

## A.4 Keterkaitan Temuan dengan Kebutuhan Sistem

Seluruh temuan observasi dan wawancara yang telah diuraikan di atas secara kolektif
membentuk dokumen kebutuhan sistem yang menjadi dasar perancangan Sistem POS Toko
Melati. Tabel A.7 memetakan keterkaitan antara sumber data pengumpulan, temuan
utama, dan kode kebutuhan yang dihasilkan.

**Tabel A.7. Pemetaan Temuan ke Kebutuhan Sistem**

| Temuan Utama                              | Sumber               | Kode Kebutuhan           |
| ----------------------------------------- | -------------------- | ------------------------ |
| Transaksi manual, antrean panjang         | O-01, O-03, Kasir    | KF-01 s.d. KF-07, KNF-01 |
| Cek stok memerlukan perjalanan ke gudang  | O-02, Kasir          | KF-02, KF-03, KNF-02     |
| Harga satuan besar tidak konsisten        | O-09, Kasir          | KF-03, KF-04, KNF-04     |
| Tidak ada pencatatan stok                 | O-04, Admin          | KF-14 s.d. KF-16         |
| Barang rusak tidak tercatat               | O-05, Admin          | KF-32 s.d. KF-36         |
| Retur terlupakan karena tidak ada catatan | O-06, Pemilik        | KF-10, KF-23             |
| Tidak ada pendataan pelanggan             | O-07, Kasir          | KF-11 s.d. KF-13         |
| Pembelian kredit tanpa dokumen hutang     | O-10, Admin, Pemilik | KF-20 s.d. KF-31         |
| Laporan manual, membutuhkan [X] jam       | O-11, Pemilik        | KF-26 s.d. KF-30         |
| Infrastruktur WiFi tersedia               | O-12                 | KA-01, KO-01             |
| Volume [X] transaksi/hari, 1 kasir/shift  | O-13                 | KNF-01, KNF-02           |

Berdasarkan hasil observasi dan wawancara yang telah dijabarkan, dapat disimpulkan
bahwa permasalahan utama Toko Melati bersumber dari tiga akar masalah yang saling
berkaitan: ketiadaan sistem pencatatan digital yang terintegrasi, ketiadaan visibilitas
stok secara _real-time_, dan ketiadaan mekanisme pelaporan yang dapat diakses secara
instan. Ketiga akar masalah ini mendorong perancangan sistem yang tidak hanya
mengotomasi transaksi, tetapi juga menyediakan _event streaming_ (aliran kejadian
secara terus-menerus) melalui Apache Kafka untuk memastikan setiap perubahan stok
diperbarui secara asinkron dan konsisten di seluruh komponen sistem, sebagaimana
diuraikan pada Sub-bab 2.x. Dengan demikian, seluruh kebutuhan yang ditetapkan
dalam dokumen ini bukan merupakan asumsi perancang, melainkan respons langsung
terhadap kondisi operasional nyata yang ditemukan di lapangan.

---

_Dokumen ini merupakan bagian dari:_
_Skripsi: "Perancangan Sistem Point of Sales Toko Grosir dengan Antarmuka Real-Time_
_Berbasis Arsitektur Event Streaming"_
_Terakhir diperbarui: [tanggal]_
