<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResepKacamata extends Model
{
    protected $table = 'resep_kacamata';
    protected $guarded = [];

    public function pesanan() { return $this->belongsTo(PesananKacamata::class, 'pesanan_id'); }
}
