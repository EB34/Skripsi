<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    protected $table = 'mutasi_stok';
    protected $guarded = [];

    // Kunci = isi kolom jenis_kejadian, nilai = label di dropdown filter.
    public const JENIS = [
        'barang_masuk'   => 'Stok Masuk (Pembelian Supplier)',
        'penjualan'      => 'Stok Terjual (POS Kasir)',
        'retur_customer' => 'Retur Masuk Customer',
        'barang_rusak'   => 'Barang Rusak Internal',
        'retur_supplier' => 'Retur ke Supplier',
        'write_off'      => 'Write Off',
        'penyesuaian'    => 'Koreksi/Inisialisasi Stok',
        'pindah_lokasi'  => 'Pindah Lokasi',
    ];

    // Dipakai kalau kolom keterangan kosong (mutasi dari Pembelian dan Penjualan).
    private const KETERANGAN_DEFAULT = [
        'barang_masuk' => 'Penerimaan barang dari supplier',
        'penjualan'    => 'Penjualan ke customer',
    ];

    // Awalan nomor referensi: kunci = isi kolom referensi_tipe.
    private const PREFIX_REFERENSI = [
        'pembelian'    => 'PO',
        'penjualan'    => 'TR',
        'barang_rusak' => 'BR',
        'opname'       => 'OPN',
    ];

    public function barang() { return $this->belongsTo(Barang::class, 'barang_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }

    /** Label singkat untuk kolom "Jenis Mutasi" (tanpa teks dalam kurung). */
    public function getJenisLabelAttribute(): string
    {
        $label = self::JENIS[$this->jenis_kejadian]
            ?? ucwords(str_replace('_', ' ', (string) $this->jenis_kejadian));

        return trim(preg_replace('/\s*\(.*\)/', '', $label));
    }

    public function getKeteranganTampilAttribute(): string
    {
        return $this->keterangan
            ?? self::KETERANGAN_DEFAULT[$this->jenis_kejadian]
            ?? '-';
    }

    public function getNoReferensiAttribute(): string
    {
        if (!$this->referensi_tipe || !$this->referensi_id) {
            return '-';
        }

        $prefix = self::PREFIX_REFERENSI[$this->referensi_tipe] ?? strtoupper($this->referensi_tipe);

        return $prefix . '-' . $this->referensi_id;
    }
}