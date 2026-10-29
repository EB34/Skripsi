<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangRusak;
use App\Models\Lokasi;
use App\Models\MutasiStok;
use App\Services\StokService;
use Illuminate\Http\Request;
use RuntimeException;

class StokController extends Controller
{
    /** Tab 1: Monitoring Stok Real Time. */
    public function index()
    {
        // Barang paling berisiko (stok paling dekat/di bawah batas) tampil paling atas.
        // CAST ke SIGNED wajib: kolom UNSIGNED dikurangi bisa error kalau hasilnya negatif.
        $daftarBarang = Barang::query()
            ->with('stokLokasi.lokasi')
            ->orderByRaw('CAST(stok_tersedia AS SIGNED) - CAST(stok_minimum AS SIGNED) asc')
            ->orderBy('kode_model')
            ->paginate(10);

        // Mencapai atau di bawah batas minimum (sudah termasuk yang habis). Sama dengan pil di bar atas.
        $jumlahMenipis = Barang::menipis()->count();

        return view('stok.index', [
            'daftarBarang'  => $daftarBarang,
            'jumlahMenipis' => $jumlahMenipis,
            'opsiBarang'    => $this->opsiBarang(),
        ]);
    }

    /** Tab 2: Riwayat Mutasi Stok. */
    public function mutasi(Request $request)
    {
        $query = MutasiStok::with(['barang', 'user']);

        if ($tipe = $request->input('tipe')) {
            $query->where('jenis_kejadian', $tipe);
        }

        if ($cari = $request->input('cari')) {
            $query->where(function ($q) use ($cari) {
                $q->where('keterangan', 'like', "%{$cari}%")
                  ->orWhereHas('barang', function ($b) use ($cari) {
                      $b->where(function ($w) use ($cari) {
                          $w->where('kode_model', 'like', "%{$cari}%")
                            ->orWhere('merk', 'like', "%{$cari}%");
                      });
                  });

                // "no ref" dicari lewat angka id referensi, misal 12 untuk PO-12
                if (ctype_digit($cari)) {
                    $q->orWhere('referensi_id', (int) $cari);
                }
            });
        }

        $mutasi = $query->orderByDesc('tanggal')->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('stok.mutasi', [
            'mutasi'      => $mutasi,
            'jenisMutasi' => MutasiStok::JENIS,
            'opsiBarang'  => $this->opsiBarang(),
        ]);
    }

    /** Tab 3: Penanganan Barang Rusak. */
    public function rusak()
    {
        // Yang masih menunggu keputusan tampil paling atas.
        $daftarRusak = BarangRusak::with(['barang', 'pelapor'])
            ->orderByRaw("status = 'menunggu_keputusan' desc")
            ->orderByDesc('id')
            ->paginate(10);

        return view('stok.rusak', [
            'daftarRusak' => $daftarRusak,
            'opsiBarang'  => $this->opsiBarang(),
        ]);
    }

    public function laporRusak(Request $request, StokService $stokService)
    {
        $data = $request->validate([
            'barang_id' => ['required', 'exists:barang,id'],
            'jumlah'    => ['required', 'integer', 'min:1'],
            'alasan'    => ['required', 'string', 'max:255'],
        ], [
            'barang_id.required' => 'Barang wajib dipilih.',
            'jumlah.required'    => 'Jumlah wajib diisi.',
            'jumlah.integer'     => 'Jumlah harus berupa angka bulat.',
            'jumlah.min'         => 'Jumlah minimal 1.',
            'alasan.required'    => 'Alasan kerusakan wajib diisi.',
        ]);

        try {
            $stokService->laporRusak(
                (int) $data['barang_id'],
                (int) $data['jumlah'],
                $data['alasan'],
                auth()->id()
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('stok.rusak')
            ->with('success', 'Laporan barang rusak dicatat. Stok layak jual sudah dikurangi.');
    }

    public function putuskanRusak(Request $request, BarangRusak $barangRusak, StokService $stokService)
    {
        $data = $request->validate([
            'keputusan' => ['required', 'in:retur_supplier,write_off'],
        ]);

        try {
            $stokService->putuskanRusak($barangRusak->id, $data['keputusan'], auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pesan = $data['keputusan'] === 'write_off'
            ? 'Barang rusak di-write off.'
            : 'Barang rusak ditandai retur ke supplier.';

        return redirect()->route('stok.rusak')->with('success', $pesan);
    }

    /** Halaman detail satu barang: stok per lokasi, batch FIFO, kartu stok, koreksi, dan pengaturan. */
    public function show(Barang $barang)
    {
        $batchAktif = $barang->batchAktif()->get();
        $mutasi     = $barang->mutasiStok()->orderByDesc('tanggal')->orderByDesc('id')->paginate(15);
        $nilai      = $barang->nilaiPersediaan();

        // Daftar lokasi (gudang dulu) dan peta jumlah: lokasi_id => jumlah
        $daftarLokasi = Lokasi::orderByDesc('is_gudang')->orderBy('id')->get();
        $petaLokasi   = $barang->stokLokasi()->pluck('jumlah', 'lokasi_id');

        return view('stok.show', compact('barang', 'batchAktif', 'mutasi', 'nilai', 'daftarLokasi', 'petaLokasi'));
    }

    public function sesuaikan(Request $request, Barang $barang, StokService $stokService)
    {
        $data = $request->validate([
            'jumlah_fisik' => ['required', 'integer', 'min:0'],
            'alasan'       => ['required', 'string', 'max:255'],
        ], [
            'jumlah_fisik.required' => 'Jumlah fisik wajib diisi.',
            'jumlah_fisik.integer'  => 'Jumlah fisik harus berupa angka bulat.',
            'jumlah_fisik.min'      => 'Jumlah fisik tidak boleh negatif.',
            'alasan.required'       => 'Alasan penyesuaian wajib diisi.',
        ]);

        try {
            $hpp = $stokService->sesuaikan(
                $barang->id,
                (int) $data['jumlah_fisik'],
                $data['alasan'],
                auth()->id()
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('stok.show', $barang->id)
            ->with('success', 'Stok berhasil disesuaikan. Nilai persediaan berkurang Rp ' . number_format($hpp, 0, ',', '.') . '.');
    }

    /** Pindah barang antar lokasi (bukan FIFO, jumlah sesuai keputusan owner). */
    public function pindahLokasi(Request $request, Barang $barang, StokService $stokService)
    {
        $data = $request->validate([
            'dari_lokasi_id' => ['required', 'exists:lokasi,id'],
            'ke_lokasi_id'   => ['required', 'exists:lokasi,id', 'different:dari_lokasi_id'],
            'jumlah'         => ['required', 'integer', 'min:1'],
            'catatan'        => ['nullable', 'string', 'max:150'],
        ], [
            'dari_lokasi_id.required' => 'Lokasi asal wajib dipilih.',
            'ke_lokasi_id.required'   => 'Lokasi tujuan wajib dipilih.',
            'ke_lokasi_id.different'  => 'Lokasi asal dan tujuan tidak boleh sama.',
            'jumlah.required'         => 'Jumlah wajib diisi.',
            'jumlah.integer'          => 'Jumlah harus berupa angka bulat.',
            'jumlah.min'              => 'Jumlah minimal 1.',
        ]);

        try {
            $stokService->pindahLokasi(
                $barang->id,
                (int) $data['dari_lokasi_id'],
                (int) $data['ke_lokasi_id'],
                (int) $data['jumlah'],
                $data['catatan'] ?? null,
                auth()->id()
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('stok.show', $barang->id)->with('success', 'Barang berhasil dipindah.');
    }

    /** Mengatur batas "stok menipis" untuk satu barang. */
    public function aturPengaturan(Request $request, Barang $barang)
    {
        $data = $request->validate([
            'stok_minimum' => ['required', 'integer', 'min:0', 'max:100000'],
        ], [
            'stok_minimum.required' => 'Batas stok minimum wajib diisi.',
            'stok_minimum.integer'  => 'Batas stok minimum harus berupa angka bulat.',
            'stok_minimum.min'      => 'Batas stok minimum tidak boleh negatif.',
        ]);

        $barang->update(['stok_minimum' => (int) $data['stok_minimum']]);

        return redirect()->route('stok.show', $barang->id)
            ->with('success', 'Batas stok menipis diubah menjadi ' . $barang->stok_minimum . ' unit.');
    }

    /** Daftar barang yang masih punya stok layak jual, untuk dropdown "Lapor Barang Rusak". */
    private function opsiBarang()
    {
        return Barang::where('stok_tersedia', '>', 0)
            ->orderBy('kode_model')
            ->get(['id', 'kode_model', 'merk', 'stok_tersedia']);
    }
}