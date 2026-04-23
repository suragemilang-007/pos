'use strict';

/**
 * config/kafka.js — KafkaJS instance dan nama topic.
 *
 * Semua nilai wajib dari env variable — JANGAN hardcode.
 * Dalam Docker Compose, env ini diset di docker-compose.yml:
 *   KAFKA_CLIENT_ID  = pos-grosir-client
 *   KAFKA_BROKERS    = kafka:9092          ← nama service Docker, bukan localhost
 *   KAFKA_TOPIC_TRANSACTION = transaction-events
 *   KAFKA_TOPIC_INVENTORY   = inventory-updates
 */

const { Kafka, logLevel } = require('kafkajs');

const kafka = new Kafka({
    clientId: process.env.KAFKA_CLIENT_ID || 'pos-grosir-client',

    // Array karena KafkaJS mendukung multi-broker.
    // Nilai 'kafka:9092' adalah hostname service di Docker network pos_network.
    // JANGAN ganti dengan 'localhost:9092' — localhost tidak bisa diakses antar container.
    brokers: [process.env.KAFKA_BROKERS || 'kafka:9092'],

    // Retry agresif karena Kafka mungkin belum sepenuhnya healthy saat container start,
    // terutama di startup pertama kali saat KRaft broker masih inisialisasi.
    retry: {
        initialRetryTime: 3000,  // 3 detik sebelum retry pertama
        retries:          10,    // total 10x retry ≈ ~5 menit tunggu maksimal
    },

    // Hanya tampilkan ERROR di production — kurangi noise log.
    // Ganti ke logLevel.DEBUG untuk troubleshoot consumer lag atau offset issues.
    logLevel: logLevel.ERROR,
});

/**
 * Nama topic diambil dari env agar mudah diubah per environment (dev/staging/prod)
 * tanpa perlu rebuild image.
 */
const TOPICS = {
    TRANSACTION: process.env.KAFKA_TOPIC_TRANSACTION || 'transaction-events',
    INVENTORY:   process.env.KAFKA_TOPIC_INVENTORY   || 'inventory-updates',
};

module.exports = { kafka, TOPICS };
