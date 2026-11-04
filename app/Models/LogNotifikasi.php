<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogNotifikasi extends Model
{
    protected $table = 'log_notifikasi';

    protected $fillable = [
        'customer_id', 'jenis', 'tujuan', 'pesan', 'status', 'percobaan',
        'respons', 'referensi_tipe', 'referensi_id', 'dibuat_oleh', 'dikirim_pada',
    ];

    protected $casts = [
        'dikirim_pada' => 'datetime',
    ];

    public function getKelasStatusAttribute(): string
    {
        return match ($this->status) {
            'terkirim' => 'bg-success',
            'gagal' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}