<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangRusak;
use App\Models\PembayaranPenjualan;
use App\Models\Penjualan;
use App\Models\PesananKacamata;
use App\Models\ReturSupplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user    = Auth::user();
        $isOwner = $user->isOwner();

        // Periode: minggu (Senin-Minggu) atau bulan. Default minggu.
        $periode  = $request->query('periode') === 'bulan' ? 'bulan' : 'minggu';
        $sekarang = Carbon::now();

        if ($periode === 'bulan') {
            $awal  = $sekarang->copy()->startOfMonth();
            $akhir = $sekarang->copy()->endOfMonth();
        } else {
            $awal  = $sekarang->copy()->startOfWeek(Carbon::MONDAY);
            $akhir = $sekarang->copy()->endOfWeek(Carbon::SUNDAY);
        }

        $labelPeriode = $awal->copy()->locale('id')->translatedFormat('j M')
            . ' - ' . $akhir->copy()->locale('id')->translatedFormat('j M Y');

        // ===== Data untuk Owner dan Staff (tanpa angka rupiah) =====
        $data = [
            'isOwner'      => $isOwner,
            'periode'      => $periode,
            'labelPeriode' => $labelPeriode,

            // Sama dengan Monitoring Stok dan pil bar atas
            'stokMenipis'  => Barang::menipis()->count(),
            'stokKritis'   => Barang::menipis()
                ->orderByRaw('CAST(stok_tersedia AS SIGNED) - CAST(stok_minimum AS SIGNED) asc')
                ->orderBy('kode_model')
                ->limit(5)
                ->get(),

            'antrean' => PesananKacamata::aktif()
                ->with(['customer', 'frame', 'lensa', 'petugas'])
                ->orderByRaw("FIELD(status, 'siap_diambil', 'dikerjakan', 'menunggu')")
                ->orderBy('id')
                ->limit(6)
                ->get(),

            'pesananMenunggu'   => PesananKacamata::where('status', 'menunggu')->count(),
            'pesananDikerjakan' => PesananKacamata::where('status', 'dikerjakan')->count(),
            'pesananSiap'       => PesananKacamata::where('status', 'siap_diambil')->count(),
            'pesananBelumAda'   => PesananKacamata::where('status', 'menunggu')->whereNull('petugas_id')->count(),
            'pesananSaya'       => PesananKacamata::where('status', 'dikerjakan')->where('petugas_id', $user->id)->count(),

            // Jumlah nota dalam periode (angka saja, aman untuk Staff)
            'jumlahNota' => Penjualan::whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])->count(),
        ];

        // ===== Data khusus Staff =====
        if (! $isOwner) {
            $data['rusakSaya'] = BarangRusak::where('user_id', $user->id)
                ->whereBetween('created_at', [$awal, $akhir])
                ->count();

            return view('dashboard', $data);
        }

        // ===== Data khusus Owner (ada nominal rupiah, tidak pernah dikirim ke view Staff) =====
        $data['uangMasuk'] = (float) PembayaranPenjualan::whereBetween('dibayar_pada', [$awal, $akhir])->sum('jumlah');

        // Piutang = sisa bayar nota yang masih berjalan (kondisi saat ini, bukan per periode)
        $data['piutang'] = Penjualan::where('status', 'berjalan')
            ->withSum('pembayaran', 'jumlah')
            ->get()
            ->sum(fn (Penjualan $p) => $p->sisa_bayar);

        $rusak = BarangRusak::whereBetween('created_at', [$awal, $akhir]);
        $data['rusakDilaporkan'] = (clone $rusak)->count();
        $data['rusakMenunggu']   = (clone $rusak)->where('status', 'menunggu_keputusan')->count();

        $data['returPerStatus'] = ReturSupplier::whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('dashboard', $data);
    }
}