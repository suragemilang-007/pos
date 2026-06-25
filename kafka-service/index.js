'use strict';

/**
 * kafka-service/index.js — Entry point utama.
 *
 * Urutan startup yang disengaja:
 * 1. createPool()          → pastikan MySQL bisa diakses (retry sampai 5x)
 * 2. ensureTopicsExist()   → buat topic Kafka jika belum ada (admin API)
 * 3. wsServer.start()      → WebSocket harus ready sebelum consumer mulai push
 * 4. httpServer.start()    → HTTP endpoint menerima event dari Laravel
 * 5. *Consumer.start()     → consumer subscribe setelah topic dijamin ada
 *
 * Jika MySQL gagal di step 1, proses exit dengan kode 1 agar
 * Docker bisa restart container secara otomatis (restart: unless-stopped).
 */

const { kafka, TOPICS }                   = require('./config/kafka');
const { createPool }                       = require('./config/database');
const { start: startHttp }                 = require('./producers/httpServer');
const { start: startWs }                   = require('./websocket/wsServer');
const wsService                            = require('./services/wsService');
const { start: startTransactionConsumer }                    = require('./consumers/transactionConsumer');
const { startVoidConsumer, stopVoidConsumer }               = require('./consumers/voidConsumer');
const { start: startPurchaseConsumer }                       = require('./consumers/purchaseConsumer');
const { start: startReturnConsumer }                         = require('./consumers/returnConsumer');
const { startAdjustmentConsumer, stopAdjustmentConsumer }   = require('./consumers/adjustmentConsumer');
const { recoverPendingTransactions }                         = require('./recovery/pendingRecovery');

// ─── Referensi untuk graceful shutdown ───────────────────────────────────────
let httpServer    = null;
let httpProducer  = null;
let wsServer      = null;
let consumers     = [];

// ─────────────────────────────────────────────────────────────────────────────
// STEP 2: Pastikan topic Kafka ada sebelum consumer start
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Buat topic yang dibutuhkan jika belum ada.
 *
 * Mengapa tidak andalkan auto-create?
 * Kafka auto-create topics saat ada produce/consume request, TAPI ada race condition:
 * jika consumer mencoba subscribe sebelum topic terbentuk sempurna (leader election),
 * KafkaJS akan throw "This server does not host this topic-partition".
 *
 * Dengan createTopics() + waitForLeaders: true, kita blokir sampai topic benar-benar
 * siap termasuk leader partition-nya — baru consumer boleh mulai.
 *
 * createTopics() pada topic yang sudah ada = no-op (aman dipanggil berulang).
 */
async function ensureTopicsExist() {
    const admin = kafka.admin();

    try {
        await admin.connect();

        const topicConfigs = [
            {
                topic:             TOPICS.TRANSACTION,
                numPartitions:     3,  // 3 partisi = 3 consumer bisa parallel di masa depan
                replicationFactor: 1,  // 1 = development (production: sesuaikan jumlah broker)
            },
            {
                topic:             TOPICS.INVENTORY,
                numPartitions:     3,
                replicationFactor: 1,
            },
        ];

        // { validateOnly: false } = benar-benar buat, bukan hanya validasi
        // { waitForLeaders: true } = blokir sampai semua leader partition elected
        const created = await admin.createTopics({
            topics:         topicConfigs,
            waitForLeaders: true,
        });

        if (created) {
            console.log(`[KAFKA] Topics dibuat: ${Object.values(TOPICS).join(', ')}`);
        } else {
            // created=false artinya topic sudah ada — bukan error
            console.log(`[KAFKA] Topics sudah ada: ${Object.values(TOPICS).join(', ')}`);
        }

    } finally {
        // Selalu disconnect admin — admin connection terpisah dari producer/consumer
        await admin.disconnect().catch(() => {});
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// MAIN STARTUP
// ─────────────────────────────────────────────────────────────────────────────

async function main() {
    console.log('════════════════════════════════════════');
    console.log('  POS Kafka Service — Starting');
    console.log(`  Kafka  : ${process.env.KAFKA_BROKERS        || 'kafka:9092'}`);
    console.log(`  DB Host: ${process.env.DB_HOST               || 'mysql'}`);
    console.log(`  HTTP   : port ${process.env.PRODUCER_HTTP_PORT || 3000}`);
    console.log(`  WS     : port ${process.env.WS_PORT           || 3001}`);
    console.log('════════════════════════════════════════');

    // ── Step 1: MySQL pool ────────────────────────────────────────────────────
    let pool;
    try {
        pool = await createPool();
    } catch (err) {
        console.error('[FATAL]', err.message);
        process.exit(1);
    }

    // ── Step 2: Pastikan Kafka topics ada ────────────────────────────────────
    // Dilakukan sebelum producer dan consumer init agar tidak ada race condition.
    try {
        await ensureTopicsExist();
    } catch (err) {
        // Kafka belum siap — jangan crash total, tapi consumer tidak akan aktif.
        // Docker akan restart container dan retry lebih awal dari next start.
        console.error('[KAFKA] Gagal memastikan topic (Kafka belum ready?):', err.message);
        console.error('[KAFKA] Consumer tidak akan distart. Restart container untuk retry.');
        // Lanjutkan startup HTTP + WS agar healthcheck tetap bisa dijawab
    }

    // ── Step 3: WebSocket server ──────────────────────────────────────────────
    const ws = startWs();
    wsServer = ws.wss;
    console.log('[MAIN] WebSocket server siap');

    // ── Step 4: HTTP server (Producer endpoint) ───────────────────────────────
    const http   = await startHttp(pool, kafka);
    httpServer   = http.server;
    httpProducer = http.producer;
    console.log('[MAIN] HTTP producer endpoint siap');

    // ── Step 5: Kafka Consumers ───────────────────────────────────────────────
    // transaction, purchase, return dijalankan paralel (group ID berbeda, aman).
    // voidConsumer distart terpisah setelah ketiganya siap, dengan named lifecycle
    // function agar bisa di-stop secara eksplisit di shutdown() via stopVoidConsumer().
    try {
        const [trxConsumer, poConsumer, returConsumer] = await Promise.all([
            startTransactionConsumer(kafka, pool, wsService),
            startPurchaseConsumer(kafka, pool, wsService),
            startReturnConsumer(kafka, pool, wsService),
        ]);

        consumers = [trxConsumer, poConsumer, returConsumer];
        console.log('[MAIN] Transaction, purchase, return consumer aktif');

        // Void consumer distart setelah consumer lain siap — lifecycle dikelola
        // sendiri via _instance di voidConsumer.js (stop via stopVoidConsumer())
        await startVoidConsumer(kafka, pool, wsService);
        console.log('[INIT] Void consumer aktif');

        // Adjustment consumer — group pos-adjustment-group, topic inventory-updates
        await startAdjustmentConsumer(kafka, pool, wsService);
        console.log('[INIT] Adjustment consumer aktif');

        console.log('[MAIN] Semua Kafka consumer aktif');

        // ── Step 6: Recovery transaksi PENDING ───────────────────────────────
        // Harus dijalankan SETELAH consumer aktif agar event yang di-publish ulang
        // langsung diproses. Delay 2 detik agar consumer selesai join group Kafka.
        setTimeout(async () => {
            try {
                await recoverPendingTransactions(httpProducer, pool);
            } catch (err) {
                console.error('[MAIN] Recovery transaksi PENDING gagal:', err.message);
            }
        }, 2000);

    } catch (err) {
        console.error('[MAIN] Kafka consumer gagal start:', err.message);
    }

    console.log('════════════════════════════════════════');
    console.log('  POS Kafka Service — Ready');
    console.log('════════════════════════════════════════');
}

// ─────────────────────────────────────────────────────────────────────────────
// GRACEFUL SHUTDOWN
// ─────────────────────────────────────────────────────────────────────────────

async function shutdown(signal) {
    console.log(`\n[SHUTDOWN] Menerima ${signal}. Menutup semua koneksi...`);

    if (httpProducer) {
        try {
            await httpProducer.disconnect();
            console.log('[SHUTDOWN] Kafka producer disconnected');
        } catch (err) {
            console.error('[SHUTDOWN] Error disconnect producer:', err.message);
        }
    }

    // Void consumer dikelola via named function (bukan consumers array)
    try {
        await stopVoidConsumer();
    } catch (err) {
        console.error('[SHUTDOWN] Error disconnect void consumer:', err.message);
    }

    // Adjustment consumer — lifecycle terpisah seperti void consumer
    try {
        await stopAdjustmentConsumer();
    } catch (err) {
        console.error('[SHUTDOWN] Error disconnect adjustment consumer:', err.message);
    }

    for (const consumer of consumers) {
        try {
            await consumer.disconnect();
            console.log('[SHUTDOWN] Kafka consumer disconnected');
        } catch (err) {
            console.error('[SHUTDOWN] Error disconnect consumer:', err.message);
        }
    }

    if (wsServer) {
        wsServer.close(() => console.log('[SHUTDOWN] WebSocket server closed'));
    }

    if (httpServer) {
        httpServer.close(() => {
            console.log('[SHUTDOWN] HTTP server closed');
            process.exit(0);
        });
    } else {
        process.exit(0);
    }

    setTimeout(() => {
        console.error('[SHUTDOWN] Force exit setelah 10 detik');
        process.exit(1);
    }, 10_000);
}

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT',  () => shutdown('SIGINT'));

process.on('uncaughtException', (err) => {
    console.error('[UNCAUGHT]', err);
    shutdown('uncaughtException');
});

process.on('unhandledRejection', (reason) => {
    console.error('[UNHANDLED REJECTION]', reason);
    // Tidak shutdown — consumer Kafka retry transient errors secara internal
});

main();
