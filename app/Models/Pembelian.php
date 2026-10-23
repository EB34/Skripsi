<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    protected $table = 'pembelian';
    protected $guarded = [];

    protected $casts = [
        'tanggal'       => 'date',
        'diterima_pada' => 'datetime',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function detail() { return $this->hasMany(DetailPembelian::class, 'pembelian_id'); }
    public function retur() { return $this->hasMany(ReturSupplier::class, 'pembelian_id'); }

    // Total nilai PO = jumlah dipesan x harga beli, dijumlahkan semua baris
    public function getTotalTagihanAttribute(): float
    {
        return (float) $this->detail->sum(fn ($d) => $d->jumlah_dipesan * $d->harga_beli);
    }
}