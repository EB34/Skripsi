<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\BarangRusak;
use App\Models\DetailPembelian;
use App\Models\Lokasi;
use App\Models\MutasiStok;
use App\Models\StokLokasi;
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

            $gudang = Lokasi::where('is_gudang', true)->orderBy('id')->first();
            if (!$gudang) {
                throw new RuntimeException(
                    'Belum ada lokasi gudang. Tambahkan dulu di Pengelolaan Stok > Kelola Lokasi (centang "Gudang").'
                );
            }
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
                'user_id'   => $userId ?? auth()->id(),
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
     * Keputusan tindak lanjut barang rusak: 'retur_supplier' atau 'write_off'.
     * Mengurangi stok_rusak. Stok layak jual tidak berubah, jadi mutasi dicatat dengan jumlah 0.
     */
    public function putuskanRusak(int $laporanId, string $keputusan, ?int $userId = null): void
    {
        if (!in_array($keputusan, ['retur_supplier', 'write_off'], true)) {
            throw new RuntimeException('Keputusan tidak valid.');
        }

        DB::transaction(function () use ($laporanId, $keputusan, $userId) {
            $laporan = BarangRusak::lockForUpdate()->findOrFail($laporanId);

            if ($laporan->status !== 'menunggu_keputusan') {
                throw new RuntimeException('Laporan ini sudah diputuskan sebelumnya.');
            }

            $barang = Barang::lockForUpdate()->findOrFail($laporan->barang_id);

            if ($barang->stok_rusak < $laporan->jumlah) {
                throw new RuntimeException("Data stok rusak {$barang->kode_model} tidak konsisten dengan laporan.");
            }

            $barang->stok_rusak -= $laporan->jumlah;
            $barang->save();

            $laporan->update([
                'status'          => $keputusan,
                'diputuskan_pada' => now(),
            ]);

            $label = $keputusan === 'write_off' ? 'Write off' : 'Retur ke supplier';

            $this->catatMutasi(
                $barang,
                $keputusan,
                0,
                'barang_rusak',
                $laporan->id,
                "{$label} {$laporan->jumlah} pcs ({$laporan->alasan})",
                $userId
            );
        });
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
            'user_id'        => $userId ?? auth()->id(),
        ]);
    }
}