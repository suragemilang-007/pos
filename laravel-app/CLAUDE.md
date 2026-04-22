# CLAUDE.md — Laravel Backend (pos_laravel)

## Peran Service Ini
Laravel bertugas sebagai:
1. **API Server** — menerima HTTP request dari browser, validasi, dan routing ke Service
2. **UI Server** — menyajikan halaman Blade + Tailwind CSS
3. **Kafka Trigger** — mengirim event ke Node.js Producer via HTTP POST

Laravel **TIDAK** langsung publish ke Kafka dan **TIDAK** langsung update stok di DB
untuk operasi transaksi. Semua operasi stok dilakukan oleh Consumer di `kafka-service`.

---

## Struktur Folder Laravel

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php
│   │   │   ├── TransaksiController.php
│   │   │   ├── ReturPenjualanController.php
│   │   │   ├── ProdukController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── SupplierController.php
│   │   │   ├── PembelianController.php
│   │   │   ├── ReturPembelianController.php
│   │   │   └── LaporanController.php
│   │   ├── Middleware/
│   │   │   ├── AuthenticateJWT.php
│   │   │   └── CheckRole.php
│   │   └── Requests/              ← FormRequest per endpoint
│   ├── Models/                    ← Eloquent models (relasi + accessor saja)
│   ├── Services/
│   │   ├── Auth/
│   │   │   ├── UserService.php
│   │   │   └── SessionService.php
│   │   ├── Transaction/
│   │   │   ├── TransaksiService.php
│   │   │   └── ReturPenjualanService.php
│   │   ├── Inventory/
│   │   │   ├── ProdukService.php
│   │   │   ├── CustomerService.php
│   │   │   └── SupplierService.php
│   │   ├── Purchasing/
│   │   │   ├── PembelianService.php
│   │   │   └── ReturPembelianService.php
│   │   ├── Reporting/
│   │   │   └── LaporanService.php
│   │   └── Kafka/
│   │       └── KafkaProducerService.php   ← HTTP POST ke kafka-service:3000
├── database/
│   ├── migrations/
│   └── seeders/
└── resources/
    └── views/                     ← Blade templates
```

---

## Aturan Controller (TIPIS — Hanya Orchestrate)

Controller TIDAK boleh berisi logika bisnis. Pola yang BENAR:

```php
// ✅ BENAR — Controller hanya: validate → service → response
public function store(TransaksiRequest $request): JsonResponse
{
    // 1. Validasi sudah ditangani FormRequest (otomatis)
    // 2. Serahkan ke Service Layer
    $result = $this->transaksiService->create(
        $request->validated()['items'],
        $request->validated()['customer_id'] ?? null,
        auth()->id()
    );

    // 3. Operasi async: return 202 Accepted, BUKAN 200 OK
    return response()->json([
        'id_transaksi' => $result->id,
        'no_transaksi' => $result->no_transaksi,
        'status'       => 'PENDING',
        'message'      => 'Transaksi sedang diproses'
    ], 202);
}

// ❌ SALAH — logika bisnis di Controller
public function store(Request $request): JsonResponse
{
    $total = 0;
    foreach ($request->items as $item) {
        $produk = Produk::find($item['produk_id']);
        $total += $produk->harga_jual * $item['qty'];
    }
    Transaksi::create(['total' => $total, ...]);  // DILARANG untuk transaksi
}
```

---

## Aturan Service Layer

```php
// KafkaProducerService — mengirim event ke Node.js via HTTP
class KafkaProducerService
{
    public function publishTransactionCreated(Transaksi $transaksi): bool
    {
        // Gunakan env variable, BUKAN hardcode URL
        $url = config('services.kafka.producer_url') . '/publish';

        $response = Http::post($url, [
            'eventType'    => 'transaction.created',
            'transaksiId'  => $transaksi->id,
            'items'        => $transaksi->details->toArray(),
            'userId'       => $transaksi->user_id,
            'timestamp'    => now()->toIso8601String(),
        ]);

        return $response->successful();
    }
}
```

---

## Environment Variables dalam Docker

**DILARANG** hardcode nilai berikut dalam kode PHP. Gunakan `config()` atau `env()`.

| Variable | Nilai dalam Docker | Keterangan |
|---|---|---|
| `DB_HOST` | `mysql` | Nama service Docker, **BUKAN localhost** |
| `DB_PORT` | `3306` | |
| `DB_DATABASE` | `pos_grosir` | |
| `KAFKA_PRODUCER_URL` | `http://kafka-service:3000` | HTTP ke Node.js |

### Cara akses di kode:
```php
// config/services.php
'kafka' => [
    'producer_url' => env('KAFKA_PRODUCER_URL', 'http://kafka-service:3000'),
],

// Penggunaan
$url = config('services.kafka.producer_url');
```

---

## Route Groups & Middleware

```php
// routes/api.php

// Publik — tidak perlu autentikasi
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Kasir & Owner
Route::middleware(['auth.jwt', 'role:kasir,owner'])->prefix('kasir')->group(function () {
    Route::post('/transaksi', [TransaksiController::class, 'store']);
    Route::get('/transaksi/{id}/struk', [TransaksiController::class, 'struk']);
    Route::post('/retur-penjualan', [ReturPenjualanController::class, 'store']);
    Route::get('/produk/{kode}/stok', [ProdukController::class, 'cekStok']);
    Route::apiResource('/customer', CustomerController::class);
    Route::get('/customer/{id}/riwayat', [CustomerController::class, 'riwayat']);
    Route::get('/stok', [ProdukController::class, 'stokRealTime']);
});

// Admin Gudang & Owner
Route::middleware(['auth.jwt', 'role:admin_gudang,owner'])->prefix('gudang')->group(function () {
    Route::apiResource('/produk', ProdukController::class);
    Route::post('/pembelian', [PembelianController::class, 'store']);
    Route::post('/retur-pembelian', [ReturPembelianController::class, 'store']);
    Route::apiResource('/supplier', SupplierController::class);
});

// Owner saja
Route::middleware(['auth.jwt', 'role:owner'])->prefix('owner')->group(function () {
    Route::get('/laporan/{jenis}', [LaporanController::class, 'generate']);
    Route::get('/laporan/{jenis}/export', [LaporanController::class, 'export']);
    Route::apiResource('/user', UserController::class);
    Route::patch('/user/{id}/nonaktifkan', [UserController::class, 'nonaktifkan']);
    Route::patch('/user/{id}/reset-password', [UserController::class, 'resetPassword']);
});
```

---

## Response Format Standar

```json
// ✅ Sukses sinkron (200)
{ "data": { ... }, "message": "Berhasil" }

// ✅ Sukses async — operasi Kafka (202)
{ "id_transaksi": 123, "status": "PENDING", "message": "Sedang diproses" }

// ✅ Dibuat (201)
{ "data": { ... }, "message": "Data berhasil ditambahkan" }

// ❌ Error validasi (422)
{ "message": "Data tidak valid", "errors": { "qty": ["Qty harus lebih dari 0"] } }

// ❌ Tidak terautentikasi (401)
{ "message": "Token tidak valid atau sudah kedaluwarsa" }

// ❌ Tidak diizinkan (403)
{ "message": "Anda tidak memiliki akses ke fitur ini" }

// ❌ Tidak ditemukan (404)
{ "message": "Data tidak ditemukan" }
```

---

## Perintah Artisan via Docker

```bash
# Jalankan migration
docker exec pos_laravel php artisan migrate

# Rollback migration terakhir
docker exec pos_laravel php artisan migrate:rollback

# Jalankan seeder
docker exec pos_laravel php artisan db:seed

# Buat file baru
docker exec pos_laravel php artisan make:controller NamaController
docker exec pos_laravel php artisan make:model NamaModel -m
docker exec pos_laravel php artisan make:service NamaService    # custom
docker exec pos_laravel php artisan make:request NamaRequest

# Cache (jalankan setelah ubah config di production)
docker exec pos_laravel php artisan config:cache
docker exec pos_laravel php artisan route:cache

# Masuk ke shell container
docker exec -it pos_laravel sh
```

---

## Soft Delete Pattern (WAJIB)

```php
// ✅ BENAR — cek dulu sebelum hapus
public function destroy(int $id): JsonResponse
{
    $produk = Produk::findOrFail($id);

    // Cek apakah produk pernah dipakai dalam transaksi
    $jumlahTransaksi = DetailTransaksi::where('produk_id', $id)->count();

    if ($jumlahTransaksi > 0) {
        // Soft delete: ubah status saja, data tetap ada
        $produk->update(['status' => 'nonaktif']);
        return response()->json(['message' => 'Produk dinonaktifkan']);
    }

    // Hard delete hanya jika belum pernah dipakai
    $produk->delete();
    return response()->json(['message' => 'Produk dihapus']);
}

// ❌ SALAH — langsung hapus tanpa cek
public function destroy(int $id): JsonResponse
{
    Produk::destroy($id);
    return response()->json(['message' => 'Dihapus']);
}
```
