<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenggantianKesalahanToko extends Model
{
    protected $table = 'penggantian_kesalahan_toko';
    protected $guarded = [];

    public function pesanan() { return $this->belongsTo(PesananKacamata::class, 'pesanan_id'); }
    public function lensaWriteoff() { return $this->belongsTo(Barang::class, 'lensa_writeoff_id'); }
    public function lensaPengganti() { return $this->belongsTo(Barang::class, 'lensa_pengganti_id'); }
}
