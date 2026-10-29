<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangRusak extends Model
{
    protected $table = 'barang_rusak';
    protected $guarded = [];

    protected $casts = [
        'nilai_hpp'       => 'float',
        'diputuskan_pada' => 'datetime',
    ];

    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }
    public function pelapor() { return $this->belongsTo(User::class, 'user_id'); }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'menunggu_keputusan' => 'Menunggu Keputusan',
            'retur_supplier'     => 'Retur ke Supplier',
            'write_off'          => 'Write Off',
            default              => (string) $this->status,
        };
    }
}