<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\PenggantianKesalahanToko;
use App\Models\PesananKacamata;
use App\Services\ReturService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class KesalahanTokoController extends Controller
{
    /** Tab Penggantian Kesalahan Toko. Owner dan Petugas (nilai kerugian hanya tampil untuk Owner di view). */
    public function index()
    {
        $daftar = PenggantianKesalahanToko::with([
            'pesanan.customer', 'pesanan.penjualan', 'lensaWriteoff', 'lensaPengganti', 'pencatat',
        ])->latest('id')->paginate(10);

        // Pesanan yang lensanya dari toko dan sudah masuk tahap pengerjaan
        $pesananOpsi = PesananKacamata::with(['customer', 'lensa'])
            ->whereIn('status', ['dikerjakan', 'siap_diambil', 'selesai'])
            ->whereNotNull('lensa_id')
            ->latest('id')
            ->limit(100)
            ->get();

        $lensaStok = Barang::whereRaw('LOWER(jenis) = ?', ['lensa'])
            ->where('stok_tersedia', '>', 0)
            ->orderBy('kode_model')
            ->get();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $totalNilai = $user->isOwner() ? (float) PenggantianKesalahanToko::sum('nilai_kerugian') : null;

        return view('retur.kesalahan', compact('daftar', 'pesananOpsi', 'lensaStok', 'totalNilai'));
    }

    public function store(Request $request, ReturService $layanan)
    {
        $v = Validator::make($request->all(), [
            'pesanan_id'         => ['required', 'exists:pesanan_kacamata,id'],
            'jenis_kesalahan'    => ['required', Rule::in(array_keys(PenggantianKesalahanToko::JENIS))],
            'lensa_pengganti_id' => ['required', 'exists:barang,id'],
            'jumlah'             => ['required', 'integer', 'min:1', 'max:2'],
            'keterangan'         => ['nullable', 'string', 'max:255'],
        ], [
            'pesanan_id.required'         => 'Pesanan belum dipilih.',
            'jenis_kesalahan.required'    => 'Jenis kesalahan wajib dipilih.',
            'lensa_pengganti_id.required' => 'Lensa pengganti wajib dipilih.',
            'jumlah.required'             => 'Jumlah lensa yang diganti wajib diisi.',
            'jumlah.integer'              => 'Jumlah harus berupa angka bulat.',
            'jumlah.min'                  => 'Jumlah minimal 1.',
            'jumlah.max'                  => 'Jumlah maksimal 2 biji.',
        ]);
        if ($v->fails()) {
            return back()->withInput()->with('error', $v->errors()->first());
        }

        try {
            $catatan = $layanan->catatKesalahanToko(
                (int) $request->input('pesanan_id'),
                $request->input('jenis_kesalahan'),
                (int) $request->input('lensa_pengganti_id'),
                (int) $request->input('jumlah'),
                $request->input('keterangan'),
                Auth::id()
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('retur.kesalahan')
            ->with('success', "{$catatan->nomor} dicatat. Lensa pengganti sudah dikeluarkan dari stok.");
    }
}