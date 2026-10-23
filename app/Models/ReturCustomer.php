<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturCustomer extends Model
{
    protected $table = 'retur_customer';
    protected $guarded = [];

    public function penjualan() { return $this->belongsTo(Penjualan::class, 'penjualan_id'); }
}
