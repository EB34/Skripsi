<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPembelian extends Model
{
    protected $table = 'detail_pembelian';
    protected $guarded = [];

    protected $casts = [
        'diretur' => 'boolean',
    ];

    public function pembelian() { return $this->belongsTo(Pembelian::class, 'pembelian_id'); }
    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }

    // Baris nota yang dipesankan (hanya untuk PO lensa custom)
    public function detailPenjualan() { return $this->belongsTo(DetailPenjualan::class, 'detail_penjualan_id'); }

    public function getLabelNotaAttribute(): ?string
    {
        return $this->detailPenjualan?->label_nota;
    }
}