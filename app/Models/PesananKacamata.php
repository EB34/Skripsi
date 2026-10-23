<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananKacamata extends Model
{
    protected $table = 'pesanan_kacamata';
    protected $guarded = [];

    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function frame() { return $this->belongsTo(Barang::class, 'frame_id'); }
    public function lensa() { return $this->belongsTo(Barang::class, 'lensa_id'); }
    public function petugas() { return $this->belongsTo(User::class, 'petugas_id'); }
    public function resep() { return $this->hasOne(ResepKacamata::class, 'pesanan_id'); }
    public function logNotifikasi() { return $this->hasMany(LogNotifikasi::class, 'pesanan_id'); }
}
