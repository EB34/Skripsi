<?php

namespace App\Http\Controllers;

use App\Models\KerugianOperasional;
use App\Models\Penjualan;
use App\Models\ReturCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LaporanController extends Controller
{
    /** Laporan Laba Rugi per periode. Hanya Owner (dijaga middleware role:owner di routes). */
    public function labaRugi(Request $request)
    {
        $aturan = [
            'dari'   => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ];
        // Aturan "sampai tidak boleh sebelum dari" hanya dipasang kalau tanggal "dari" diisi
        if ($request->filled('dari')) {
            $aturan['sampai'][] = 'after_or_equal:dari';
        }

        $data = $request->validate($aturan, [
            'dari.date'             => 'Tanggal awal tidak valid.',
            'sampai.date'           => 'Tanggal akhir tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        // Default: 1 bulan berjalan
        $dari   = Carbon::parse($data['dari'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $sampai = Carbon::parse($data['sampai'] ?? now()->toDateString())->endOfDay();
        if ($sampai->lt($dari)) {
            $sampai = $dari->copy()->endOfDay();
        }

        // Baris kosong untuk rincian per hari
        $kosong = ['nota' => 0, 'pendapatan' => 0.0, 'refund' => 0.0, 'hpp' => 0.0, 'kerugian' => 0.0];
        $harian = [];
        $tambah = function (string $tgl, string $kolom, float $nilai) use (&$harian, $kosong) {
            $harian[$tgl] ??= $kosong;
            $harian[$tgl][$kolom] += $nilai;
        };

        // ---------- 1. Penjualan (berdasarkan tanggal nota) ----------
        $notaList = Penjualan::with('detail.barang:id,tanpa_stok')
            ->withSum('pembayaran', 'jumlah')
            ->whereDate('tanggal', '>=', $dari->toDateString())
            ->whereDate('tanggal', '<=', $sampai->toDateString())
            ->get();

        $penjualanSelesai = 0.0;
        $dpHangus         = 0.0;
        $hppTotal         = 0.0;
        $notaSelesai      = 0;
        $notaBatal        = 0;
        $lensaCustom      = 0;
        $berjalan         = ['jumlah' => 0, 'total' => 0.0, 'dibayar' => 0.0];

        foreach ($notaList as $p) {
            $tgl     = $p->tanggal->toDateString();
            $dibayar = (float) ($p->pembayaran_sum_jumlah ?? 0);

            // Nota berjalan belum diakui sebagai pendapatan, hanya dicatat sebagai informasi
            if ($p->status === 'berjalan') {
                $berjalan['jumlah']++;
                $berjalan['total']   += (float) $p->total;
                $berjalan['dibayar'] += $dibayar;
                continue;
            }

            // Nota selesai = total nota. Nota batal = hanya DP yang sudah masuk (DP hangus).
            $pendapatan = $p->status === 'batal' ? $dibayar : (float) $p->total;

            // hpp di detail_penjualan = total modal baris itu. Frame yang dikembalikan ke stok
            // (jumlah_kembali) tidak dihitung sebagai modal terpakai.
            $hpp = 0.0;
            foreach ($p->detail as $d) {
                if ($d->jumlah > 0) {
                    $terpakai = max($d->jumlah - $d->jumlah_kembali, 0);
                    $hpp += (float) $d->hpp * $terpakai / $d->jumlah;
                }
                if ($d->barang && $d->barang->tanpa_stok) {
                    $lensaCustom++;
                }
            }

            if ($p->status === 'batal') {
                $dpHangus += $pendapatan;
                $notaBatal++;
            } else {
                $penjualanSelesai += $pendapatan;
                $notaSelesai++;
            }

            $hppTotal += $hpp;
            $tambah($tgl, 'pendapatan', $pendapatan);
            $tambah($tgl, 'hpp', $hpp);
            $tambah($tgl, 'nota', 1);
        }

        // ---------- 2. Refund retur customer (berdasarkan tanggal diproses Owner) ----------
        $refundList = ReturCustomer::where('status', ReturCustomer::STATUS_SELESAI)
            ->where('tindak_lanjut', 'refund')
            ->whereBetween('diproses_pada', [$dari, $sampai])
            ->get();

        $refundTotal = 0.0;
        foreach ($refundList as $r) {
            $refundTotal += (float) $r->jumlah_refund;
            $tambah($r->diproses_pada->toDateString(), 'refund', (float) $r->jumlah_refund);
        }

        // ---------- 3. Kerugian operasional ----------
        $kerugianList = KerugianOperasional::whereBetween('tanggal', [$dari, $sampai])->get();

        $kerugianJenis = [];
        $kerugianTotal = 0.0;
        foreach ($kerugianList as $k) {
            $kerugianJenis[$k->jenis] = ($kerugianJenis[$k->jenis] ?? 0.0) + (float) $k->nilai;
            $kerugianTotal += (float) $k->nilai;
            $tambah($k->tanggal->toDateString(), 'kerugian', (float) $k->nilai);
        }

        ksort($harian);

        // ---------- 4. Ringkasan ----------
        $pendapatanBersih = $penjualanSelesai + $dpHangus - $refundTotal;
        $labaKotor        = $pendapatanBersih - $hppTotal;
        $labaBersih       = $labaKotor - $kerugianTotal;

        return view('laporan.labarugi', [
            'dari'          => $dari,
            'sampai'        => $sampai,
            'harian'        => $harian,
            'kerugianJenis' => $kerugianJenis,
            'berjalan'      => $berjalan,
            'lensaCustom'   => $lensaCustom,
            'r' => [
                'penjualan'         => $penjualanSelesai,
                'dp_hangus'         => $dpHangus,
                'refund'            => $refundTotal,
                'pendapatan_bersih' => $pendapatanBersih,
                'hpp'               => $hppTotal,
                'laba_kotor'        => $labaKotor,
                'kerugian'          => $kerugianTotal,
                'laba_bersih'       => $labaBersih,
                'nota_selesai'      => $notaSelesai,
                'nota_batal'        => $notaBatal,
            ],
        ]);
    }
}