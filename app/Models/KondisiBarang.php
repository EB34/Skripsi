<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KondisiBarang extends Model
{
    protected $table = 'kondisi_barang';
    protected $guarded = [];

    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }
    public function pelapor() { return $this->belongsTo(User::class, 'pelapor_id'); }
}
