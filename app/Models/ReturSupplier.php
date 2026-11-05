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

    // Alasan retur. Keterangan tambahan per alasan akan ditambahkan nanti.
    public const ALASAN = [
        'cacat'                 => 'Cacat (dari supplier)',
        'salah_kirim'           => 'Salah kirim',
        'rusak_saat_pemasangan' => 'Rusak saat pemasangan',
        'rusak_saat_pengiriman' => 'Rusak saat pengiriman',
        'lainnya'               => 'Lainnya',
    ];

    public function pembelian() { return $this->belongsTo(Pembelian::class, 'pembelian_id'); }
    public function detail() { return $this->hasMany(DetailReturSupplier::class, 'retur_supplier_id'); }

    /** Nomor nota retur diturunkan dari id (tidak disimpan di database): RTR-0001, RTR-0002, dst. */
    public function getNomorReturAttribute(): string
    {
        return 'RTR-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? (string) $this->status;
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