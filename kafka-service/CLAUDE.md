# CLAUDE.md — Kafka Service (pos_kafka_service)

## Peran Service Ini
Node.js service ini adalah **jantung dari arsitektur event-driven**. Bertugas sebagai:
1. **HTTP Producer Endpoint** (port 3000) — menerima event dari Laravel, publish ke Kafka
2. **Kafka Consumer** — mengonsumsi event, memproses logika stok, tulis ke MySQL
3. **WebSocket Server** (port 3001) — push notifikasi real-time ke browser

---

## Struktur Folder

```
kafka-service/
├── index.js                     ← Entry point: start semua service
├── package.json
├── config/
│   ├── kafka.js                 ← KafkaJS configuration
│   └── database.js              ← MySQL connection pool
├── producers/
│   └── httpServer.js            ← Express server port 3000
├── consumers/
│   ├── transactionConsumer.js   ← Konsumsi: transaction-events
│   ├── purchaseConsumer.js      ← Konsumsi: inventory-updates (purchase)
│   └── returnConsumer.js        ← Konsumsi: inventory-updates (retur)
├── services/
│   ├── stockService.js          ← Logic update stok di MySQL
│   ├── transactionService.js    ← Logic update status transaksi
│   └── wsService.js             ← WebSocket push ke browser
└── websocket/
    └── wsServer.js              ← WebSocket server port 3001
```

---

## Hostname Docker (KRITIS)

Service ini berjalan sebagai container `pos_kafka_service` dalam Docker network `pos_network`.

```javascript
// ✅ BENAR — gunakan env variable yang sudah diset di docker-compose
const kafka = new Kafka({
    clientId: process.env.KAFKA_CLIENT_ID,
    brokers: [process.env.KAFKA_BROKERS],   // 'kafka:9092'
});

const pool = mysql.createPool({
    host:     process.env.DB_HOST,           // 'mysql'
    port:     process.env.DB_PORT,           // 3306
    database: process.env.DB_DATABASE,
    user:     process.env.DB_USER,
    password: process.env.DB_PASSWORD,
});

// ❌ SALAH — localhost tidak bisa diakses antar container
const kafka = new Kafka({ brokers: ['localhost:9092'] });
const pool  = mysql.createPool({ host: 'localhost' });
```

---

## Kafka Topics & Events

| Topic | Event Type | Siapa Publish | Siapa Consume |
|---|---|---|---|
| `transaction-events` | `transaction.created` | Laravel (via HTTP ke sini) | transactionConsumer.js |
| `inventory-updates` | `purchase.created` | Laravel (via HTTP ke sini) | purchaseConsumer.js |
| `inventory-updates` | `sale.return.requested` | Laravel (via HTTP ke sini) | returnConsumer.js |
| `inventory-updates` | `purchase.return.requested` | Laravel (via HTTP ke sini) | returnConsumer.js |

---

## Payload Standar Setiap Event

```javascript
// transaction.created
{
    eventType:   'transaction.created',
    transaksiId: 123,
    items: [
        { produkId: 1, qty: 5, hargaSatuan: 10000, diskonItem: 0 }
    ],
    customerId:  null,          // nullable
    userId:      2,             // ID kasir
    total:       50000,
    metodeBayar: 'tunai',
    timestamp:   '2025-01-01T10:00:00.000Z'
}

// purchase.created
{
    eventType:    'purchase.created',
    pembelianId:  45,
    supplierId:   3,
    items: [
        { produkId: 1, qty: 100, hargaBeliSatuan: 8000 }
    ],
    userId:       5,
    timestamp:    '2025-01-01T10:00:00.000Z'
}

// sale.return.requested
{
    eventType:    'sale.return.requested',
    returId:      10,
    transaksiId:  123,
    items: [
        { produkId: 1, qtyRetur: 2, hargaSatuan: 10000 }
    ],
    userId:       2,
    timestamp:    '2025-01-01T10:00:00.000Z'
}

// purchase.return.requested
{
    eventType:    'purchase.return.requested',
    returId:      5,
    pembelianId:  45,
    items: [
        { produkId: 1, qtyRetur: 10, hargaSatuan: 8000 }
    ],
    userId:       5,
    timestamp:    '2025-01-01T10:00:00.000Z'
}
```

---

## Urutan Operasi Consumer (WAJIB DIIKUTI)

### transactionConsumer.js:
```
1. Ambil data transaksi dari payload event
2. Untuk setiap item, SELECT qty_stok FROM produk WHERE id = ?
3. Jika ADA item yang qty_stok < qty transaksi:
   → UPDATE transaksi SET status = 'FAILED'
   → wsService.push(userId, {event: 'transaction.failed', transaksiId, ...})
   → STOP (jangan proses item lain)
4. Jika SEMUA item stok mencukupi:
   → Mulai transaksi database (BEGIN TRANSACTION)
   → UPDATE produk SET qty_stok = qty_stok - ? per item
   → UPDATE transaksi SET status = 'COMPLETED'
   → COMMIT
   → wsService.push(userId, {event: 'transaction.completed', transaksiId, dataStruk})
   → wsService.pushAll('stok-global', {event: 'stock.updated', updates: [...]})
```

### purchaseConsumer.js:
```
1. Mulai transaksi database
2. Untuk setiap item:
   → UPDATE produk SET qty_stok = qty_stok + ?, harga_beli = ? WHERE id = ?
3. UPDATE pembelian SET status = 'COMPLETED'
4. COMMIT
5. wsService.push(userId, {event: 'purchase.completed', pembelianId, ...})
6. wsService.pushAll('stok-global', {event: 'stock.updated', updates: [...]})
```

### returnConsumer.js (sale return):
```
1. UPDATE produk SET qty_stok = qty_stok + ? per item (stok BERTAMBAH)
2. UPDATE retur_penjualan SET status = 'COMPLETED', total_refund = ?
3. wsService.push(userId, {event: 'return.sale.completed', returId, nilaiRefund})
4. wsService.pushAll('stok-global', {event: 'stock.updated', updates: [...]})
```

### returnConsumer.js (purchase return):
```
1. UPDATE produk SET qty_stok = qty_stok - ? per item (stok BERKURANG)
2. UPDATE retur_pembelian SET status = 'COMPLETED', total_kredit = ?
3. wsService.push(userId, {event: 'return.purchase.completed', returId, nilaiKredit})
4. wsService.pushAll('stok-global', {event: 'stock.updated', updates: [...]})
```

---

## WebSocket Events yang Di-push ke Browser

```javascript
// Channel: 'user-{userId}' — notifikasi personal
wsService.push(`user-${userId}`, {
    event:       'transaction.completed',
    transaksiId: 123,
    status:      'COMPLETED',
    dataStruk:   { noTransaksi: 'TRX-20250101-00001', total: 50000, ... }
});

// Channel: 'stok-global' — update stok ke semua terminal aktif
wsService.pushAll('stok-global', {
    event:   'stock.updated',
    updates: [
        { produkId: 1, stokBaru: 95 },
        { produkId: 2, stokBaru: 48 }
    ]
});
```

---

## Consumer Groups

| Group ID | Topic | File |
|---|---|---|
| `pos-transaction-group` | `transaction-events` | consumers/transactionConsumer.js |
| `pos-inventory-group` | `inventory-updates` | consumers/purchaseConsumer.js |
| `pos-return-group` | `inventory-updates` | consumers/returnConsumer.js |

**Catatan:** purchaseConsumer dan returnConsumer subscribe ke topic yang sama
(`inventory-updates`) tapi dengan group ID berbeda. Filter event berdasarkan
field `eventType` dalam payload.

---

## Ports

| Port | Protokol | Fungsi |
|---|---|---|
| 3000 | HTTP | Menerima event dari Laravel (internal Docker) |
| 3001 | WebSocket | Push ke browser (diproxy nginx di path /ws) |
