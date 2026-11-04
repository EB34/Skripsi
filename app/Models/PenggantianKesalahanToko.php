<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenggantianKesalahanToko extends Model
{
    protected $table = 'penggantian_kesalahan_toko';
    protected $guarded = [];

    public const JENIS = [
        'salah_ukuran' => 'Lensa salah ukuran',
        'salah_potong' => 'Salah potong / tidak muat di frame',
        'lensa_pecah'  => 'Lensa pecah / tergores saat pengerjaan',
        'lainnya'      => 'Lainnya',
    ];

    protected $casts = [
        'nilai_kerugian' => 'float',
    ];

    public function pesanan()        { return $this->belongsTo(PesananKacamata::class, 'pesanan_id'); }
    public function lensaWriteoff()  { return $this->belongsTo(Barang::class, 'lensa_writeoff_id'); }
    public function lensaPengganti() { return $this->belongsTo(Barang::class, 'lensa_pengganti_id'); }
    public function pencatat()       { return $this->belongsTo(User::class, 'user_id'); }

    /** Nomor diturunkan dari id (tidak disimpan): KST-0001, KST-0002, dst. */
    public function getNomorAttribute(): string
    {
        return 'KST-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS[$this->jenis_kesalahan] ?? (string) $this->jenis_kesalahan;
    }
}