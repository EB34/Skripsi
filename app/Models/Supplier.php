<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'supplier';
    protected $guarded = [];

    public function pembelian() { return $this->hasMany(Pembelian::class, 'supplier_id'); }

    // Contact person (bisa lebih dari satu)
    public function kontak() { return $this->hasMany(SupplierKontak::class, 'supplier_id')->orderBy('id'); }
}