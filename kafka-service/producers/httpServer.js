'use strict';

/**
 * producers/httpServer.js — Express HTTP server port 3000.
 *
 * Menerima HTTP POST dari Laravel, routing ke Kafka topic yang sesuai.
 * Laravel tidak langsung publish ke Kafka — semua lewat endpoint ini
 * agar konfigurasi Kafka terpusat di Node.js service, bukan di PHP.
 */

const express    = require('express');
const http       = require('http');
const { TOPICS } = require('../config/kafka');

const HTTP_PORT = parseInt(process.env.PRODUCER_HTTP_PORT || '3000');

/**
 * Routing table: eventType → nama topic Kafka.
 *
 * Dua topic dipakai (bukan satu) karena consumer group berbeda:
 * - transaction-events → hanya dikonsumsi transactionConsumer (validasi stok real-time)
 * - inventory-updates  → dikonsumsi purchaseConsumer DAN returnConsumer (filter per eventType)
 *
 * Memisahkan topic memungkinkan scaling consumer secara independen:
 * transaksi bisa punya lebih banyak partition jika throughput kasir tinggi.
 */
const TOPIC_MAP = {
    'transaction.created':          TOPICS.TRANSACTION,
    'transaction.void.requested':   TOPICS.TRANSACTION,  // voidConsumer.js — group pos-void-group
    'purchase.created':             TOPICS.INVENTORY,
    'sale.return.requested':        TOPICS.INVENTORY,
    'purchase.return.requested':    TOPICS.INVENTORY,
};

/**
 * Inisialisasi producer KafkaJS.
 *
 * idempotent: true dipilih karena event stok tidak boleh diproses dua kali.
 * Jika Laravel retry POST (timeout/network error) dan producer juga retry,
 * tanpa idempotent Kafka akan menerima dua pesan identik → Consumer akan
 * UPDATE qty_stok dua kali → stok salah.
 *
 * Konsekuensi: maxInFlightRequests wajib 1 (syarat KafkaJS untuk idempotent).
 *
 * @param {import('kafkajs').Kafka} kafka
 * @returns {Promise<import('kafkajs').Producer>}
 */
async function createProducer(kafka) {
    const producer = kafka.producer({
        idempotent:          true,
        // Wajib 1 jika idempotent=true — KafkaJS akan throw jika > 1
        maxInFlightRequests: 1,
    });

    await producer.connect();
    console.log('[KAFKA] Producer terhubung ke broker');

    return producer;
}

/**
 * Start HTTP server dan Kafka producer.
 *
 * @param {import('mysql2/promise').Pool}  _pool  - tidak dipakai di producer, tapi
 *                                                  dijaga agar signature konsisten dengan
 *                                                  pemanggil di index.js
 * @param {import('kafkajs').Kafka}         kafka
 * @returns {Promise<{ server: http.Server, producer: import('kafkajs').Producer }>}
 */
async function start(_pool, kafka) {
    const producer = await createProducer(kafka);

    const app = express();

    // limit: '256kb' — lindungi dari payload oversized yang bisa memori habis.
    // Payload Kafka event terbesar (transaksi dengan banyak item) tidak lebih dari beberapa KB.
    app.use(express.json({ limit: '256kb' }));

    // ── GET /health ────────────────────────────────────────────────────────────
    // Dipakai Docker healthcheck di Dockerfile.
    // Jika endpoint ini tidak response 200, Docker akan restart container.
    app.get('/health', (_req, res) => {
        res.status(200).json({
            status:    'ok',
            timestamp: new Date().toISOString(),
        });
    });

    // ── POST /publish ──────────────────────────────────────────────────────────
    app.post('/publish', async (req, res) => {
        const body = req.body;

        // ── Validasi 1: body harus ada ────────────────────────────────────────
        if (!body || typeof body !== 'object') {
            return res.status(400).json({
                success: false,
                message: 'Request body harus berupa JSON object',
            });
        }

        const { eventType } = body;

        // ── Validasi 2: eventType wajib ada ───────────────────────────────────
        if (!eventType) {
            return res.status(400).json({
                success: false,
                message: 'Field "eventType" wajib ada dalam body',
            });
        }

        // ── Validasi 3: eventType harus dikenal ───────────────────────────────
        const topicName = TOPIC_MAP[eventType];

        if (!topicName) {
            return res.status(400).json({
                success:    false,
                message:    `eventType "${eventType}" tidak dikenal`,
                validTypes: Object.keys(TOPIC_MAP),
            });
        }

        // ── Publish ke Kafka ───────────────────────────────────────────────────
        try {
            await producer.send({
                topic:    topicName,
                messages: [{ value: JSON.stringify(body) }],
                // acks default = -1 (all replicas) karena idempotent=true.
                // Ini memastikan pesan tidak hilang jika leader broker crash sesaat setelah menerima.
            });

            console.log(`[KAFKA PUBLISH] ${eventType} → topic ${topicName}`);

            return res.status(200).json({
                success:   true,
                eventType: eventType,
                topic:     topicName,
            });

        } catch (err) {
            // Kafka error: broker tidak responsif, topic tidak ada, dll.
            // Return 503 (bukan 500) karena ini upstream failure, bukan bug kode kita.
            // Laravel bisa retry POST jika terima 503.
            console.error(`[KAFKA PUBLISH] GAGAL publish "${eventType}" → ${topicName}: ${err.message}`);

            return res.status(503).json({
                success: false,
                message: 'Gagal mempublikasikan event ke Kafka',
                error:   err.message,
            });
        }
    });

    // ── 404 handler ───────────────────────────────────────────────────────────
    app.use((_req, res) => {
        res.status(404).json({ message: 'Endpoint tidak ditemukan' });
    });

    // ── Start HTTP server ─────────────────────────────────────────────────────
    const server = http.createServer(app);

    await new Promise((resolve, reject) => {
        server.listen(HTTP_PORT, resolve);
        server.once('error', reject);
    });

    console.log(`[HTTP] Producer endpoint aktif di port ${HTTP_PORT}`);

    // Return producer agar index.js bisa disconnect saat graceful shutdown
    return { server, producer };
}

module.exports = { start };
