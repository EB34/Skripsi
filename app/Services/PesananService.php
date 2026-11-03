<?php

namespace App\Services;

use App\Models\Penjualan;
use App\Models\PesananKacamata;
use App\Models\Refraksi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PesananService
{
    public const SISI = ['od', 'os'];
    public const KOLOM_UKURAN = ['sphere', 'cylinder', 'axis', 'add', 'mono_pd'];

    /** Aturan validasi isian pesanan (nama field: pesanan[...]). */
    public static function aturan(): array
    {
        $aturan = [
            'pesanan.refraksi_id'      => ['nullable', 'exists:refraksi,id'],
            'pesanan.estimasi_selesai' => ['nullable', 'date', 'after_or_equal:today'],
            'pesanan.keterangan'       => ['nullable', 'string', 'max:255'],
            'pesanan.pd_total'         => ['nullable', 'numeric', 'between:40,90'],
        ];

        foreach (self::SISI as $s) {
            $aturan["pesanan.{$s}_sphere"]   = ['nullable', 'numeric', 'between:-20,20'];
            $aturan["pesanan.{$s}_cylinder"] = ['nullable', 'numeric', 'between:-10,10'];
            $aturan["pesanan.{$s}_axis"]     = ['nullable', 'integer', 'between:0,180'];
            $aturan["pesanan.{$s}_add"]      = ['nullable', 'numeric', 'between:0,10'];
            $aturan["pesanan.{$s}_mono_pd"]  = ['nullable', 'numeric', 'between:10,50'];
        }

        return $aturan;
    }

    /** Nama field dalam bahasa Indonesia untuk pesan error validasi. */
    public static function namaAtribut(): array
    {
        $nama = [
            'pesanan.refraksi_id'      => 'Data refraksi',
            'pesanan.estimasi_selesai' => 'Estimasi selesai',
            'pesanan.keterangan'       => 'Keterangan pesanan',
            'pesanan.pd_total'         => 'PD total',
        ];
        $sisi  = ['od' => 'mata kanan (OD)', 'os' => 'mata kiri (OS)'];
        $kolom = ['sphere' => 'Sphere', 'cylinder' => 'Cylinder', 'axis' => 'Axis', 'add' => 'Add', 'mono_pd' => 'PD'];

        foreach ($sisi as $s => $labelSisi) {
            foreach ($kolom as $k => $labelKolom) {
                $nama["pesanan.{$s}_{$k}"] = "{$labelKolom} {$labelSisi}";
            }
        }

        return $nama;
    }

    /**
     * Riwayat refraksi tiap pelanggan untuk dropdown "ambil ukuran dari refraksi".
     * Kembalian: [customer_id => [['id', 'label', 'nilai' => [...]], ...]]
     */
    public static function refraksiPerCustomer(Collection $customers): array
    {
        $hasil = [];

        foreach ($customers as $c) {
            $hasil[$c->id] = $c->refraksi->map(function ($r) {
                $nilai = [];
                foreach (self::SISI as $s) {
                    foreach (self::KOLOM_UKURAN as $k) {
                        $nilai["{$s}_{$k}"] = $r->{"{$s}_{$k}"};
                    }
                }
                $nilai['pd_total'] = $r->pd_total;

                return [
                    'id'    => $r->id,
                    'label' => $r->tanggal_periksa->format('d/m/Y')
                        . ' — OD ' . self::fmt($r->od_sphere) . '/' . self::fmt($r->od_cylinder) . '/' . ($r->od_axis ?? '-')
                        . ' · OS ' . self::fmt($r->os_sphere) . '/' . self::fmt($r->os_cylinder) . '/' . ($r->os_axis ?? '-'),
                    'nilai' => $nilai,
                ];
            })->values()->all();
        }

        return $hasil;
    }

    private static function fmt($v): string
    {
        if ($v === null || $v === '') {
            return '-';
        }
        $teks = rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

        return ((float) $v > 0 ? '+' : '') . $teks;
    }

    private static function bersih($v)
    {
        return ($v === '' || $v === null) ? null : $v;
    }

    /**
     * Membuat pesanan dari sebuah nota. Frame dan lensa diambil dari baris nota;
     * kalau tidak ada di nota, berarti milik pelanggan (bawaan).
     * Melempar RuntimeException kalau aturan dilanggar (pemanggil menangkapnya).
     */
    public function buatDariNota(Penjualan $penjualan, array $data, ?int $userId = null): PesananKacamata
    {
        return DB::transaction(function () use ($penjualan, $data, $userId) {
            $nota = Penjualan::with('detail.barang')->lockForUpdate()->findOrFail($penjualan->id);

            if ($nota->status !== 'berjalan') {
                $label = strtolower(Penjualan::STATUS[$nota->status] ?? $nota->status);
                throw new RuntimeException("Nota {$nota->nomor_nota} berstatus {$label}, tidak bisa dibuatkan pesanan.");
            }
            if (PesananKacamata::where('penjualan_id', $nota->id)->exists()) {
                throw new RuntimeException("Nota {$nota->nomor_nota} sudah punya pesanan.");
            }
            if (! $nota->customer_id) {
                throw new RuntimeException('Nota tanpa pelanggan tidak bisa dibuatkan pesanan.');
            }

            $frame = $nota->detail->filter(fn ($d) => strtolower((string) $d->barang->jenis) === 'frame');
            $lensa = $nota->detail->filter(fn ($d) => strtolower((string) $d->barang->jenis) === 'lensa');

            if ($frame->isEmpty() && $lensa->isEmpty()) {
                throw new RuntimeException("Nota {$nota->nomor_nota} tidak berisi frame atau lensa, jadi tidak bisa dibuatkan pesanan.");
            }
            if ($frame->count() > 1 || $frame->sum('jumlah') > 1) {
                throw new RuntimeException('Pesanan hanya bisa dibuat untuk nota yang berisi satu frame.');
            }
            if ($lensa->count() > 1 || $lensa->sum('jumlah') > 2) {
                throw new RuntimeException('Pesanan hanya bisa dibuat untuk nota yang berisi satu jenis lensa dengan jumlah 1 atau 2 biji.');
            }

            $jumlahLensa = (int) $lensa->sum('jumlah');

            // Data refraksi hanya acuan, tapi harus milik pelanggan di nota
            $refraksiId = self::bersih($data['refraksi_id'] ?? null);
            if ($refraksiId !== null) {
                $milikPelanggan = Refraksi::where('id', $refraksiId)->where('customer_id', $nota->customer_id)->exists();
                if (! $milikPelanggan) {
                    throw new RuntimeException('Data refraksi yang dipilih bukan milik pelanggan di nota ini.');
                }
            }

            $ukuran = [];
            foreach (self::SISI as $s) {
                foreach (self::KOLOM_UKURAN as $k) {
                    $ukuran["{$s}_{$k}"] = self::bersih($data["{$s}_{$k}"] ?? null);
                }
            }
            $ukuran['pd_total'] = self::bersih($data['pd_total'] ?? null);

            // Lensa dari toko: ukuran wajib sesuai jumlah biji. Lensa lama: ukuran boleh kosong.
            if ($jumlahLensa > 0) {
                $sisiTerisi = collect(self::SISI)->filter(
                    fn ($s) => $ukuran["{$s}_sphere"] !== null || $ukuran["{$s}_cylinder"] !== null
                )->count();

                if ($sisiTerisi !== $jumlahLensa) {
                    throw new RuntimeException(
                        $jumlahLensa === 1
                            ? 'Lensa berjumlah 1 biji, jadi ukuran diisi untuk satu sisi mata saja (kanan atau kiri).'
                            : 'Lensa berjumlah 2 biji, jadi ukuran mata kanan (OD) dan mata kiri (OS) harus diisi.'
                    );
                }
            }

            $urut  = PesananKacamata::whereDate('created_at', now()->toDateString())->count() + 1;
            $nomor = 'ORD-' . now()->format('dmY') . '-' . $urut;

            return PesananKacamata::create(array_merge($ukuran, [
                'nomor_pesanan'    => $nomor,
                'penjualan_id'     => $nota->id,
                'customer_id'      => $nota->customer_id,
                'refraksi_id'      => $refraksiId,
                'frame_id'         => $frame->first()?->barang_id,
                'lensa_id'         => $lensa->first()?->barang_id,
                'jumlah_lensa'     => $jumlahLensa,
                'dibuat_oleh'      => $userId,
                'status'           => 'menunggu',
                'estimasi_selesai' => self::bersih($data['estimasi_selesai'] ?? null),
                'keterangan'       => self::bersih($data['keterangan'] ?? null),
            ]));
        });
    }
}