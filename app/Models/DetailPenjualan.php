<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    protected $table = 'detail_penjualan';
    protected $guarded = [];

    public function penjualan() { return $this->belongsTo(Penjualan::class, 'penjualan_id'); }
    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }
}
