<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KerugianOperasional extends Model
{
    protected $table = 'kerugian_operasional';
    protected $guarded = [];

    public const JENIS_WRITE_OFF_RUSAK = 'write_off_rusak';
    public const JENIS_SELISIH_OPNAME  = 'selisih_opname';
    public const JENIS_KESALAHAN_TOKO  = 'kesalahan_toko'; // penulisnya baru dibuat di Modul Retur

    public const JENIS = [
        self::JENIS_WRITE_OFF_RUSAK => 'Write Off Barang Rusak',
        self::JENIS_SELISIH_OPNAME  => 'Selisih Opname',
        self::JENIS_KESALAHAN_TOKO  => 'Kesalahan Pengerjaan Toko',
    ];

    protected $casts = [
        'nilai'   => 'float',
        'tanggal' => 'datetime',
    ];

    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }
    public function user()   { return $this->belongsTo(User::class, 'user_id'); }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? (string) $this->jenis;
    }

    /** Untuk Laporan Laba Rugi nanti: KerugianOperasional::periode($dari, $sampai)->sum('nilai'). */
    public function scopePeriode($query, $dari, $sampai)
    {
        return $query->whereBetween('tanggal', [$dari, $sampai]);
    }
}