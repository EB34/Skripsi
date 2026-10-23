<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturSupplier extends Model
{
    protected $table = 'retur_supplier';
    protected $guarded = [];

    public function pembelian() { return $this->belongsTo(Pembelian::class, 'pembelian_id'); }
    public function detail() { return $this->hasMany(DetailReturSupplier::class, 'retur_supplier_id'); }
}
