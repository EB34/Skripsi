<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\DetailPembelian;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Input stok masuk dengan tanggal/jam yang ditentukan sendiri (stok awal / data susulan).
 * Memakai penulis yang sama dengan StokService (tambahStokLokasi, gudangTujuan, catatMutasi).
 */
class StokImportService extends StokService
{
    public function masukkan(
        int $barangId,
        int $jumlah,
        float $hargaBeli,
        DateTimeInterface $tanggal,
        ?int $userId = null
    ): void {
        if ($jumlah < 1) {
            throw new RuntimeException('Jumlah minimal 1.');
        }
        if ($hargaBeli < 0) {
            throw new RuntimeException('Harga beli tidak boleh negatif.');
        }

        DB::transaction(function () use ($barangId, $jumlah, $hargaBeli, $tanggal, $userId) {
            $barang = Barang::lockForUpdate()->findOrFail($barangId);

            if ($barang->tanpa_stok) {
                throw new RuntimeException("{$barang->kode_model} adalah lensa custom (tanpa stok).");
            }

            $gudang = $this->gudangTujuan();

            // Batch FIFO baru, bukan dari PO. tanggal_masuk = tanggal yang Anda tentukan.
            $batch = new DetailPembelian();
            $batch->forceFill([
                'pembelian_id'     => null,
                'barang_id'        => $barang->id,
                'jumlah_dipesan'   => $jumlah,
                'jumlah_diterima'  => $jumlah,
                'jumlah_cacat'     => 0,
                'keterangan_cacat' => null,
                'harga_beli'       => $hargaBeli,
                'sisa_jumlah'      => $jumlah,
                'tanggal_masuk'    => $tanggal,
            ])->save();

            $barang->stok_tersedia += $jumlah;
            $barang->save();

            $this->tambahStokLokasi($barang->id, $gudang->id, $jumlah);

            $this->catatMutasi(
                $barang, 'barang_masuk', $jumlah, null, null,
                'Input stok via Excel', $userId, $tanggal
            );
        });
    }
}