<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ReturSupplier extends Model
{
    protected $table = 'retur_supplier';
    protected $guarded = [];

    public const STATUS_DIAJUKAN = 'diajukan';
    public const STATUS_DIKIRIM  = 'dikirim';
    public const STATUS_SELESAI  = 'selesai';

    public const STATUS = [
        self::STATUS_DIAJUKAN => 'Diajukan',
        self::STATUS_DIKIRIM  => 'Dikirim ke Supplier',
        self::STATUS_SELESAI  => 'Selesai',
    ];

    // Jenis retur: supplier mengirim pengganti, atau tagihan dipotong / uang dikembalikan
    public const JENIS_TUKAR   = 'tukar_barang';
    public const JENIS_POTONG  = 'potong_tagihan';

    public const JENIS = [
        self::JENIS_TUKAR  => 'Ganti barang',
        self::JENIS_POTONG => 'Potong tagihan / uang kembali',
    ];

    public const JENIS_DETAIL = [
        self::JENIS_TUKAR  => 'Ganti barang: barang dikembalikan ke supplier, pengganti dikirim ke toko',
        self::JENIS_POTONG => 'Potong tagihan / uang kembali: barang dikembalikan ke supplier, tanpa pengganti',
    ];

    // Alasan retur. Keterangan tambahan per alasan akan ditambahkan nanti.
    public const ALASAN = [
        'cacat'                 => 'Cacat (dari supplier)',
        'salah_kirim'           => 'Salah kirim',
        'rusak_saat_pemasangan' => 'Rusak saat pemasangan',
        'rusak_saat_pengiriman' => 'Rusak saat pengiriman',
        'lainnya'               => 'Lainnya',
    ];

    protected $casts = [
        'nilai_potongan' => 'float',
    ];

    public function pembelian() { return $this->belongsTo(Pembelian::class, 'pembelian_id'); }
    public function detail() { return $this->hasMany(DetailReturSupplier::class, 'retur_supplier_id'); }

    /** Nomor retur internal (tidak dicetak di surat jalan), diturunkan dari id: RTR-0001, RTR-0002, dst. */
    public function getNomorReturAttribute(): string
    {
        return 'RTR-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? (string) $this->status;
    }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS[$this->jenis_retur] ?? 'Jenis belum dipilih';
    }

    public function getTanggalFormatAttribute(): string
    {
        return Carbon::parse($this->tanggal)->locale('id')->translatedFormat('d M Y');
    }

    public function getTotalUnitAttribute(): int
    {
        return (int) $this->detail->sum('jumlah');
    }
}