<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Penjualan;
use App\Models\ReturCustomer;
use App\Services\ReturService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class ReturCustomerController extends Controller
{
    /** Tab Retur dari Customer. Owner dan Petugas. */
    public function index(Request $request)
    {
        $status = $request->input('status');
        if (! array_key_exists((string) $status, ReturCustomer::STATUS)) {
            $status = null;
        }

        $query = ReturCustomer::with([
            'penjualan' => fn ($q) => $q->withSum('pembayaran', 'jumlah'),
            'penjualan.customer', 'penjualan.detail',
            'barang', 'pengganti', 'pencatat', 'pemroses',
        ]);
        if ($status) {
            $query->where('status', $status);
        }

        // Yang masih menunggu Owner tampil paling atas
        $daftar = $query->orderByRaw("status = 'dicatat' desc")
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $hitung = ReturCustomer::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Nota yang boleh diretur: selesai, berisi frame, belum pernah diretur
        $notaTersedia = Penjualan::with(['customer', 'detail.barang'])
            ->where('status', 'selesai')
            ->whereDoesntHave('returCustomer')
            ->whereHas('detail.barang', fn ($q) => $q->whereRaw('LOWER(jenis) = ?', ['frame']))
            ->latest('id')
            ->limit(100)
            ->get();

        // Frame pengganti dari stok toko (untuk popup Proses, hanya dipakai Owner)
        $frameStok = Barang::whereRaw('LOWER(jenis) = ?', ['frame'])
            ->where('stok_tersedia', '>', 0)
            ->orderBy('kode_model')
            ->get();

        return view('retur.customer', compact('daftar', 'status', 'hitung', 'notaTersedia', 'frameStok'));
    }

    /** Petugas/Owner mencatat keluhan pelanggan. */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'penjualan_id' => ['required', 'exists:penjualan,id'],
            'keluhan'      => ['required', 'string', 'max:500'],
        ], [
            'penjualan_id.required' => 'Nota belum dipilih.',
            'keluhan.required'      => 'Keluhan pelanggan wajib diisi.',
            'keluhan.max'           => 'Keluhan maksimal 500 karakter.',
        ]);
        if ($v->fails()) {
            return back()->withInput()->with('error', $v->errors()->first());
        }

        try {
            $retur = DB::transaction(function () use ($request) {
                $nota = Penjualan::with('detail.barang')->lockForUpdate()->findOrFail($request->input('penjualan_id'));

                if ($nota->status !== 'selesai') {
                    throw new RuntimeException("Nota {$nota->nomor_nota} belum selesai, jadi belum bisa diretur.");
                }
                if (ReturCustomer::where('penjualan_id', $nota->id)->exists()) {
                    throw new RuntimeException("Nota {$nota->nomor_nota} sudah pernah dicatat returnya.");
                }

                $frame = $nota->detail->first(fn ($d) => strtolower((string) $d->barang->jenis) === 'frame');
                if (! $frame) {
                    throw new RuntimeException("Nota {$nota->nomor_nota} tidak berisi frame.");
                }

                return ReturCustomer::create([
                    'penjualan_id' => $nota->id,
                    'barang_id'    => $frame->barang_id,
                    'keluhan'      => trim((string) $request->input('keluhan')),
                    'tanggal'      => now()->toDateString(),
                    'status'       => ReturCustomer::STATUS_DICATAT,
                    'dicatat_oleh' => Auth::id(),
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('retur.customer')
            ->with('success', "{$retur->nomor_retur} dicatat. Menunggu konfirmasi dan tindak lanjut dari Owner.");
    }

    /** Owner memproses retur (konfirmasi supplier + tindak lanjut). Dijaga middleware role:owner. */
    public function proses(Request $request, ReturCustomer $retur, ReturService $layanan)
    {
        $v = Validator::make($request->all(), [
            'status_konfirmasi'   => ['required', Rule::in(array_keys(ReturCustomer::KONFIRMASI))],
            'tindak_lanjut'       => ['required', Rule::in(array_keys(ReturCustomer::TINDAK_LANJUT))],
            'barang_pengganti_id' => ['nullable', 'exists:barang,id'],
            'jumlah_refund'       => ['nullable', 'numeric', 'min:0'],
            'catatan_owner'       => ['nullable', 'string', 'max:255'],
        ], [
            'status_konfirmasi.required' => 'Hasil konfirmasi ke supplier wajib dipilih.',
            'tindak_lanjut.required'     => 'Tindak lanjut wajib dipilih.',
            'jumlah_refund.numeric'      => 'Nominal refund harus berupa angka.',
        ]);
        if ($v->fails()) {
            return back()->with('error', $v->errors()->first());
        }

        try {
            $hasil = $layanan->prosesReturCustomer(
                $retur->id,
                $request->input('status_konfirmasi'),
                $request->input('tindak_lanjut'),
                $request->filled('barang_pengganti_id') ? (int) $request->input('barang_pengganti_id') : null,
                $request->filled('jumlah_refund') ? (float) $request->input('jumlah_refund') : null,
                $request->input('catatan_owner'),
                Auth::id()
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pesan = "{$hasil->nomor_retur} selesai diproses: {$hasil->tindak_lanjut_label}.";
        if ($hasil->tindak_lanjut === 'tukar_stok_toko') {
            $pesan .= ' Stok frame pengganti sudah dikurangi.';
        } elseif ($hasil->tindak_lanjut === 'refund') {
            $pesan .= ' Refund Rp ' . number_format($hasil->jumlah_refund, 0, ',', '.') . ' dicatat.';
        }

        return redirect()->route('retur.customer')->with('success', $pesan);
    }
}