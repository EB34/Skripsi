<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';
    protected $guarded = [];

    public function detailPembelian() { return $this->hasMany(DetailPembelian::class, 'barang_id'); }
    public function mutasiStok() { return $this->hasMany(MutasiStok::class, 'barang_id'); }
    public function kondisi() { return $this->hasMany(KondisiBarang::class, 'barang_id'); }
    public function laporanRusak() { return $this->hasMany(BarangRusak::class, 'barang_id'); }
    public function stokLokasi() { return $this->hasMany(StokLokasi::class, 'barang_id'); }

    /** Barang yang stoknya mencapai atau di bawah batas minimumnya sendiri (sudah termasuk yang habis). */
    public function scopeMenipis($query)
    {
        return $query->whereColumn('stok_tersedia', '<=', 'stok_minimum');
    }

    /** Batch FIFO yang masih ada sisanya, dari yang terlama (aturan sama dengan keluarFifo). */
    public function batchAktif()
    {
        return $this->hasMany(DetailPembelian::class, 'barang_id')
            ->where('sisa_jumlah', '>', 0)
            ->whereNotNull('tanggal_masuk')
            ->orderBy('tanggal_masuk')
            ->orderBy('id');
    }

    /** Nilai persediaan = jumlah (sisa_jumlah x harga_beli) semua batch aktif. */
    public function nilaiPersediaan(): float
    {
        return (float) DetailPembelian::where('barang_id', $this->id)
            ->where('sisa_jumlah', '>', 0)
            ->whereNotNull('tanggal_masuk')
            ->selectRaw('COALESCE(SUM(sisa_jumlah * harga_beli), 0) as total')
            ->value('total');
    }

    /**
     * Status stok: habis / menipis / aman.
     * "Menipis" jika stok <= stok_minimum milik barang itu sendiri.
     */
    public function getStatusStokAttribute(): string
    {
        if ($this->stok_tersedia <= 0) {
            return 'habis';
        }
        if ($this->stok_tersedia <= $this->stok_minimum) {
            return 'menipis';
        }
        return 'aman';
    }

    /** Label kategori untuk tabel monitoring. */
    public function getKategoriLabelAttribute(): string
    {
        return match ($this->jenis) {
            'frame'    => 'Frame Kacamata',
            'lensa'    => 'Lensa',
            'softlens' => 'Softlens',
            default    => ucfirst((string) $this->jenis),
        };
    }
}