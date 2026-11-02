# Wireframe Desain Tampilan — Sistem POS Toko Melati

Wireframe dibuat dengan **PlantUML Salt** dan diturunkan langsung dari view Blade di
`laravel-app/resources/views/`. Setiap file `.puml` mencantumkan file Blade sumbernya di baris komentar teratas.

`_layout.iuml` berisi komponen bersama (sidebar per role + header dengan indikator WebSocket),
di-include oleh semua halaman yang memakai `layouts/app.blade.php`.

## Cara render

- **VS Code** (ekstensi PlantUML): buka file → `Alt+D` untuk preview → klik kanan *Export Current Diagram*.
- **Semua sekaligus**: Command Palette → *PlantUML: Export Workspace Diagrams*.
- **CLI**: `java -jar plantuml.jar -tpng docs/plantuml/wireframe/WF-*.puml`

## Daftar Wireframe

### Umum
| Kode | Halaman |
|---|---|
| WF-01 | Login |

### Kasir
| Kode | Halaman |
|---|---|
| WF-02 | Transaksi Penjualan (layout kasir full-screen) |
| WF-02b | Modal Pembayaran QRIS / Transfer |
| WF-02c | Modal Transaksi Berhasil / Gagal |
| WF-05 | Dashboard Kasir |
| WF-05b | Modal Detail Transaksi |
| WF-06 | Pantau Stok Real-Time |
| WF-07 | Pengajuan Void Transaksi |
| WF-07b | Modal Ajukan Void ke Pemilik |

### Admin Gudang
| Kode | Halaman |
|---|---|
| WF-03 | Buat Purchase Order |
| WF-11 | Dashboard Gudang |
| WF-12 | Data Produk |
| WF-12b | Modal Riwayat Penjualan Produk & Hapus Produk |
| WF-13 | Tambah / Edit Produk (multi-satuan) |
| WF-14 | Manajemen Kategori Produk |
| WF-14b | Modal Tambah/Edit & Hapus Kategori |
| WF-15 | Data Supplier |
| WF-15b | Modal Tambah/Edit & Hapus Supplier |
| WF-16 | Daftar Pembelian (PO) |
| WF-17 | Detail Purchase Order |
| WF-17b | Modal Retur Pembelian |
| WF-18 | Hutang Dagang |
| WF-18b | Modal Catat Pembayaran Hutang |
| WF-19 | Penyesuaian Stok |
| WF-19b | Modal Penyesuaian Stok (2 langkah) |

### Owner
| Kode | Halaman |
|---|---|
| WF-04 | Laporan (tab Penjualan) |
| WF-08 | Retur Penjualan |
| WF-09 | Data Customer |
| WF-09b | Modal Tambah/Edit & Nonaktifkan Customer |
| WF-10 | Riwayat Belanja Customer |
| WF-21 | Dashboard Owner |
| WF-22 | Daftar Transaksi |
| WF-22b | Modal Detail Transaksi |
| WF-23 | Persetujuan Void |
| WF-23b | Modal Tinjau Pengajuan Void |
| WF-24 | Kelola User |
| WF-24b | Modal Tambah, Edit, Reset Password & Nonaktifkan User |

> Owner juga dapat mengakses semua halaman Kasir dan Admin Gudang (sidebar menampilkan semua grup menu).
