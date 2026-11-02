<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananKacamata extends Model
{
    protected $table = 'pesanan_kacamata';
    protected $guarded = [];

    protected $casts = [
        'estimasi_selesai'  => 'date',
        'dikerjakan_pada'   => 'datetime',
        'siap_diambil_pada' => 'datetime',
        'selesai_pada'      => 'datetime',
    ];

    // Label status, sama dengan dropdown filter di mockup
    public const STATUS = [
        'menunggu'     => 'Menunggu Pengerjaan',
        'dikerjakan'   => 'Sedang Dikerjakan',
        'siap_diambil' => 'Siap Diambil',
        'selesai'      => 'Selesai',
    ];

    public function customer()   { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function refraksi()   { return $this->belongsTo(Refraksi::class, 'refraksi_id'); }
    public function frame()      { return $this->belongsTo(Barang::class, 'frame_id'); }
    public function lensa()      { return $this->belongsTo(Barang::class, 'lensa_id'); }
    public function petugas()    { return $this->belongsTo(User::class, 'petugas_id'); }
    public function pencatat()   { return $this->belongsTo(User::class, 'dibuat_oleh'); }
    public function logNotifikasi() { return $this->hasMany(LogNotifikasi::class, 'pesanan_id'); } // dipakai modul WhatsApp nanti

    // Pesanan aktif = belum selesai (dipakai pil bar atas)
    public function scopeAktif($query)
    {
        return $query->where('status', '!=', 'selesai');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}