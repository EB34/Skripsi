<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    protected $table = 'detail_penjualan';
    protected $guarded = [];

    public function penjualan() { return $this->belongsTo(Penjualan::class, 'penjualan_id'); }
    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }

    // PO lensa custom yang memesan baris nota ini (maksimal satu)
    public function pembelianCustom() { return $this->hasOne(DetailPembelian::class, 'detail_penjualan_id'); }

    // Label nota untuk dropdown PO dan checklist. Kalau mau memakai nomor nota asli, ganti '#'.$pj->id di sini.
    public function getLabelNotaAttribute(): string
    {
        $pj = $this->penjualan;
        if (! $pj) {
            return 'Nota -';
        }

        $cust = $pj->customer?->nama;

        return 'Nota #' . $pj->id . ' | ' . $pj->tanggal->format('d/m/Y') . ($cust ? ' | ' . $cust : '');
    }
}