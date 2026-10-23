<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'supplier';
    protected $guarded = [];

    public function pembelian() { return $this->hasMany(Pembelian::class, 'supplier_id'); 
    }
}
