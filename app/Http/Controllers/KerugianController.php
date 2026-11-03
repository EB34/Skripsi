<?php

namespace App\Http\Controllers;

use App\Models\KerugianOperasional;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class KerugianController extends Controller
{
    /** Daftar kerugian operasional. Hanya Owner (dijaga middleware role:owner di routes). */
    public function index(Request $request)
    {
        $aturan = [
            'jenis'  => ['nullable', 'in:' . implode(',', array_keys(KerugianOperasional::JENIS))],
            'dari'   => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ];
        // Aturan "sampai tidak boleh sebelum dari" hanya dipasang kalau tanggal "dari" diisi
        if ($request->filled('dari')) {
            $aturan['sampai'][] = 'after_or_equal:dari';
        }

        $data = $request->validate($aturan, [
            'jenis.in'               => 'Jenis kerugian tidak valid.',
            'dari.date'              => 'Tanggal awal tidak valid.',
            'sampai.date'            => 'Tanggal akhir tidak valid.',
            'sampai.after_or_equal'  => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        $jenis  = $data['jenis'] ?? null;
        $dari   = $data['dari'] ?? null;
        $sampai = $data['sampai'] ?? null;

        // Ringkasan per jenis: hanya mengikuti periode (tidak mengikuti filter jenis)
        $baris = $this->terapkanPeriode(KerugianOperasional::query(), $dari, $sampai)
            ->selectRaw('jenis, SUM(nilai) as total, SUM(jumlah) as unit')
            ->groupBy('jenis')
            ->get()
            ->keyBy('jenis');

        $ringkas = [];
        foreach (KerugianOperasional::JENIS as $kode => $label) {
            $ringkas[$kode] = [
                'label' => $label,
                'nilai' => (float) ($baris[$kode]->total ?? 0),
                'unit'  => (int) ($baris[$kode]->unit ?? 0),
            ];
        }
        $totalPeriode = array_sum(array_column($ringkas, 'nilai'));

        // Daftar: mengikuti periode DAN jenis
        $daftar = $this->terapkanPeriode(KerugianOperasional::with(['barang', 'user']), $dari, $sampai);
        if ($jenis) {
            $daftar->where('jenis', $jenis);
        }

        $totalTampil = (float) (clone $daftar)->sum('nilai');

        $daftar = $daftar->orderByDesc('tanggal')->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('kerugian.index', [
            'daftar'       => $daftar,
            'ringkas'      => $ringkas,
            'totalPeriode' => $totalPeriode,
            'totalTampil'  => $totalTampil,
            'jenisOpsi'    => KerugianOperasional::JENIS,
            'jenis'        => $jenis,
            'dari'         => $dari,
            'sampai'       => $sampai,
        ]);
    }

    /** Batasi query ke rentang tanggal (awal hari "dari" sampai akhir hari "sampai"). */
    private function terapkanPeriode($query, ?string $dari, ?string $sampai)
    {
        if ($dari) {
            $query->where('tanggal', '>=', Carbon::parse($dari)->startOfDay());
        }
        if ($sampai) {
            $query->where('tanggal', '<=', Carbon::parse($sampai)->endOfDay());
        }

        return $query;
    }
}