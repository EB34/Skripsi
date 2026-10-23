<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $table = 'penjualan';
    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function kasir() { return $this->belongsTo(User::class, 'user_id'); }
    public function detail() { return $this->hasMany(DetailPenjualan::class, 'penjualan_id'); }
    public function returCustomer() { return $this->hasMany(ReturCustomer::class, 'penjualan_id'); }

    // Subtotal sebelum diskon = total yang tersimpan + diskon
    public function getSubtotalAttribute(): float
    {
        return (float) $this->total + (float) $this->diskon;
    }
}