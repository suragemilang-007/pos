'use strict';

/**
 * websocket/wsServer.js — WebSocket server port 3001.
 *
 * Protokol subscribe yang dipilih: pesan JSON dari client (bukan query param).
 * Alasan: query param hanya bisa subscribe satu channel saat connect, dan
 * memaksa reconnect untuk berpindah channel. Dengan message-based protocol,
 * satu koneksi bisa subscribe banyak channel tanpa reconnect.
 *
 * Format pesan dari client:
 *   { "action": "subscribe",   "channel": "user-2" }
 *   { "action": "subscribe",   "channel": "stok-global" }
 *   { "action": "unsubscribe", "channel": "user-2" }
 *
 * Format push dari server ke client (selalu JSON):
 *   { "event": "transaction.completed", "transaksiId": 123, ... }
 *   { "event": "stock.updated", "updates": [...] }
 */

const { WebSocketServer, WebSocket } = require('ws');
const wsService = require('../services/wsService');

const WS_PORT = parseInt(process.env.WS_PORT || '3001');

// Whitelist channel yang valid — tolak subscribe ke channel sembarang
// untuk mencegah satu client membanjiri channel orang lain
const VALID_CHANNEL_PREFIXES = ['user-', 'stok-global'];

/**
 * Cek apakah nama channel diizinkan.
 * 'stok-global' diterima apa adanya.
 * 'user-{n}' diterima jika suffix adalah angka positif.
 */
function isValidChannel(channel) {
    if (channel === 'stok-global') return true;
    if (/^user-\d+$/.test(channel)) return true;
    return false;
}

/**
 * Tangani satu pesan dari client.
 * Return false jika pesan tidak valid — log tapi tidak putus koneksi.
 *
 * @param {WebSocket} ws
 * @param {Buffer|string} rawData
 */
function handleMessage(ws, rawData) {
    let msg;

    try {
        msg = JSON.parse(rawData.toString());
    } catch {
        // Abaikan pesan non-JSON — client mungkin mengirim ping teks
        return;
    }

    const { action, channel } = msg;

    if (!action || !channel) return;

    if (!isValidChannel(channel)) {
        ws.send(JSON.stringify({
            event:   'error',
            message: `Channel "${channel}" tidak valid`,
        }));
        return;
    }

    if (action === 'subscribe') {
        wsService.subscribe(ws, channel);

        // Konfirmasi ke client agar tahu subscribe berhasil
        ws.send(JSON.stringify({
            event:   'subscribed',
            channel: channel,
        }));

    } else if (action === 'unsubscribe') {
        // Unsubscribe dari satu channel saja — tidak semua channel
        // wsService tidak punya unsubscribeOne, jadi kita kelola manual
        // Pendekatan: unsubscribe semua, lalu subscribe ulang ke channel lain
        // yang masih aktif. Tapi ini kompleks — untuk sekarang, tidak support
        // unsubscribe parsial. Client cukup disconnect dan reconnect.
        ws.send(JSON.stringify({
            event:   'error',
            message: 'Gunakan disconnect dan reconnect untuk unsubscribe',
        }));

    } else {
        ws.send(JSON.stringify({
            event:   'error',
            message: `Action "${action}" tidak dikenal`,
        }));
    }
}

/**
 * Start WebSocket server.
 * Return { wss } agar index.js bisa call wss.close() saat graceful shutdown.
 */
function start() {
    const wss = new WebSocketServer({ port: WS_PORT });

    wss.on('connection', (ws) => {
        console.log(`[WS] Client terhubung | total: ${wss.clients.size}`);

        // Kirim welcome message agar client tahu koneksi berhasil
        ws.send(JSON.stringify({
            event:   'connected',
            message: 'WebSocket POS terhubung. Kirim {action:"subscribe", channel:"..."} untuk mulai.',
        }));

        // ── Message handler ───────────────────────────────────────────────────
        ws.on('message', (rawData) => {
            handleMessage(ws, rawData);
        });

        // ── Disconnect handler ────────────────────────────────────────────────
        ws.on('close', (code, reason) => {
            wsService.unsubscribe(ws);
            console.log(`[WS] Client terputus | code: ${code} | remaining: ${wss.clients.size}`);
        });

        // ── Error handler — WAJIB dipasang ───────────────────────────────────
        // Tanpa handler ini, error pada ws akan naik ke process level sebagai
        // uncaughtException dan bisa menghentikan seluruh aplikasi.
        // Contoh error: ECONNRESET ketika client disconnect paksa (browser ditutup)
        ws.on('error', (err) => {
            if (err.code !== 'ECONNRESET') {
                // ECONNRESET adalah normal — browser/app ditutup paksa
                console.error(`[WS] Error koneksi: ${err.message}`);
            }
            wsService.unsubscribe(ws);
        });
    });

    // ── Server-level error (port sudah dipakai, dll) ──────────────────────────
    wss.on('error', (err) => {
        console.error(`[WS] Server error: ${err.message}`);
    });

    console.log(`[WS] WebSocket server aktif di port ${WS_PORT}`);

    return { wss };
}

module.exports = { start };
