<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customer';
    protected $guarded = [];

    // Nama kolom nomor WhatsApp di tabel customer.
    // CEK di HeidiSQL, lalu ganti nilai ini kalau nama kolomnya berbeda.
    public const KOLOM_WA = 'no_wa';

    public function getNomorWaAttribute(): ?string
    {
        return $this->{self::KOLOM_WA};
    }

    public function penjualan() {
        return $this->hasMany(Penjualan::class, 'customer_id')
            ->orderByDesc('tanggal')
            ->orderByDesc('id');
        }
    public function pesanan() {
        return $this->hasMany(PesananKacamata::class, 'customer_id');
    }

    public function refraksi() {
        return $this->hasMany(Refraksi::class)
        ->orderByDesc('tanggal_periksa')
        ->orderByDesc('id');
    }
}