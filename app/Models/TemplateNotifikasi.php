<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateNotifikasi extends Model
{
    protected $table = 'template_notifikasi';

    protected $fillable = ['kode', 'nama', 'isi'];

    /** Penanda yang boleh dipakai di isi template. */
    public const PENANDA = [
        'nama'            => 'Nama pelanggan',
        'nomor_pesanan'   => 'Nomor pesanan',
        'nomor_nota'      => 'Nomor nota',
        'jumlah_kacamata' => 'Jumlah kacamata',
        'sisa_bayar'      => 'Sisa bayar (angka saja, contoh 350.000)',
        'nama_toko'       => 'Nama toko',
    ];

    /** Contoh data untuk pratinjau di halaman edit. */
    public const CONTOH = [
        'nama'            => 'Budi Santoso',
        'nomor_pesanan'   => 'ORD-04112026-001',
        'nomor_nota'      => 'INV-04112026-001',
        'jumlah_kacamata' => '2',
        'sisa_bayar'      => '350.000',
        'nama_toko'       => 'Solo Optik',
    ];

    /** Daftar template beserta teks bawaan dan penanda yang wajib ada. */
    public const DAFTAR = [
        'siap_diambil_lunas' => [
            'nama' => 'Siap diambil (sudah lunas)',
            'keterangan' => 'Dikirim saat semua kacamata dalam pesanan siap diambil dan pembayaran sudah lunas.',
            'wajib' => [],
            'isi' => "Halo {nama}, pesanan kacamata Anda ({nomor_pesanan}) sudah selesai dan siap diambil di {nama_toko}. Pembayaran sudah lunas. Terima kasih.",
        ],
        'siap_diambil_sisa' => [
            'nama' => 'Siap diambil (ada sisa bayar / DP)',
            'keterangan' => 'Dikirim saat semua kacamata siap diambil dan pelanggan masih harus melunasi sisa pembayaran.',
            'wajib' => ['sisa_bayar'],
            'isi' => "Halo {nama}, pesanan kacamata Anda ({nomor_pesanan}) sudah selesai dan siap diambil di {nama_toko}. Mohon lunasi sisa pembayaran sebesar Rp {sisa_bayar} saat pengambilan. Terima kasih.",
        ],
    ];

    /** Ambil template; kalau barisnya belum ada, dibuat dari teks bawaan. */
    public static function ambil(string $kode): self
    {
        $def = self::DAFTAR[$kode];

        return self::firstOrCreate(
            ['kode' => $kode],
            ['nama' => $def['nama'], 'isi' => $def['isi']]
        );
    }

    /** Isi pesan akhir: penanda diganti data asli. Dipakai oleh pemicu notifikasi. */
    public static function render(string $kode, array $data): string
    {
        $data = array_merge(['nama_toko' => 'Solo Optik'], $data);
        $isi = self::ambil($kode)->isi;

        foreach ($data as $kunci => $nilai) {
            $isi = str_replace('{' . $kunci . '}', (string) $nilai, $isi);
        }

        return $isi;
    }
}