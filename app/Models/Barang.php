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

    /** Status stok: habis / menipis / aman. Dipakai di view sebagai $barang->status_stok */
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
}


