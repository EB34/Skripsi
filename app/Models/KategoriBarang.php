<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriBarang extends Model
{
    protected $table = 'kategori_barang';
    protected $guarded = [];

    // Nama jenis untuk tab dan pilihan
    public const JENIS = [
        'frame'    => 'Frame',
        'lensa'    => 'Lensa',
        'softlens' => 'Softlens',
    ];

    // Istilah yang tampil di layar untuk tiap jenis
    public const LABEL = [
        'frame'    => 'Material',
        'lensa'    => 'Tipe Lensa',
        'softlens' => 'Tipe Softlens',
    ];

    public function barang()
    {
        return $this->hasMany(Barang::class, 'kategori_id');
    }
}