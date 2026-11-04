<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturCustomer extends Model
{
    protected $table = 'retur_customer';
    protected $guarded = [];

    public const STATUS_DICATAT = 'dicatat';
    public const STATUS_SELESAI = 'selesai';

    public const STATUS = [
        self::STATUS_DICATAT => 'Menunggu Owner',
        self::STATUS_SELESAI => 'Selesai',
    ];

    public const KONFIRMASI = [
        'supplier_tersedia' => 'Supplier masih ada stok / masih diproduksi',
        'supplier_habis'    => 'Supplier tidak ada stok / tidak diproduksi lagi',
    ];

    public const TINDAK_LANJUT = [
        'tukar_supplier'  => 'Tukar frame dari supplier',
        'tukar_stok_toko' => 'Tukar dengan frame kompatibel dari stok toko',
        'refund'          => 'Refund',
    ];

    protected $casts = [
        'tanggal'        => 'date',
        'diproses_pada'  => 'datetime',
        'jumlah_refund'  => 'float',
        'nilai_kerugian' => 'float',
    ];

    public function penjualan() { return $this->belongsTo(Penjualan::class, 'penjualan_id'); }
    public function barang()    { return $this->belongsTo(Barang::class, 'barang_id'); }
    public function pengganti() { return $this->belongsTo(Barang::class, 'barang_pengganti_id'); }
    public function pencatat()  { return $this->belongsTo(User::class, 'dicatat_oleh'); }
    public function pemroses()  { return $this->belongsTo(User::class, 'diproses_oleh'); }

    /** Nomor diturunkan dari id (tidak disimpan): RTC-0001, RTC-0002, dst. */
    public function getNomorReturAttribute(): string
    {
        return 'RTC-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? (string) $this->status;
    }

    public function getTindakLanjutLabelAttribute(): string
    {
        return self::TINDAK_LANJUT[$this->tindak_lanjut] ?? '-';
    }

    public function getKonfirmasiLabelAttribute(): string
    {
        return self::KONFIRMASI[$this->status_konfirmasi] ?? '-';
    }
}