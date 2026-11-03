<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\DetailPembelian;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tambahan operasi stok khusus pesanan kacamata.
 * Mewarisi StokService supaya semua perubahan stok tetap memakai penulis yang sama
 * (catatMutasi, tambahStokLokasi, gudangTujuan).
 */
class StokPesananService extends StokService
{
    /**
     * Mengembalikan barang yang sudah terjual ke stok (frame dari pesanan yang dibatalkan).
     * Dibuat batch FIFO baru (bukan dari PO) dengan harga beli = HPP per unit saat dijual,
     * masuk ke gudang, dan stok_lokasi ikut bertambah sehingga totalnya tetap sama dengan stok_tersedia.
     */
    public function kembalikanKeStok(
        int $barangId,
        int $jumlah,
        float $hargaBeli,
        ?string $refTipe = null,
        ?int $refId = null,
        ?string $keterangan = null,
        ?int $userId = null
    ): void {
        if ($jumlah < 1) {
            throw new RuntimeException('Jumlah yang dikembalikan ke stok minimal 1.');
        }
        if ($hargaBeli < 0) {
            throw new RuntimeException('Harga beli tidak boleh negatif.');
        }

        DB::transaction(function () use ($barangId, $jumlah, $hargaBeli, $refTipe, $refId, $keterangan, $userId) {
            $barang = Barang::lockForUpdate()->findOrFail($barangId);
            $gudang = $this->gudangTujuan();   // ditolak kalau belum ada lokasi gudang

            // forceFill: tidak bergantung pada $fillable/$guarded di model DetailPembelian
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
                'tanggal_masuk'    => now(),
            ])->save();

            $barang->stok_tersedia += $jumlah;
            $barang->save();

            $this->tambahStokLokasi($barang->id, $gudang->id, $jumlah);

            // 'penyesuaian' bertanda +, referensi ke nota supaya No Referensi tampil nomor nota
            $this->catatMutasi($barang, 'penyesuaian', $jumlah, $refTipe, $refId, $keterangan, $userId);
        });
    }
}