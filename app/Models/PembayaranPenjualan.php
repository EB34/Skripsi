<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranPenjualan extends Model
{
    protected $table = 'pembayaran_penjualan';
    protected $guarded = [];

    protected $casts = [
        'dibayar_pada' => 'datetime',
    ];

    public const JENIS = [
        'penuh'     => 'Bayar Penuh',
        'dp'        => 'DP',
        'pelunasan' => 'Pelunasan',
    ];

    public function penjualan() { return $this->belongsTo(Penjualan::class, 'penjualan_id'); }
    public function kasir() { return $this->belongsTo(User::class, 'user_id'); }
}