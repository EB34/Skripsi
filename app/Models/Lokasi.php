<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    protected $table = 'lokasi';
    protected $guarded = [];

    protected $casts = ['is_gudang' => 'boolean'];

    public function stokLokasi() { return $this->hasMany(StokLokasi::class, 'lokasi_id'); }
}