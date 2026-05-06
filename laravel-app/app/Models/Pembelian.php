<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembelian extends Model
{
    // Laravel pluralize → 'pembelians', harus di-set manual
    protected $table = 'pembelian';

    protected $fillable = [
        'user_id',
        'supplier_id',
        'no_pembelian',
        'total',
        // ── Kolom hutang dagang v4 ────────────────────────────────────────────
        'tipe_pembayaran',
        'jumlah_dp',
        'sisa_hutang',
        'tanggal_jatuh_tempo',
        'status_pembayaran',
        'catatan_pembayaran',
        // ─────────────────────────────────────────────────────────────────────
        'status',
        'tanggal_pembelian',
    ];

    protected function casts(): array
    {
        return [
            'total'                => 'decimal:2',
            'jumlah_dp'            => 'decimal:2',
            'sisa_hutang'          => 'decimal:2',
            // DATE — hanya perlu tanggal, bukan jam
            'tanggal_jatuh_tempo'  => 'date',
            'tanggal_pembelian'    => 'date',
            'created_at'           => 'datetime',
            'updated_at'           => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailPembelian::class, 'pembelian_id');
    }

    public function returPembelian(): HasMany
    {
        return $this->hasMany(ReturPembelian::class, 'pembelian_id');
    }

    // Semua cicilan/pembayaran untuk PO ini (1:N)
    public function pembayaran(): HasMany
    {
        return $this->hasMany(PembayaranPembelian::class, 'pembelian_id');
    }

    // ── Accessor ──────────────────────────────────────────────────────────────

    /**
     * Total yang sudah dibayar = SUM semua cicilan pembayaran.
     * Dipanggil sebagai $pembelian->total_dibayar.
     *
     * Catatan: relasi pembayaran harus di-load terlebih dahulu agar tidak N+1.
     * Gunakan Pembelian::with('pembayaran')->... di query yang membutuhkan ini.
     */
    public function getTotalDibayarAttribute(): float
    {
        return (float) $this->pembayaran->sum('jumlah');
    }

    /**
     * Sisa hari hingga jatuh tempo. Negatif jika sudah lewat.
     * Return null jika PO tidak memiliki tanggal_jatuh_tempo (cash).
     *
     * diffInDays($date, false) → negatif jika tanggal_jatuh_tempo sudah lewat
     * Dipakai untuk mendeteksi hutang yang lewat jatuh tempo di laporan/dashboard.
     */
    public function getHariTersisaAttribute(): ?int
    {
        if (! $this->tanggal_jatuh_tempo) {
            return null;
        }

        return (int) now()->diffInDays($this->tanggal_jatuh_tempo, false);
    }

    /**
     * Apakah PO sudah jatuh tempo (hari_tersisa <= 0)?
     * Dipakai untuk highlight di tabel daftar hutang.
     */
    public function getIsJatuhTempoAttribute(): bool
    {
        return $this->hari_tersisa !== null && $this->hari_tersisa <= 0;
    }
}
