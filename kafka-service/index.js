/**
 * kafka-service/index.js
 * Entry point utama. Menjalankan semua komponen secara berurutan
 * dengan retry logic karena MySQL dan Kafka mungkin belum siap saat startup.
 */

const { Kafka } = require("kafkajs");
const express = require("express");
const http = require("http");

// ─── Konfigurasi dari environment (diset di docker-compose.yml) ──────────────
const KAFKA_BROKERS = process.env.KAFKA_BROKERS || "kafka:9092";
const KAFKA_CLIENT_ID = process.env.KAFKA_CLIENT_ID || "pos-grosir-client";
const PRODUCER_HTTP_PORT = parseInt(process.env.PRODUCER_HTTP_PORT || "3000");
const WS_PORT = parseInt(process.env.WS_PORT || "3001");

// ─── Kafka instance ───────────────────────────────────────────────────────────
const kafka = new Kafka({
  clientId: KAFKA_CLIENT_ID,
  brokers: [KAFKA_BROKERS],
  // Retry agresif karena Kafka mungkin belum sepenuhnya siap saat container start
  retry: {
    initialRetryTime: 3000,
    retries: 10,
  },
});

// ─── Express HTTP Server (Producer endpoint + health check) ──────────────────
const app = express();
app.use(express.json());

// Health check — dipakai Docker healthcheck di Dockerfile
app.get("/health", (req, res) => {
  res.json({ status: "ok", timestamp: new Date().toISOString() });
});

// Placeholder endpoint publish — akan diisi di Fase 3
app.post("/publish", (req, res) => {
  console.log("[HTTP] Event diterima:", req.body?.eventType);
  res.json({
    status: "received",
    message: "Belum terhubung ke Kafka Producer",
  });
});

const httpServer = http.createServer(app);
httpServer.listen(PRODUCER_HTTP_PORT, () => {
  console.log(`[HTTP] Producer endpoint aktif di port ${PRODUCER_HTTP_PORT}`);
});

// ─── WebSocket Server (placeholder) ──────────────────────────────────────────
const WebSocket = require("ws");
const wss = new WebSocket.Server({ port: WS_PORT });

wss.on("connection", (ws) => {
  console.log("[WS] Client terhubung");
  ws.on("close", () => console.log("[WS] Client terputus"));
});

console.log(`[WS] WebSocket server aktif di port ${WS_PORT}`);

// ─── Test koneksi Kafka ───────────────────────────────────────────────────────
async function testKafkaConnection() {
  const admin = kafka.admin();
  try {
    await admin.connect();
    const topics = await admin.listTopics();
    console.log("[KAFKA] Koneksi berhasil. Topics:", topics);
    await admin.disconnect();
  } catch (err) {
    // Tidak crash — hanya log. Consumer akan retry sendiri saat dipanggil nanti.
    console.warn("[KAFKA] Koneksi awal gagal (akan retry):", err.message);
  }
}

// ─── Graceful shutdown ────────────────────────────────────────────────────────
process.on("SIGTERM", () => {
  console.log("[SHUTDOWN] Menerima SIGTERM, menutup server...");
  httpServer.close(() => process.exit(0));
});

process.on("SIGINT", () => {
  console.log("[SHUTDOWN] Menerima SIGINT, menutup server...");
  httpServer.close(() => process.exit(0));
});

// ─── Start ────────────────────────────────────────────────────────────────────
console.log("=== POS Kafka Service Starting ===");
console.log(`Kafka Broker  : ${KAFKA_BROKERS}`);
console.log(`HTTP Port     : ${PRODUCER_HTTP_PORT}`);
console.log(`WebSocket Port: ${WS_PORT}`);

testKafkaConnection();
