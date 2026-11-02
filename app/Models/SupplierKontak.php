<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierKontak extends Model
{
    protected $table = 'supplier_kontak';
    protected $guarded = [];

    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id'); }
}