<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\BarangRusak;
use App\Models\DetailPembelian;
use App\Models\DetailReturSupplier;
use App\Models\KerugianOperasional;
use App\Models\Lokasi;
use App\Models\MutasiStok;
use App\Models\Pembelian;
use App\Models\ReturSupplier;
use App\Models\StokLokasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StokService
{
    // Stok masuk dipake waktu barang dtg dr supplier. Barang masuk ke lokasi gudang.
    public function barangMasuk(int $barangId, int $jumlah, ?string $refTipe = null, ?int $refId = null): void
    {
        DB::transaction(function () use ($barangId, $jumlah, $refTipe, $refId) {
            $barang = Barang::lockForUpdate()->findOrFail($barangId);

            $barang->stok_tersedia += $jumlah;
            $barang->save();

            $gudang = $this->gudangTujuan();
            $this->tambahStokLokasi($barang->id, $gudang->id, $jumlah);

            $this->catatMutasi($barang, 'barang_masuk', $jumlah, $refTipe, $refId);
        });
    }


    // Stok keluar dengan metode FIFO. Mengembalikan total HPP untuk jumlah yang keluar.
    // Dipakai oleh Penjualan, penggantian lensa, barang rusak, dan penyesuaian stok (opname).
    public function keluarFifo(
        int $barangId,
        int $jumlah,
        string $jenisKejadian,
        ?string $refTipe = null,
        ?int $refId = null,
        ?string $keterangan = null,
        ?int $userId = null
    ): float {
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

            // Kurangi juga stok per lokasi supaya totalnya tetap sama dengan stok_tersedia
            $this->kurangiStokLokasi($barang, $jumlah);

            $this->catatMutasi($barang, $jenisKejadian, -$jumlah, $refTipe, $refId, $keterangan, $userId);

            return (float) $hpp;
        });
    }

    /**
     * Pindah barang antar lokasi (misal gudang ke etalase/laci).
     * Bukan FIFO: jumlahnya sesuai keputusan owner. Batch dan HPP tidak berubah,
     * total stok tidak berubah, jadi mutasi dicatat dengan jumlah 0.
     */
    public function pindahLokasi(
        int $barangId,
        int $dariLokasiId,
        int $keLokasiId,
        int $jumlah,
        ?string $catatan = null,
        ?int $userId = null
    ): void {
        if ($jumlah < 1) {
            throw new RuntimeException('Jumlah yang dipindah minimal 1.');
        }
        if ($dariLokasiId === $keLokasiId) {
            throw new RuntimeException('Lokasi asal dan tujuan tidak boleh sama.');
        }

        DB::transaction(function () use ($barangId, $dariLokasiId, $keLokasiId, $jumlah, $catatan, $userId) {
            // Kunci baris barang supaya pemindahan dan penjualan pada barang yang sama berjalan bergantian
            $barang = Barang::lockForUpdate()->findOrFail($barangId);
            $asal   = Lokasi::findOrFail($dariLokasiId);
            $tujuan = Lokasi::findOrFail($keLokasiId);

            $baris = StokLokasi::where('barang_id', $barangId)
                ->where('lokasi_id', $dariLokasiId)
                ->lockForUpdate()
                ->first();

            $ada = $baris?->jumlah ?? 0;

            if ($ada < $jumlah) {
                throw new RuntimeException(
                    "Stok {$barang->kode_model} di {$asal->nama} hanya {$ada}, tidak cukup untuk dipindah {$jumlah}."
                );
            }

            $baris->decrement('jumlah', $jumlah);
            $this->tambahStokLokasi($barangId, $keLokasiId, $jumlah);

            $keterangan = "{$asal->nama} → {$tujuan->nama}, {$jumlah} pcs" . ($catatan ? " ({$catatan})" : '');

            $this->catatMutasi($barang, 'pindah_lokasi', 0, null, null, $keterangan, $userId);
        });
    }

    /**
     * Penyesuaian stok (opname): menyamakan stok sistem dengan hasil hitung fisik.
     *
     * - Fisik lebih kecil dari sistem: stok dikurangi lewat keluarFifo() (batch tertua dulu),
     *   dan nilainya dicatat sebagai kerugian operasional (selisih opname).
     * - Fisik lebih besar dari sistem: dibuat batch FIFO baru (bukan dari PO) dengan harga beli
     *   dari $hargaBeli, atau dari batch terbaru barang itu kalau $hargaBeli kosong.
     *
     * Mengembalikan ['arah' => 'tambah'|'kurang', 'jumlah' => selisih, 'nilai' => nilai persediaan yang berubah].
     */
    public function sesuaikan(
        int $barangId,
        int $jumlahFisik,
        string $alasan,
        ?int $userId = null,
        ?float $hargaBeli = null
    ): array {
        return DB::transaction(function () use ($barangId, $jumlahFisik, $alasan, $userId, $hargaBeli) {
            $barang = Barang::lockForUpdate()->findOrFail($barangId);

            if ($jumlahFisik < 0) {
                throw new RuntimeException('Jumlah fisik tidak boleh negatif.');
            }

            $selisih = $jumlahFisik - (int) $barang->stok_tersedia; // positif = stok bertambah

            if ($selisih === 0) {
                throw new RuntimeException('Jumlah fisik sama dengan stok sistem, tidak ada yang disesuaikan.');
            }

            if ($selisih < 0) {
                $hpp = $this->keluarFifo($barangId, -$selisih, 'penyesuaian', 'opname', null, $alasan, $userId);

                // Barang fisik hilang/kurang = kerugian sebesar HPP-nya
                $this->catatKerugian(
                    KerugianOperasional::JENIS_SELISIH_OPNAME,
                    $barangId,
                    -$selisih,
                    $hpp,
                    'opname',
                    null,
                    $alasan,
                    $userId
                );

                return ['arah' => 'kurang', 'jumlah' => -$selisih, 'nilai' => $hpp];
            }

            return $this->tambahBatchPenyesuaian($barang, $selisih, $alasan, $hargaBeli, $userId);
        });
    }

    /**
     * Harga beli per unit dari batch terbaru barang (untuk mengisi form koreksi).
     * Null kalau barang belum pernah diterima dari PO.
     */
    public function hargaBeliTerakhir(int $barangId): ?float
    {
        $harga = DetailPembelian::where('barang_id', $barangId)
            ->whereNotNull('tanggal_masuk')
            ->orderByDesc('tanggal_masuk')
            ->orderByDesc('id')
            ->value('harga_beli');

        return $harga === null ? null : (float) $harga;
    }

    /**
     * Lapor barang rusak/cacat: barang keluar dari stok layak jual (FIFO),
     * masuk ke stok_rusak, dan menunggu keputusan (retur supplier atau write off).
     */
    public function laporRusak(int $barangId, int $jumlah, string $alasan, ?int $userId = null): BarangRusak
    {
        if ($jumlah < 1) {
            throw new RuntimeException('Jumlah barang rusak minimal 1.');
        }

        return DB::transaction(function () use ($barangId, $jumlah, $alasan, $userId) {
            // Laporan dibuat dulu supaya id-nya bisa jadi referensi mutasi.
            $laporan = BarangRusak::create([
                'barang_id' => $barangId,
                'jumlah'    => $jumlah,
                'alasan'    => $alasan,
                'status'    => 'menunggu_keputusan',
                'nilai_hpp' => 0,
                'user_id'   => $userId ?? Auth::id(),
            ]);

            // Cek stok, potong batch FIFO, kurangi stok_tersedia dan stok lokasi, catat mutasi.
            $hpp = $this->keluarFifo(
                $barangId, $jumlah, 'barang_rusak', 'barang_rusak', $laporan->id, $alasan, $userId
            );

            $laporan->update(['nilai_hpp' => $hpp]);
            Barang::where('id', $barangId)->increment('stok_rusak', $jumlah);

            return $laporan;
        });
    }

    /**
     * Keputusan tindak lanjut barang rusak oleh Owner: 'retur_supplier' atau 'write_off'.
     *
     * - retur_supplier: membuat ReturSupplier (status diajukan) dan DetailReturSupplier
     *   dengan PO asal $pembelianId dan alasan $alasanRetur. Menunggu validasi Owner
     *   di tab Retur ke Supplier, yang menerbitkan surat jalan.
     * - write_off: dicatat sebagai kerugian operasional senilai nilai_hpp laporan.
     *
     * Stok layak jual tidak berubah di kedua kasus, karena sudah dikurangi saat laporRusak().
     * Mengembalikan ReturSupplier yang dibuat, atau null untuk write off.
     */
    public function putuskanRusak(
        int $laporanId,
        string $keputusan,
        ?int $userId = null,
        ?int $pembelianId = null,
        ?string $alasanRetur = null
    ): ?ReturSupplier {
        if (!in_array($keputusan, ['retur_supplier', 'write_off'], true)) {
            throw new RuntimeException('Keputusan tidak valid.');
        }

        return DB::transaction(function () use ($laporanId, $keputusan, $userId, $pembelianId, $alasanRetur) {
            $laporan = BarangRusak::lockForUpdate()->findOrFail($laporanId);

            if ($laporan->status !== 'menunggu_keputusan') {
                throw new RuntimeException('Laporan ini sudah diputuskan sebelumnya.');
            }

            $barang = Barang::lockForUpdate()->findOrFail($laporan->barang_id);

            if ($barang->stok_rusak < $laporan->jumlah) {
                throw new RuntimeException("Data stok rusak {$barang->kode_model} tidak konsisten dengan laporan.");
            }

            $retur = null;
            $dataLaporan = [];

            if ($keputusan === 'retur_supplier') {
                $retur = $this->buatReturSupplier($barang, (int) $laporan->jumlah, $pembelianId, $alasanRetur);
                $dataLaporan['retur_supplier_id'] = $retur->id;
            }

            $barang->stok_rusak -= $laporan->jumlah;
            $barang->save();

            $laporan->update(array_merge($dataLaporan, [
                'status'          => $keputusan,
                'diputuskan_pada' => now(),
            ]));

            $label = $keputusan === 'write_off' ? 'Write off' : 'Retur ke supplier';
            $keterangan = "{$label} {$laporan->jumlah} pcs ({$laporan->alasan})";
            if ($retur) {
                $keterangan .= ", {$retur->nomor_retur}";
            }

            $this->catatMutasi(
                $barang,
                $keputusan,
                0,
                'barang_rusak',
                $laporan->id,
                $keterangan,
                $userId
            );

            // Hanya write off yang rugi. Retur ke supplier belum tentu rugi, jadi tidak dicatat di sini.
            if ($keputusan === 'write_off') {
                $this->catatKerugian(
                    KerugianOperasional::JENIS_WRITE_OFF_RUSAK,
                    $barang->id,
                    (int) $laporan->jumlah,
                    (float) $laporan->nilai_hpp,
                    'barang_rusak',
                    $laporan->id,
                    $laporan->alasan,
                    $userId
                );
            }

            return $retur;
        });
    }

    /**
     * Membuat retur ke supplier (header dan satu baris detail) untuk barang rusak yang diputuskan retur.
     * Memastikan PO yang dipilih memang memuat barang tersebut, supaya surat jalan tidak salah PO.
     */
    protected function buatReturSupplier(
        Barang $barang,
        int $jumlah,
        ?int $pembelianId,
        ?string $alasanRetur
    ): ReturSupplier {
        if (!$pembelianId) {
            throw new RuntimeException('Pilih PO asal untuk retur ke supplier.');
        }

        if (!$alasanRetur || !array_key_exists($alasanRetur, ReturSupplier::ALASAN)) {
            throw new RuntimeException('Alasan retur tidak valid.');
        }

        $pembelian = Pembelian::find($pembelianId);

        if (!$pembelian) {
            throw new RuntimeException('PO asal tidak ditemukan.');
        }

        $memuat = DetailPembelian::where('pembelian_id', $pembelian->id)
            ->where('barang_id', $barang->id)
            ->exists();

        if (!$memuat) {
            throw new RuntimeException("PO {$pembelian->nomor_po} tidak memuat {$barang->kode_model}.");
        }

        $retur = ReturSupplier::create([
            'pembelian_id'      => $pembelian->id,
            'tanggal'           => now()->toDateString(),
            'status'            => ReturSupplier::STATUS_DIAJUKAN,
            'nomor_surat_jalan' => null,
        ]);

        DetailReturSupplier::create([
            'retur_supplier_id' => $retur->id,
            'barang_id'         => $barang->id,
            'jumlah'            => $jumlah,
            'alasan'            => $alasanRetur,
        ]);

        return $retur;
    }

    /**
     * Koreksi stok ke arah tambah: membuat batch FIFO baru (bukan dari PO),
     * menaikkan stok_tersedia, memasukkan selisih ke gudang, dan mencatat mutasi bertanda +.
     * Dipanggil dari sesuaikan(), yang sudah membuka transaksi dan mengunci baris barang.
     */
    protected function tambahBatchPenyesuaian(
        Barang $barang,
        int $tambah,
        string $alasan,
        ?float $hargaBeli,
        ?int $userId
    ): array {
        $harga = $hargaBeli ?? $this->hargaBeliTerakhir($barang->id);

        if ($harga === null) {
            throw new RuntimeException(
                "Harga beli untuk unit tambahan {$barang->kode_model} wajib diisi (barang ini belum punya riwayat pembelian)."
            );
        }
        if ($harga < 0) {
            throw new RuntimeException('Harga beli tidak boleh negatif.');
        }

        $gudang = $this->gudangTujuan();

        // forceFill: tidak bergantung pada $fillable/$guarded di model DetailPembelian
        $batch = new DetailPembelian();
        $batch->forceFill([
            'pembelian_id'     => null,
            'barang_id'        => $barang->id,
            'jumlah_dipesan'   => $tambah,
            'jumlah_diterima'  => $tambah,
            'jumlah_cacat'     => 0,
            'keterangan_cacat' => null,
            'harga_beli'       => $harga,
            'sisa_jumlah'      => $tambah,
            'tanggal_masuk'    => now(),
        ])->save();

        $barang->stok_tersedia += $tambah;
        $barang->save();

        $this->tambahStokLokasi($barang->id, $gudang->id, $tambah);

        $keterangan = "{$alasan} (+{$tambah} pcs, harga beli Rp " . number_format($harga, 0, ',', '.') . ')';

        $this->catatMutasi($barang, 'penyesuaian', $tambah, 'opname', null, $keterangan, $userId);

        return ['arah' => 'tambah', 'jumlah' => $tambah, 'nilai' => (float) ($tambah * $harga)];
    }

    /** Lokasi tujuan barang masuk: gudang dengan id terkecil. Ditolak kalau belum ada gudang. */
    protected function gudangTujuan(): Lokasi
    {
        $gudang = Lokasi::where('is_gudang', true)->orderBy('id')->first();

        if (!$gudang) {
            throw new RuntimeException(
                'Belum ada lokasi gudang. Tambahkan dulu di Pengelolaan Stok > Kelola Lokasi (centang "Gudang").'
            );
        }

        return $gudang;
    }

    /** Tambah jumlah barang di satu lokasi (baris dibuat kalau belum ada). */
    protected function tambahStokLokasi(int $barangId, int $lokasiId, int $jumlah): void
    {
        $baris = StokLokasi::firstOrCreate(
            ['barang_id' => $barangId, 'lokasi_id' => $lokasiId],
            ['jumlah' => 0]
        );

        $baris->increment('jumlah', $jumlah);
    }

    /**
     * Kurangi stok per lokasi untuk barang yang keluar (terjual, rusak, koreksi).
     * Urutan: etalase/laci dulu (tempat barang dijual), baru gudang.
     */
    protected function kurangiStokLokasi(Barang $barang, int $jumlah): void
    {
        $baris = StokLokasi::with('lokasi')
            ->where('barang_id', $barang->id)
            ->where('jumlah', '>', 0)
            ->lockForUpdate()
            ->get()
            ->sortBy(fn ($s) => [$s->lokasi->is_gudang ? 1 : 0, $s->lokasi_id])
            ->values();

        $sisa = $jumlah;

        foreach ($baris as $b) {
            if ($sisa <= 0) {
                break;
            }
            $ambil = min($b->jumlah, $sisa);
            $b->decrement('jumlah', $ambil);
            $sisa -= $ambil;
        }

        if ($sisa > 0) {
            throw new RuntimeException(
                "Data stok per lokasi {$barang->kode_model} tidak konsisten dengan angka stok."
            );
        }
    }

    /**
     * Satu-satunya tempat yang menulis ke mutasi_stok.
     */
    protected function catatMutasi(
        Barang $barang,
        string $jenis,
        int $jumlah,
        ?string $refTipe,
        ?int $refId,
        ?string $keterangan = null,
        ?int $userId = null
    ): void {
        MutasiStok::create([
            'barang_id'      => $barang->id,
            'jenis_kejadian' => $jenis,
            'jumlah'         => $jumlah,                  // negatif jika stok berkurang
            'saldo_setelah'  => $barang->stok_tersedia,
            'tanggal'        => now(),
            'referensi_tipe' => $refTipe,
            'referensi_id'   => $refId,
            'keterangan'     => $keterangan,
            'user_id'        => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Satu-satunya tempat yang menulis ke kerugian_operasional.
     * Dipanggil dari dalam transaksi pemanggilnya, jadi ikut batal kalau transaksi gagal.
     * Nilai selalu positif (besar kerugian), mengikuti HPP FIFO.
     */
    protected function catatKerugian(
        string $jenis,
        ?int $barangId,
        int $jumlah,
        float $nilai,
        ?string $refTipe = null,
        ?int $refId = null,
        ?string $keterangan = null,
        ?int $userId = null
    ): void {
        KerugianOperasional::create([
            'jenis'          => $jenis,
            'barang_id'      => $barangId,
            'jumlah'         => $jumlah,
            'nilai'          => $nilai,
            'tanggal'        => now(),
            'referensi_tipe' => $refTipe,
            'referensi_id'   => $refId,
            'keterangan'     => $keterangan,
            'user_id'        => $userId ?? Auth::id(),
        ]);
    }
}