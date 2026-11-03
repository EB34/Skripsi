<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $table = 'penjualan';
    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public const STATUS = [
        'berjalan' => 'Berjalan',
        'selesai'  => 'Selesai',
        'batal'    => 'Dibatalkan',
    ];

    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function kasir() { return $this->belongsTo(User::class, 'user_id'); }
    public function detail() { return $this->hasMany(DetailPenjualan::class, 'penjualan_id'); }
    public function returCustomer() { return $this->hasMany(ReturCustomer::class, 'penjualan_id'); }
    public function pembayaran() { return $this->hasMany(PembayaranPenjualan::class, 'penjualan_id'); }
    public function pesanan() { return $this->hasOne(PesananKacamata::class, 'penjualan_id'); }

    // Subtotal sebelum diskon = total yang tersimpan + diskon
    public function getSubtotalAttribute(): float
    {
        return (float) $this->total + (float) $this->diskon;
    }

    // Total yang sudah dibayar. Memakai hasil withSum('pembayaran', 'jumlah') kalau ada (hemat query di daftar).
    public function getTotalDibayarAttribute(): float
    {
        $jumlah = $this->attributes['pembayaran_sum_jumlah'] ?? null;

        return (float) ($jumlah ?? $this->pembayaran()->sum('jumlah'));
    }

    // Sisa yang belum dibayar (piutang). Nota batal tidak punya sisa: DP hangus.
    public function getSisaBayarAttribute(): float
    {
        if ($this->status === 'batal') {
            return 0.0;
        }

        return max((float) $this->total - $this->total_dibayar, 0.0);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? (string) $this->status;
    }
}