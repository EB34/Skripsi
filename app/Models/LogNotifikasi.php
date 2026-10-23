<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogNotifikasi extends Model
{
    protected $table = 'log_notifikasi';
    protected $guarded = [];

    public function pesanan() { return $this->belongsTo(PesananKacamata::class, 'pesanan_id'); }
}
