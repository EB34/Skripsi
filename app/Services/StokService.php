<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\DetailPembelian;
use App\Models\MutasiStok;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StokService
{
    // Stok masuk dipake waktu barang dtg dr supplier
    public function barangMasuk(int $barangId, int $jumlah, ?string $refTipe = null, ?int $refId = null): void
    {
        DB::transaction(function () use ($barangId, $jumlah, $refTipe, $refId) {
            $barang = Barang::lockForUpdate()->findOrFail($barangId);

            $barang->stok_tersedia += $jumlah;
            $barang->save();

            $this->catatMutasi($barang, 'barang_masuk', $jumlah, $refTipe, $refId);
        });
    }


    // Stok keluar dengan metode FIFO. Mengembalikan total HPP untuk jumlah yang keluar.
    // Dipakai nanti oleh Penjualan, penggantian lensa, dan write off.

    public function keluarFifo(int $barangId, int $jumlah, string $jenisKejadian, ?string $refTipe = null, ?int $refId = null, ?string $keterangan = null, ?int $userId = null): float
    {
        return DB::transaction(function () use ($barangId, $jumlah, $jenisKejadian, $refTipe, $refId, $keterangan, $userId) {
            $barang = Barang::lockForUpdate()->findOrFail($barangId);

            if ($barang->stok_tersedia < $jumlah) {
                throw new RuntimeException(
                    "Stok {$barang->kode_model} tidak cukup (tersedia {$barang->stok_tersedia}, diminta {$jumlah})."
                );
            }

            // Batch paling awal diterima diambil lebih dulu
            $batches = DetailPembelian::where('barang_id', $barangId)
                ->where('sisa_jumlah', '>', 0)
                ->whereNotNull('tanggal_masuk')
                ->orderBy('tanggal_masuk')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $sisaDiminta = $jumlah;
            $hpp = 0;

            foreach ($batches as $batch) {
                if ($sisaDiminta <= 0) {
                    break;
                }
                $ambil = min($batch->sisa_jumlah, $sisaDiminta);
                $hpp += $ambil * $batch->harga_beli;
                $batch->decrement('sisa_jumlah', $ambil);
                $sisaDiminta -= $ambil;
            }

            if ($sisaDiminta > 0) {
                throw new RuntimeException("Data batch stok {$barang->kode_model} tidak konsisten dengan angka stok.");
            }

            $barang->stok_tersedia -= $jumlah;
            $barang->save();

            $this->catatMutasi($barang, $jenisKejadian, -$jumlah, $refTipe, $refId, $keterangan, $userId);

            return (float) $hpp;
        });
    }

    /**
     * Satu-satunya tempat yang menulis ke mutasi_stok.
     */
    protected function catatMutasi(Barang $barang, string $jenis, int $jumlah, ?string $refTipe, ?int $refId, ?string $keterangan = null, ?int $userId = null): void
    {
        MutasiStok::create([
            'barang_id'      => $barang->id,
            'jenis_kejadian' => $jenis,
            'jumlah'         => $jumlah,                  // negatif jika stok berkurang
            'saldo_setelah'  => $barang->stok_tersedia,
            'tanggal'        => now(),
            'referensi_tipe' => $refTipe,
            'referensi_id'   => $refId,
            'keterangan'     => $keterangan,
            'user_id'        => $userId
        ]);
    }

      /**
     * Penyesuaian stok (opname): menyamakan stok sistem dengan hasil hitung fisik.
     * Hanya boleh MENGURANGI stok. Pemotongan batch memakai keluarFifo().
     * Mengembalikan total HPP barang yang dikurangi (nilai kerugian opname).
     */
    public function sesuaikan(int $barangId, int $jumlahFisik, string $alasan, ?int $userId = null): float
    {
        return DB::transaction(function () use ($barangId, $jumlahFisik, $alasan, $userId) {
            $barang = Barang::lockForUpdate()->findOrFail($barangId);

            if ($jumlahFisik < 0) {
                throw new RuntimeException('Jumlah fisik tidak boleh negatif.');
            }

            $selisih = $barang->stok_tersedia - $jumlahFisik; // positif = stok berkurang

            if ($selisih < 0) {
                throw new RuntimeException(
                    "Jumlah fisik ({$jumlahFisik}) lebih besar dari stok sistem ({$barang->stok_tersedia}). "
                    . 'Opname hanya boleh mengurangi stok.'
                );
            }

            if ($selisih === 0) {
                throw new RuntimeException('Jumlah fisik sama dengan stok sistem, tidak ada yang disesuaikan.');
            }

            return $this->keluarFifo($barangId, $selisih, 'penyesuaian', 'opname', null, $alasan, $userId);
        });
    }
}