# Sequence Diagram — Manajemen Kategori Produk (SD-16)

> Diturunkan dari implementasi aktual:
> - View: `resources/views/gudang/kategori/index.blade.php`
> - Route: `routes/api.php` (group `auth.jwt` + `role:admin_gudang,owner`, prefix `gudang`)
> - Controller: `KategoriProdukController`
> - FormRequest: `StoreKategoriRequest`, `UpdateKategoriRequest`
> - Service: `Inventory\KategoriProdukService`
> - Model: `KategoriProduk` → tabel `kategori_produk`
>
> Kategori adalah **data referensi** → operasi **sinkron**, **tidak lewat Kafka**.

---

## SD-16a: Tambah Kategori

```mermaid
sequenceDiagram
    autonumber
    actor U as Admin Gudang / Owner
    participant V as View Kategori (Blade + Axios)
    participant M as Middleware (auth.jwt, role)
    participant C as KategoriProdukController
    participant R as StoreKategoriRequest
    participant S as KategoriProdukService
    participant K as Model KategoriProduk
    participant DB as MySQL (kategori_produk)

    U->>V: Klik "Tambah Kategori"
    V->>V: bukaModalTambah() — form kosong
    U->>V: Isi nama_kategori, deskripsi, klik "Simpan"
    V->>V: simpanKategori() — cek nama tidak kosong

    alt Nama kosong
        V-->>U: Tampilkan error inline "Nama kategori wajib diisi"
    else Nama terisi
        V->>M: POST /api/gudang/kategori {nama_kategori, deskripsi}
        M->>M: Validasi token JWT & role
        alt Token tidak valid / role tidak diizinkan
            M-->>V: 401 / 403
            V-->>U: Toast "Gagal menyimpan"
        else Lolos
            M->>C: store(request)
            C->>R: rules() — required, max:100, unique
            R->>DB: SELECT nama_kategori (cek unique)
            DB-->>R: hasil
            alt Validasi gagal (duplikat / > 100 karakter)
                R-->>V: 422 {message, errors.nama_kategori}
                V-->>U: Error inline di bawah field nama
            else Valid
                C->>S: create(validated)
                S->>K: KategoriProduk::create(...)
                K->>DB: INSERT INTO kategori_produk
                DB-->>K: id baru
                K-->>S: objek KategoriProduk
                S-->>C: KategoriProduk
                C-->>V: 201 {data, message: "Kategori berhasil ditambahkan"}
                V->>V: tutupModalForm()
                V-->>U: Toast sukses
                V->>M: GET /api/gudang/kategori (loadKategori)
                M->>C: indexWithCount()
                C->>S: getAll()
                S->>DB: SELECT ... withCount(produk as jumlah_produk) ORDER BY nama_kategori
                DB-->>S: daftar kategori
                S-->>C: Collection
                C-->>V: 200 {data}
                V-->>U: Tabel kategori diperbarui
            end
        end
    end
```

---

## SD-16b: Edit Kategori

```mermaid
sequenceDiagram
    autonumber
    actor U as Admin Gudang / Owner
    participant V as View Kategori (Blade + Axios)
    participant M as Middleware (auth.jwt, role)
    participant C as KategoriProdukController
    participant R as UpdateKategoriRequest
    participant S as KategoriProdukService
    participant K as Model KategoriProduk
    participant DB as MySQL (kategori_produk)

    U->>V: Klik tombol "Edit" pada baris kategori
    V->>V: bukaModalEdit(id) — isi form dari cache kategoriList
    Note over V: Tidak ada request tambahan,<br/>data diambil dari hasil GET sebelumnya
    U->>V: Ubah nama_kategori / deskripsi, klik "Perbarui"
    V->>V: simpanKategori() — cek nama tidak kosong

    alt Nama kosong
        V-->>U: Tampilkan error inline "Nama kategori wajib diisi"
    else Nama terisi
        V->>M: PUT /api/gudang/kategori/{id} {nama_kategori, deskripsi}
        M->>M: Validasi token JWT & role
        alt Token tidak valid / role tidak diizinkan
            M-->>V: 401 / 403
            V-->>U: Toast "Gagal menyimpan"
        else Lolos
            M->>C: update(request, id)
            C->>R: rules() — required, max:100, unique ignore(id)
            R->>DB: SELECT nama_kategori WHERE id != {id}
            DB-->>R: hasil
            alt Validasi gagal (nama dipakai kategori lain)
                R-->>V: 422 {message, errors.nama_kategori}
                V-->>U: Error inline di bawah field nama
            else Valid
                C->>S: update(id, validated)
                S->>K: KategoriProduk::findOrFail(id)
                K->>DB: SELECT * FROM kategori_produk WHERE id = ?
                DB-->>K: row / kosong
                alt Kategori tidak ditemukan
                    K-->>V: 404
                    V-->>U: Toast "Gagal menyimpan"
                else Ditemukan
                    S->>K: update(nama_kategori, deskripsi)
                    K->>DB: UPDATE kategori_produk SET ... WHERE id = ?
                    DB-->>K: OK
                    K-->>S: true
                    S-->>C: true
                    C->>K: KategoriProduk::find(id)
                    K->>DB: SELECT * WHERE id = ?
                    DB-->>K: row terbaru
                    K-->>C: KategoriProduk
                    C-->>V: 200 {data, message: "Kategori berhasil diperbarui"}
                    V->>V: tutupModalForm()
                    V-->>U: Toast sukses
                    V->>M: GET /api/gudang/kategori (loadKategori)
                    M->>C: indexWithCount()
                    C->>S: getAll()
                    S->>DB: SELECT ... withCount(produk as jumlah_produk)
                    DB-->>S: daftar kategori
                    S-->>C: Collection
                    C-->>V: 200 {data}
                    V-->>U: Tabel kategori diperbarui
                end
            end
        end
    end
```

---

## SD-16c: Hapus Kategori

```mermaid
sequenceDiagram
    autonumber
    actor U as Admin Gudang / Owner
    participant V as View Kategori (Blade + Axios)
    participant M as Middleware (auth.jwt, role)
    participant C as KategoriProdukController
    participant S as KategoriProdukService
    participant K as Model KategoriProduk
    participant DB as MySQL (kategori_produk, produk)

    U->>V: Klik tombol "Hapus" pada baris kategori
    V->>V: bukaModalHapus(id) — cek jumlah_produk dari cache

    alt jumlah_produk > 0 (cek sisi client)
        V-->>U: Modal info "masih digunakan oleh N produk"<br/>(tombol hapus disembunyikan)
    else jumlah_produk = 0
        V-->>U: Modal konfirmasi "akan dihapus permanen. Lanjutkan?"
        U->>V: Klik "Ya, Hapus"
        V->>M: DELETE /api/gudang/kategori/{id}
        M->>M: Validasi token JWT & role
        alt Token tidak valid / role tidak diizinkan
            M-->>V: 401 / 403
            V-->>U: Toast "Gagal menghapus"
        else Lolos
            M->>C: destroy(id)
            C->>S: delete(id)
            S->>K: withCount('produk')->findOrFail(id)
            K->>DB: SELECT kategori + COUNT(*) FROM produk WHERE kategori_id = ?
            DB-->>K: row + produk_count
            alt Kategori tidak ditemukan
                K-->>V: 404
                V-->>U: Toast "Gagal menghapus"
            else produk_count > 0 (cek ulang sisi server)
                S-->>C: throw RuntimeException
                C-->>V: 422 {message: "Kategori tidak dapat dihapus karena masih digunakan oleh N produk"}
                V->>V: tutupModalHapus()
                V-->>U: Toast "Gagal menghapus"
                Note over V: Terjadi jika ada produk baru masuk<br/>ke kategori setelah modal dibuka
            else produk_count = 0
                S->>K: delete()
                K->>DB: DELETE FROM kategori_produk WHERE id = ?
                DB-->>K: OK
                K-->>S: true
                S-->>C: true
                C-->>V: 200 {message: "Kategori berhasil dihapus"}
                V->>V: tutupModalHapus()
                V-->>U: Toast sukses
            end
            V->>M: GET /api/gudang/kategori (loadKategori)
            M->>C: indexWithCount()
            C->>S: getAll()
            S->>DB: SELECT ... withCount(produk as jumlah_produk)
            DB-->>S: daftar kategori
            S-->>C: Collection
            C-->>V: 200 {data}
            V-->>U: Tabel kategori diperbarui
        end
    end
```
