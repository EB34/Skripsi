<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\DetailPembelian;
use App\Models\DetailPenjualan;
use App\Models\DetailReturSupplier;
use App\Models\Pembelian;
use App\Models\ReturSupplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Operasi Modul Retur ke Supplier yang menyentuh stok dan tagihan.
 * Mewarisi StokService supaya keluarFifo(), tambahStokLokasi(), gudangTujuan(), catatMutasi() tetap jalur tunggal.
 */
class ReturSupplierService extends StokService
{
    /**
     * Tombol "Buat Retur Baru".
     * Barang stok: dibuat dari barang yang masih ada di stok layak jual, stok langsung dikurangi (FIFO).
     * Lensa custom: tidak punya stok, jadi stok tidak berubah. Retur menunjuk satu baris PO ($detailPembelianId),
     * dan HPP nota dikembalikan ke 0 karena lensanya dikembalikan ke supplier.
     */
    public function buatManual(
        int $pembelianId,
        int $barangId,
        int $jumlah,
        string $alasan,
        string $keterangan,
        string $jenis,
        ?int $userId = null,
        ?int $detailPembelianId = null
    ): ReturSupplier {
        if ($jumlah < 1) {
            throw new RuntimeException('Jumlah retur minimal 1.');
        }
        if (! array_key_exists($alasan, ReturSupplier::ALASAN)) {
            throw new RuntimeException('Alasan retur tidak valid.');
        }
        if (! array_key_exists($jenis, ReturSupplier::JENIS)) {
            throw new RuntimeException('Jenis retur tidak valid.');
        }
        if (trim($keterangan) === '') {
            throw new RuntimeException('Keterangan wajib diisi.');
        }

        return DB::transaction(function () use ($pembelianId, $barangId, $jumlah, $alasan, $keterangan, $jenis, $userId, $detailPembelianId) {
            $userId    = $userId ?? Auth::id();
            $pembelian = Pembelian::findOrFail($pembelianId);
            $barang    = Barang::findOrFail($barangId);
            $custom    = (bool) $barang->tanpa_stok;
            $baris     = null;

            if ($pembelian->status === 'menunggu_pengiriman') {
                throw new RuntimeException("PO {$pembelian->nomor_po} belum diterima, jadi belum ada barang yang bisa diretur.");
            }

            if ($custom) {
                if (! $detailPembelianId) {
                    throw new RuntimeException('Pilih lensa custom yang diretur (nota dan ukurannya).');
                }
                if ($jumlah !== 1) {
                    throw new RuntimeException('Lensa custom diretur satu biji per retur.');
                }

                $baris = DetailPembelian::lockForUpdate()->find($detailPembelianId);

                if (! $baris
                    || (int) $baris->pembelian_id !== (int) $pembelian->id
                    || (int) $baris->barang_id !== (int) $barang->id
                    || $baris->detail_penjualan_id === null) {
                    throw new RuntimeException("Lensa custom yang dipilih bukan bagian dari PO {$pembelian->nomor_po}.");
                }
                if ($baris->diretur) {
                    throw new RuntimeException('Lensa custom ini sudah diretur (potong tagihan). Pesan ulang lewat PO baru.');
                }
                if ((int) $baris->jumlah_diterima < 1) {
                    throw new RuntimeException('Lensa custom ini belum diterima dari supplier, jadi belum bisa diretur.');
                }

                $masihAda = DetailReturSupplier::where('detail_pembelian_id', $baris->id)
                    ->whereHas('retur', fn ($q) => $q->where('status', '!=', ReturSupplier::STATUS_SELESAI))
                    ->exists();
                if ($masihAda) {
                    throw new RuntimeException('Lensa custom ini masih punya retur yang belum selesai.');
                }

                // Ukuran ikut tercetak di keterangan surat jalan supaya supplier tahu lensa yang mana
                $keterangan = Str::limit(
                    trim($keterangan) . ' [Ukuran: ' . ($baris->keterangan_ukuran ?: '-') . ']',
                    255, ''
                );
            } else {
                $memuat = DetailPembelian::where('pembelian_id', $pembelian->id)
                    ->where('barang_id', $barang->id)
                    ->exists();
                if (! $memuat) {
                    throw new RuntimeException("PO {$pembelian->nomor_po} tidak memuat {$barang->kode_model}.");
                }
            }

            $retur = ReturSupplier::create([
                'pembelian_id'      => $pembelian->id,
                'tanggal'           => now()->toDateString(),
                'status'            => ReturSupplier::STATUS_DIAJUKAN,
                'jenis_retur'       => $jenis,
                'nomor_surat_jalan' => null,
            ]);

            DetailReturSupplier::create([
                'retur_supplier_id'   => $retur->id,
                'barang_id'           => $barang->id,
                'detail_pembelian_id' => $baris?->id,
                'jumlah'              => $jumlah,
                'alasan'              => $alasan,
                'keterangan'          => trim($keterangan),
            ]);

            if ($custom) {
                // Lensa dikembalikan ke supplier: HPP nota 0 lagi sampai pengganti atau PO baru diterima
                DetailPenjualan::whereKey($baris->detail_penjualan_id)->update(['hpp' => 0]);
            } else {
                // Barang keluar dari stok layak jual. Ditolak kalau stok tidak cukup.
                $this->keluarFifo(
                    $barang->id, $jumlah, 'retur_supplier', 'retur_supplier', $retur->id,
                    "Retur ke supplier {$retur->nomor_retur}: " . trim($keterangan), $userId
                );
            }

            return $retur;
        });
    }

    /**
     * Retur jenis "ganti barang": pengganti dari supplier sudah datang dan dicek.
     * $item = [detail_retur_id => ['diterima' => n, 'cacat' => n, 'keterangan' => '...']]
     * Barang stok: unit bagus masuk stok (batch FIFO baru dengan harga beli PO asal).
     * Lensa custom: tidak masuk stok, HPP nota terisi dari harga beli PO.
     * Unit cacat otomatis diajukan sebagai retur baru.
     */
    public function terimaPengganti(int $returId, array $item, ?int $userId = null): array
    {
        return DB::transaction(function () use ($returId, $item, $userId) {
            $userId = $userId ?? Auth::id();
            $retur  = ReturSupplier::with('detail')->lockForUpdate()->findOrFail($returId);

            if ($retur->status !== ReturSupplier::STATUS_DIKIRIM || $retur->jenis_retur !== ReturSupplier::JENIS_TUKAR) {
                throw new RuntimeException('Hanya retur jenis "Ganti barang" berstatus "Dikirim ke Supplier" yang bisa diisi penerimaan pengganti.');
            }

            $totalBagus  = 0;
            $totalCustom = 0;
            $cacatBaru   = [];
            $semuaNol    = true;

            foreach ($retur->detail as $d) {
                $in = $item[$d->id] ?? null;
                if (! $in) {
                    throw new RuntimeException('Data barang pada checklist tidak lengkap.');
                }

                $diterima = (int) ($in['diterima'] ?? 0);
                $cacat    = (int) ($in['cacat'] ?? 0);
                $ket      = trim((string) ($in['keterangan'] ?? ''));
                $barang   = Barang::lockForUpdate()->findOrFail($d->barang_id);

                $barisPo = $d->detail_pembelian_id ? DetailPembelian::find($d->detail_pembelian_id) : null;
                $custom  = $barisPo !== null && $barisPo->detail_penjualan_id !== null;

                if ($diterima > (int) $d->jumlah) {
                    throw new RuntimeException("{$barang->kode_model}: jumlah pengganti melebihi jumlah yang diretur ({$d->jumlah}).");
                }
                if ($cacat > $diterima) {
                    throw new RuntimeException("{$barang->kode_model}: jumlah cacat tidak boleh lebih besar dari jumlah diterima.");
                }
                if ($cacat > 0 && $ket === '') {
                    throw new RuntimeException("{$barang->kode_model}: isi keterangan cacat untuk pengganti yang rusak.");
                }

                $bagus = $diterima - $cacat;
                if ($diterima > 0) {
                    $semuaNol = false;
                }

                if ($bagus > 0) {
                    if ($custom) {
                        // Pengganti custom bagus: HPP nota terisi dari harga beli PO. Stok tidak berubah.
                        DetailPenjualan::whereKey($barisPo->detail_penjualan_id)
                            ->update(['hpp' => (float) $barisPo->harga_beli * $bagus]);
                        $totalCustom += $bagus;
                    } else {
                        $this->masukkanPengganti($barang, $bagus, $this->hargaPo((int) $retur->pembelian_id, $barang->id), $retur, $userId);
                        $totalBagus += $bagus;
                    }
                }

                $d->forceFill([
                    'jumlah_pengganti'       => $diterima,
                    'jumlah_pengganti_cacat' => $cacat,
                ])->save();

                if ($cacat > 0) {
                    if ($custom) {
                        $ket = Str::limit(trim($ket . ' [Ukuran: ' . ($barisPo->keterangan_ukuran ?: '-') . ']'), 255, '');
                    }
                    $cacatBaru[] = [
                        'barang_id'           => $barang->id,
                        'detail_pembelian_id' => $d->detail_pembelian_id,
                        'jumlah'              => $cacat,
                        'keterangan'          => $ket,
                    ];
                }
            }

            if ($semuaNol) {
                throw new RuntimeException('Belum ada barang pengganti yang diterima. Isi jumlah diterima minimal 1.');
            }

            $returBaru = null;
            if ($cacatBaru) {
                $returBaru = ReturSupplier::create([
                    'pembelian_id'      => $retur->pembelian_id,
                    'tanggal'           => now()->toDateString(),
                    'status'            => ReturSupplier::STATUS_DIAJUKAN,
                    'jenis_retur'       => ReturSupplier::JENIS_TUKAR,
                    'nomor_surat_jalan' => null,
                ]);

                foreach ($cacatBaru as $c) {
                    DetailReturSupplier::create([
                        'retur_supplier_id'   => $returBaru->id,
                        'barang_id'           => $c['barang_id'],
                        'detail_pembelian_id' => $c['detail_pembelian_id'],
                        'jumlah'              => $c['jumlah'],
                        'alasan'              => 'cacat',
                        'keterangan'          => $c['keterangan'],
                    ]);
                }
            }

            $retur->update(['status' => ReturSupplier::STATUS_SELESAI]);

            return [
                'nomor'      => $retur->nomor_retur,
                'bagus'      => $totalBagus,
                'custom'     => $totalCustom,
                'cacat'      => array_sum(array_column($cacatBaru, 'jumlah')),
                'retur_baru' => $returBaru?->nomor_retur,
            ];
        });
    }

    /**
     * Retur jenis "potong tagihan": Owner mengisi nominal potongan, retur selesai.
     * Total tagihan PO berkurang sebesar nilai ini. TIDAK dicatat sebagai kerugian operasional.
     * Untuk lensa custom: baris PO ditandai diretur, sehingga lensa itu bisa dipesan ulang lewat PO baru.
     */
    public function selesaikanPotongan(int $returId, float $nilai, ?int $userId = null): ReturSupplier
    {
        return DB::transaction(function () use ($returId, $nilai) {
            $retur = ReturSupplier::with('detail')->lockForUpdate()->findOrFail($returId);

            if ($retur->status !== ReturSupplier::STATUS_DIKIRIM || $retur->jenis_retur !== ReturSupplier::JENIS_POTONG) {
                throw new RuntimeException('Hanya retur jenis "Potong tagihan" berstatus "Dikirim ke Supplier" yang bisa diselesaikan di sini.');
            }
            if ($nilai <= 0) {
                throw new RuntimeException('Nominal potongan wajib diisi.');
            }

            // Potongan tidak boleh melebihi sisa tagihan PO (total PO dikurangi potongan retur lain)
            $totalPo = (float) DetailPembelian::where('pembelian_id', $retur->pembelian_id)
                ->get()
                ->sum(fn ($d) => (int) $d->jumlah_dipesan * (float) $d->harga_beli);

            $sudah = (float) ReturSupplier::where('pembelian_id', $retur->pembelian_id)
                ->where('id', '!=', $retur->id)
                ->sum('nilai_potongan');

            $maks = max($totalPo - $sudah, 0);
            if ($nilai > $maks) {
                throw new RuntimeException('Nominal potongan melebihi sisa tagihan PO (Rp ' . number_format($maks, 0, ',', '.') . ').');
            }

            $retur->update([
                'nilai_potongan' => $nilai,
                'status'         => ReturSupplier::STATUS_SELESAI,
            ]);

            // Lensa custom: lepaskan dari PO ini supaya bisa dipesan ulang. HPP nota tetap 0 sampai PO baru diterima.
            foreach ($retur->detail as $d) {
                if (! $d->detail_pembelian_id) {
                    continue;
                }
                $baris = DetailPembelian::lockForUpdate()->find($d->detail_pembelian_id);
                if ($baris && $baris->detail_penjualan_id !== null) {
                    $baris->update(['diretur' => true]);
                    DetailPenjualan::whereKey($baris->detail_penjualan_id)->update(['hpp' => 0]);
                }
            }

            return $retur->fresh('detail');
        });
    }

    /** Harga beli di PO asal (dipakai untuk batch barang pengganti). */
    private function hargaPo(int $pembelianId, int $barangId): float
    {
        $harga = DetailPembelian::where('pembelian_id', $pembelianId)
            ->where('barang_id', $barangId)
            ->value('harga_beli');

        $harga ??= $this->hargaBeliTerakhir($barangId);

        if ($harga === null) {
            throw new RuntimeException('Harga beli barang pengganti tidak ditemukan di PO asal.');
        }

        return (float) $harga;
    }

    /** Barang pengganti masuk gudang sebagai batch FIFO baru (bukan dari PO). */
    private function masukkanPengganti(Barang $barang, int $jumlah, float $harga, ReturSupplier $retur, ?int $userId): void
    {
        $gudang = $this->gudangTujuan();

        // forceFill: tidak bergantung pada $fillable/$guarded di model DetailPembelian
        $batch = new DetailPembelian();
        $batch->forceFill([
            'pembelian_id'     => null,
            'barang_id'        => $barang->id,
            'jumlah_dipesan'   => $jumlah,
            'jumlah_diterima'  => $jumlah,
            'jumlah_cacat'     => 0,
            'keterangan_cacat' => null,
            'harga_beli'       => $harga,
            'sisa_jumlah'      => $jumlah,
            'tanggal_masuk'    => now(),
        ])->save();

        $barang->stok_tersedia += $jumlah;
        $barang->save();

        $this->tambahStokLokasi($barang->id, $gudang->id, $jumlah);

        $this->catatMutasi(
            $barang, 'barang_masuk', $jumlah, 'retur_supplier', $retur->id,
            "Barang pengganti retur {$retur->nomor_retur}", $userId
        );
    }
}