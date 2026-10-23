<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailReturSupplier extends Model
{
    protected $table = 'detail_retur_supplier';
    protected $guarded = [];

    public function retur() { return $this->belongsTo(ReturSupplier::class, 'retur_supplier_id'); }
    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }
}
