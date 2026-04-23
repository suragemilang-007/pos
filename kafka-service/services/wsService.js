'use strict';

/**
 * services/wsService.js — Manajemen channel WebSocket dan push ke browser.
 *
 * Modul ini memiliki state channels Map sendiri (tidak menerima dari luar).
 * Semua consumer memanggil push/pushAll, semua wsServer memanggil subscribe/unsubscribe.
 *
 * Struktur data yang dipilih:
 *   channels:   Map<string, Set<WebSocket>>  — forward: channel → siapa yang listen
 *   wsChannels: WeakMap<WebSocket, Set<string>> — reverse: ws → channel apa yang ia subscribe
 *
 * WeakMap dipilih untuk wsChannels karena:
 * - Tidak mencegah garbage collection WebSocket yang sudah disconnect
 * - Tidak perlu cleanup manual saat ws object di-GC
 * - Lookup O(1) untuk menemukan semua channel milik satu ws
 */

const { WebSocket } = require('ws');

// Forward index: untuk push ke channel → temukan semua subscriber
const channels = new Map();

// Reverse index: untuk unsubscribe → temukan semua channel milik satu ws
// Tanpa ini, unsubscribe harus iterate seluruh channels Map — O(c × s)
// Dengan ini, cukup O(k) di mana k = jumlah channel yang di-subscribe ws ini
const wsChannels = new WeakMap();

/**
 * Daftarkan satu WebSocket ke sebuah channel.
 * Satu client bisa subscribe ke lebih dari satu channel (contoh: 'user-1' + 'stok-global').
 *
 * @param {WebSocket} ws
 * @param {string}    channel  — misal: 'user-2', 'stok-global'
 */
function subscribe(ws, channel) {
    // Forward: channel → tambah ws ke Set subscriber
    if (!channels.has(channel)) {
        channels.set(channel, new Set());
    }
    channels.get(channel).add(ws);

    // Reverse: ws → catat channel ini
    if (!wsChannels.has(ws)) {
        wsChannels.set(ws, new Set());
    }
    wsChannels.get(ws).add(channel);

    console.log(`[WS-SERVICE] subscribe: channel="${channel}" | total=${channels.get(channel).size}`);
}

/**
 * Hapus satu WebSocket dari semua channel yang ia subscribe.
 * Dipanggil saat client disconnect (close atau error).
 *
 * Channel yang kosong setelah unsubscribe dihapus dari Map
 * untuk mencegah akumulasi key kosong seiring waktu.
 *
 * @param {WebSocket} ws
 */
function unsubscribe(ws) {
    const subscribedChannels = wsChannels.get(ws);

    if (!subscribedChannels) return; // ws belum pernah subscribe channel apapun

    subscribedChannels.forEach(channel => {
        const channelSet = channels.get(channel);
        if (!channelSet) return;

        channelSet.delete(ws);

        // Hapus entry channel kosong agar Map tidak terus membesar
        if (channelSet.size === 0) {
            channels.delete(channel);
        }
    });

    // WeakMap.delete() — bersihkan reverse index
    wsChannels.delete(ws);
}

/**
 * Kirim data ke semua subscriber sebuah channel.
 * Jika tidak ada subscriber, fungsi ini no-op (tidak throw).
 *
 * Koneksi yang sudah mati (readyState bukan OPEN) dikumpulkan dulu,
 * baru diunsubscribe setelah iterasi selesai — menghindari modifikasi
 * Set sambil di-iterate.
 *
 * @param {string} channel
 * @param {object} data  — akan di-JSON.stringify sebelum dikirim
 */
function push(channel, data) {
    const subscribers = channels.get(channel);

    if (!subscribers || subscribers.size === 0) return;

    const payload = JSON.stringify(data);
    const dead    = [];

    subscribers.forEach(ws => {
        if (ws.readyState !== WebSocket.OPEN) {
            // Koneksi mati — tandai untuk cleanup, jangan kirim
            dead.push(ws);
            return;
        }

        try {
            ws.send(payload);
        } catch (err) {
            // ws.send() bisa throw jika koneksi putus tepat saat kita kirim
            // (race condition antara readyState check dan send)
            dead.push(ws);
        }
    });

    // Bersihkan koneksi mati yang ditemukan selama iterasi
    dead.forEach(deadWs => unsubscribe(deadWs));
}

/**
 * Broadcast ke semua subscriber sebuah channel (biasanya 'stok-global').
 * Nama terpisah dari push() untuk kejelasan intent di sisi consumer:
 *   push()    → kirim ke user tertentu  ('user-2')
 *   pushAll() → broadcast ke semua terminal ('stok-global')
 *
 * @param {string} broadcastChannel  — sesuai CLAUDE.md: 'stok-global'
 * @param {object} data
 */
function pushAll(broadcastChannel, data) {
    push(broadcastChannel, data);
}

/**
 * Debug helper — jumlah subscriber per channel saat ini.
 * Berguna untuk monitoring di log consumer.
 */
function getStats() {
    const stats = {};
    channels.forEach((set, channel) => {
        stats[channel] = set.size;
    });
    return stats;
}

module.exports = { subscribe, unsubscribe, push, pushAll, getStats };
