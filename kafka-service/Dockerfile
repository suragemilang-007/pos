# ============================================================
# Dockerfile — Node.js Kafka Service (Producer + Consumer + WebSocket)
# ============================================================
FROM node:20-alpine

# ── Working Directory ────────────────────────────────────────
WORKDIR /app

# ── Install Dependencies (layer cache optimization) ──────────
# Copy package files dulu sebelum source code, supaya layer ini
# tidak direbuild setiap ada perubahan kode
COPY package*.json ./
RUN npm ci --only=production

# ── Copy Application Code ────────────────────────────────────
COPY . .

# Port 3000: HTTP endpoint — menerima event dari Laravel
# Port 3001: WebSocket server — push notifikasi ke browser
EXPOSE 3000 3001

# Health check: pastikan HTTP producer endpoint bisa diakses
HEALTHCHECK --interval=15s --timeout=5s --retries=3 \
  CMD wget -qO- http://localhost:3000/health || exit 1

CMD ["node", "index.js"]
