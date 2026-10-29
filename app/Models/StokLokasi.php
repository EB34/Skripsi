<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokLokasi extends Model
{
    protected $table = 'stok_lokasi';
    protected $guarded = [];

    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }
    public function lokasi() { return $this->belongsTo(Lokasi::class, 'lokasi_id'); }
}