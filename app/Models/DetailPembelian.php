<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPembelian extends Model
{
    protected $table = 'detail_pembelian';
    protected $guarded = [];

    public function pembelian() { return $this->belongsTo(Pembelian::class, 'pembelian_id'); }
    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }
}
