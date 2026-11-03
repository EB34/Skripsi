<?php

namespace App\Http\Controllers;

use App\Models\ReturSupplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReturSupplierController extends Controller
{
    /** Tab Retur ke Supplier. Hanya Owner (dijaga middleware role:owner di routes). */
    public function index(Request $request)
    {
        $status = $request->input('status');
        if (! array_key_exists((string) $status, ReturSupplier::STATUS)) {
            $status = null;
        }

        $query = ReturSupplier::with(['pembelian.supplier.kontak', 'detail.barang']);
        if ($status) {
            $query->where('status', $status);
        }

        // Yang masih menunggu validasi tampil paling atas
        $daftar = $query->orderByRaw("status = 'diajukan' desc")
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $hitung = ReturSupplier::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('retur.supplier', compact('daftar', 'status', 'hitung'));
    }

    /** Owner memvalidasi retur: status jadi dikirim dan nomor surat jalan diterbitkan. */
    public function kirim(ReturSupplier $retur)
    {
        try {
            DB::transaction(function () use ($retur) {
                $r = ReturSupplier::lockForUpdate()->findOrFail($retur->id);

                if ($r->status !== ReturSupplier::STATUS_DIAJUKAN) {
                    throw new RuntimeException('Retur ini sudah divalidasi sebelumnya.');
                }

                // SJ-ddmmyyyy-urutan di hari itu, contoh SJ-04112026-1
                $prefix = 'SJ-' . now()->format('dmY') . '-';
                $urut   = ReturSupplier::where('nomor_surat_jalan', 'like', $prefix . '%')->count() + 1;

                $r->update([
                    'status'            => ReturSupplier::STATUS_DIKIRIM,
                    'nomor_surat_jalan' => $prefix . $urut,
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $retur->refresh();

        return redirect()->route('retur.supplier')
            ->with('success', "{$retur->nomor_retur} divalidasi. Surat jalan {$retur->nomor_surat_jalan} sudah diterbitkan dan siap dicetak.");
    }

    /** Supplier sudah menerima barang retur. */
    public function selesai(ReturSupplier $retur)
    {
        try {
            DB::transaction(function () use ($retur) {
                $r = ReturSupplier::lockForUpdate()->findOrFail($retur->id);

                if ($r->status !== ReturSupplier::STATUS_DIKIRIM) {
                    throw new RuntimeException('Hanya retur berstatus "Dikirim ke Supplier" yang bisa diselesaikan.');
                }

                $r->update(['status' => ReturSupplier::STATUS_SELESAI]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('retur.supplier')
            ->with('success', "{$retur->nomor_retur} ditandai selesai.");
    }

    public function nota(ReturSupplier $retur)
    {
        return $this->tampil($retur, 'nota');
    }

    public function suratJalan(ReturSupplier $retur)
    {
        if (! $retur->nomor_surat_jalan) {
            return redirect()->route('retur.supplier')
                ->with('error', 'Surat jalan belum diterbitkan. Validasi retur terlebih dahulu.');
        }

        return $this->tampil($retur, 'surat_jalan');
    }

    private function tampil(ReturSupplier $retur, string $mode)
    {
        $retur->load(['pembelian.supplier.kontak', 'pembelian.detail', 'detail.barang']);

        return view('retur.cetak', [
            'retur' => $retur,
            'mode'  => $mode,
            'judul' => $mode === 'nota' ? 'NOTA RETUR BARANG' : 'SURAT JALAN PENGEMBALIAN BARANG',
            'nomor' => $mode === 'nota' ? $retur->nomor_retur : $retur->nomor_surat_jalan,
        ]);
    }
}