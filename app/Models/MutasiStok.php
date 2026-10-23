<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    protected $table = 'mutasi_stok';
    protected $guarded = [];

    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }
}
