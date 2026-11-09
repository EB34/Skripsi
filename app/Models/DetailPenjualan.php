<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    protected $table = 'detail_penjualan';
    protected $guarded = [];

    public function penjualan() { return $this->belongsTo(Penjualan::class, 'penjualan_id'); }
    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }

    // Semua PO lensa custom untuk baris nota ini (bisa lebih dari satu kalau pernah diretur potong tagihan)
    public function pembelianCustom() { return $this->hasMany(DetailPembelian::class, 'detail_penjualan_id'); }

    // PO yang masih berlaku (belum diretur potong tagihan). Kalau tidak ada, lensa ini perlu dipesan.
    public function pembelianAktif()
    {
        return $this->hasOne(DetailPembelian::class, 'detail_penjualan_id')->where('diretur', false);
    }

    // Label nota untuk dropdown PO, checklist, dan retur
    public function getLabelNotaAttribute(): string
    {
        $pj = $this->penjualan;
        if (! $pj) {
            return 'Nota -';
        }

        $cust = $pj->customer?->nama;

        return 'Nota ' . ($pj->nomor_nota ?: '#' . $pj->id)
            . ' | ' . $pj->tanggal->format('d/m/Y')
            . ($cust ? ' | ' . $cust : '');
    }

    /**
     * Menyusun teks ukuran lensa custom, contoh: "SPH -1.00 | CYL -0.50 | AXIS 90 | ADD +2.00".
     * Bagian yang kosong dilewati.
     */
    public static function susunUkuran($sph, $cyl, $axis, $add): string
    {
        $f = fn ($v) => ((float) $v > 0 ? '+' : '') . number_format((float) $v, 2, '.', '');
        $ada = fn ($v) => $v !== null && $v !== '';

        $bagian = [];
        if ($ada($sph))  { $bagian[] = 'SPH ' . $f($sph); }
        if ($ada($cyl))  { $bagian[] = 'CYL ' . $f($cyl); }
        if ($ada($axis)) { $bagian[] = 'AXIS ' . (int) $axis; }
        if ($ada($add))  { $bagian[] = 'ADD ' . $f($add); }

        return implode(' | ', $bagian);
    }
}