<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\KerugianOperasional;
use App\Models\PenggantianKesalahanToko;
use App\Models\Penjualan;
use App\Models\PesananKacamata;
use App\Models\ReturCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Operasi stok dan kerugian untuk Modul Retur.
 * Mewarisi StokService supaya keluarFifo() dan catatKerugian() tetap jadi jalur tunggal.
 */
class ReturService extends StokService
{
    /**
     * Owner memproses retur frame cacat dari customer.
     *  - tukar_supplier  : hanya kalau supplier masih punya stok. Stok toko tidak berubah.
     *  - tukar_stok_toko : frame pengganti keluar dari stok (FIFO), HPP-nya dicatat sebagai kerugian.
     *  - refund          : nominal disimpan di retur, stok tidak berubah.
     */
    public function prosesReturCustomer(
        int $returId,
        string $konfirmasi,
        string $tindak,
        ?int $penggantiId = null,
        ?float $refund = null,
        ?string $catatan = null,
        ?int $userId = null
    ): ReturCustomer {
        return DB::transaction(function () use ($returId, $konfirmasi, $tindak, $penggantiId, $refund, $catatan, $userId) {
            $retur  = ReturCustomer::lockForUpdate()->findOrFail($returId);
            $userId = $userId ?? Auth::id();

            if ($retur->status !== ReturCustomer::STATUS_DICATAT) {
                throw new RuntimeException("{$retur->nomor_retur} sudah diproses sebelumnya.");
            }
            if ($tindak === 'tukar_supplier' && $konfirmasi !== 'supplier_tersedia') {
                throw new RuntimeException('Tukar dari supplier hanya bisa dipilih kalau supplier masih memiliki stok atau model masih diproduksi.');
            }

            $data = [
                'status'            => ReturCustomer::STATUS_SELESAI,
                'status_konfirmasi' => $konfirmasi,
                'tindak_lanjut'     => $tindak,
                'catatan_owner'     => $catatan,
                'diproses_oleh'     => $userId,
                'diproses_pada'     => now(),
            ];

            if ($tindak === 'tukar_stok_toko') {
                if (! $penggantiId) {
                    throw new RuntimeException('Pilih frame pengganti dari stok toko.');
                }

                $pengganti = Barang::findOrFail($penggantiId);
                if (strtolower((string) $pengganti->jenis) !== 'frame') {
                    throw new RuntimeException('Barang pengganti harus berjenis frame.');
                }

                // Referensi ke nota supaya Riwayat Mutasi menampilkan nomor nota
                $hpp = $this->keluarFifo(
                    $pengganti->id, 1, 'penyesuaian', 'penjualan', $retur->penjualan_id,
                    "Frame pengganti retur customer {$retur->nomor_retur}", $userId
                );

                $this->catatKerugian(
                    KerugianOperasional::JENIS_RETUR_CUSTOMER,
                    $pengganti->id, 1, $hpp,
                    'retur_customer', $retur->id,
                    "Frame pengganti retur customer {$retur->nomor_retur}", $userId
                );

                $data['barang_pengganti_id'] = $pengganti->id;
                $data['nilai_kerugian']      = $hpp;
            } elseif ($tindak === 'refund') {
                $nota    = Penjualan::findOrFail($retur->penjualan_id);
                $dibayar = (float) $nota->pembayaran()->sum('jumlah');

                if ($refund === null || $refund <= 0) {
                    throw new RuntimeException('Nominal refund wajib diisi.');
                }
                if ($refund > $dibayar) {
                    throw new RuntimeException('Nominal refund melebihi total yang dibayar pada nota (Rp ' . number_format($dibayar, 0, ',', '.') . ').');
                }

                $data['jumlah_refund'] = $refund;
            }

            $retur->update($data);

            return $retur->fresh();
        });
    }

    /**
     * Mencatat penggantian lensa akibat kesalahan pengerjaan toko.
     * Lensa lama = lensa pada pesanan (di-write off, stok tidak berubah karena sudah keluar saat nota dibuat).
     * Lensa pengganti keluar dari stok (FIFO), kerugian = HPP lensa pengganti.
     */
    public function catatKesalahanToko(
        int $pesananId,
        string $jenisKesalahan,
        int $lensaPenggantiId,
        int $jumlah,
        ?string $keterangan = null,
        ?int $userId = null
    ): PenggantianKesalahanToko {
        return DB::transaction(function () use ($pesananId, $jenisKesalahan, $lensaPenggantiId, $jumlah, $keterangan, $userId) {
            $pesanan = PesananKacamata::lockForUpdate()->findOrFail($pesananId);
            $userId  = $userId ?? Auth::id();

            if (! in_array($pesanan->status, ['dikerjakan', 'siap_diambil', 'selesai'], true)) {
                throw new RuntimeException("Pesanan {$pesanan->nomor_pesanan} belum/tidak dalam tahap pengerjaan, jadi tidak bisa dicatat kesalahan pengerjaannya.");
            }
            if (! $pesanan->lensa_id) {
                throw new RuntimeException('Pesanan ini memakai lensa lama milik pelanggan, bukan lensa toko, jadi tidak ada lensa toko yang diganti.');
            }
            if ($jumlah < 1 || $jumlah > (int) $pesanan->jumlah_lensa) {
                throw new RuntimeException("Jumlah lensa yang diganti harus antara 1 dan {$pesanan->jumlah_lensa} (jumlah lensa pada pesanan).");
            }

            $pengganti = Barang::findOrFail($lensaPenggantiId);
            if (strtolower((string) $pengganti->jenis) !== 'lensa') {
                throw new RuntimeException('Barang pengganti harus berjenis lensa.');
            }

            // Dibuat dulu supaya id-nya bisa jadi nomor KST di keterangan mutasi
            $catatan = PenggantianKesalahanToko::create([
                'pesanan_id'         => $pesanan->id,
                'jenis_kesalahan'    => $jenisKesalahan,
                'lensa_writeoff_id'  => $pesanan->lensa_id,
                'lensa_pengganti_id' => $pengganti->id,
                'jumlah'             => $jumlah,
                'nilai_kerugian'     => 0,
                'keterangan'         => $keterangan,
                'user_id'            => $userId,
            ]);

            $hpp = $this->keluarFifo(
                $pengganti->id, $jumlah, 'penyesuaian', 'penjualan', $pesanan->penjualan_id,
                "Lensa pengganti kesalahan toko {$catatan->nomor}, pesanan {$pesanan->nomor_pesanan}", $userId
            );

            $catatan->update(['nilai_kerugian' => $hpp]);

            $this->catatKerugian(
                KerugianOperasional::JENIS_KESALAHAN_TOKO,
                $pengganti->id, $jumlah, $hpp,
                'kesalahan_toko', $catatan->id,
                "{$catatan->nomor}: " . (PenggantianKesalahanToko::JENIS[$jenisKesalahan] ?? $jenisKesalahan)
                    . ", pesanan {$pesanan->nomor_pesanan}",
                $userId
            );

            return $catatan->fresh();
        });
    }
}