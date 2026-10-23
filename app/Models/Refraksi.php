<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refraksi extends Model
{
    protected $table = 'refraksi';
    protected $guarded = [];
    protected $casts = [
        'tanggal_periksa' => 'date',
    ];

    public function customer() { 
        return $this->belongsTo(Customer::class);
        }

    // User yg cek refraksi
    public function user() { 
        return $this->belongsTo(User::class);
        }
}