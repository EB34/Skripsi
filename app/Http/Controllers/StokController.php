<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangRusak;
use App\Models\DetailPembelian;
use App\Models\Lokasi;
use App\Models\MutasiStok;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\ReturSupplier;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use RuntimeException;

class StokController extends Controller
{
    /** Tab 1: Monitoring Stok Real Time. */
    public function index()
    {
        // Barang paling berisiko (stok paling dekat/di bawah batas) tampil paling atas.
        // CAST ke SIGNED wajib: kolom UNSIGNED dikurangi bisa error kalau hasilnya negatif.
        // Lensa custom (tanpa stok) tidak dimonitor karena memang tidak punya stok.
        $daftarBarang = Barang::query()
            ->where('tanpa_stok', false)
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
                  })
                  // Nomor referensi: cari di nomor PO (pembelian) dan nomor nota (penjualan)
                  ->orWhere(function ($r) use ($cari) {
                      $r->where('referensi_tipe', 'pembelian')
                        ->whereIn('referensi_id', Pembelian::where('nomor_po', 'like', "%{$cari}%")->select('id'));
                  })
                  ->orWhere(function ($r) use ($cari) {
                      $r->where('referensi_tipe', 'penjualan')
                        ->whereIn('referensi_id', Penjualan::where('nomor_nota', 'like', "%{$cari}%")->select('id'));
                  });

                // Referensi lain (BR-, OPN-) tidak punya nomor dokumen, jadi dicari lewat angka id-nya
                if (ctype_digit($cari)) {
                    $q->orWhere(function ($r) use ($cari) {
                        $r->whereNotIn('referensi_tipe', ['pembelian', 'penjualan'])
                          ->where('referensi_id', (int) $cari);
                    });
                }
            });
        }

        $mutasi = $query->orderByDesc('tanggal')->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Ambil nomor PO / nomor nota asli untuk baris-baris di halaman ini (2 query)
        MutasiStok::muatNomorDokumen($mutasi->getCollection());

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

        // PO asal per barang, untuk dropdown "Retur ke Supplier" di modal keputusan.
        // Bentuk: [barang_id => [['id' => ..., 'label' => ...], ...]]
        $barangIds = $daftarRusak->getCollection()->pluck('barang_id')->unique()->values();

        $poPerBarang = DetailPembelian::with('pembelian.supplier')
            ->whereIn('barang_id', $barangIds)
            ->whereHas('pembelian')
            ->get()
            ->groupBy('barang_id')
            ->map(fn ($grup) => $grup->pluck('pembelian')
                ->unique('id')
                ->values()
                ->map(fn (Pembelian $p) => [
                    'id'    => $p->id,
                    'label' => $p->nomor_po
                        . ' · ' . ($p->supplier?->nama_perusahaan ?? '-')
                        . ' · ' . ($p->tanggal?->format('d/m/Y') ?? '-'),
                ])
                ->values());

        return view('stok.rusak', [
            'daftarRusak' => $daftarRusak,
            'opsiBarang'  => $this->opsiBarang(),
            'poPerBarang' => $poPerBarang,
            'alasanRetur' => ReturSupplier::ALASAN,
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
                Auth::id()
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('stok.rusak')
            ->with('success', 'Laporan barang rusak dicatat. Stok layak jual sudah dikurangi.');
    }

    /** Keputusan Owner: retur ke supplier (wajib PO asal dan alasan) atau write off. */
    public function putuskanRusak(Request $request, BarangRusak $barangRusak, StokService $stokService)
    {
        $data = $request->validate([
            'keputusan'   => ['required', 'in:retur_supplier,write_off'],
            'pembelian_id' => ['required_if:keputusan,retur_supplier', 'nullable', 'integer', 'exists:pembelian,id'],
            'alasan_retur' => [
                'required_if:keputusan,retur_supplier', 'nullable', 'string',
                Rule::in(array_keys(ReturSupplier::ALASAN)),
            ],
        ], [
            'keputusan.required'     => 'Keputusan wajib dipilih.',
            'pembelian_id.required_if' => 'Pilih PO asal untuk retur ke supplier.',
            'pembelian_id.exists'    => 'PO asal tidak ditemukan.',
            'alasan_retur.required_if' => 'Pilih alasan retur.',
            'alasan_retur.in'        => 'Alasan retur tidak valid.',
        ]);

        try {
            $retur = $stokService->putuskanRusak(
                $barangRusak->id,
                $data['keputusan'],
                Auth::id(),
                isset($data['pembelian_id']) ? (int) $data['pembelian_id'] : null,
                $data['alasan_retur'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pesan = $retur
            ? "Retur {$retur->nomor_retur} dibuat dan menunggu validasi Owner di tab Retur ke Supplier."
            : 'Barang rusak di-write off.';

        return redirect()->route('stok.rusak')->with('success', $pesan);
    }

    /** Halaman detail satu barang: stok per lokasi, batch FIFO, kartu stok, koreksi, dan pengaturan. */
    public function show(Barang $barang, StokService $stokService)
    {
        $batchAktif = $barang->batchAktif()->get();
        $mutasi     = $barang->mutasiStok()->orderByDesc('tanggal')->orderByDesc('id')->paginate(15);
        $nilai      = $barang->nilaiPersediaan();

        // Daftar lokasi (gudang dulu) dan peta jumlah: lokasi_id => jumlah
        $daftarLokasi = Lokasi::orderByDesc('is_gudang')->orderBy('id')->get();
        $petaLokasi   = $barang->stokLokasi()->pluck('jumlah', 'lokasi_id');

        // Harga awal di form koreksi kalau stok ditambah (harga batch terbaru, boleh diubah)
        $hargaTerakhir = $stokService->hargaBeliTerakhir($barang->id);

        return view('stok.show', compact(
            'barang', 'batchAktif', 'mutasi', 'nilai', 'daftarLokasi', 'petaLokasi', 'hargaTerakhir'
        ));
    }

    /** Koreksi stok (opname): boleh mengurangi maupun menambah. */
    public function sesuaikan(Request $request, Barang $barang, StokService $stokService)
    {
        $data = $request->validate([
            'jumlah_fisik' => ['required', 'integer', 'min:0'],
            'alasan'       => ['required', 'string', 'max:150'],
            // Harga beli wajib hanya kalau stok akan bertambah (fisik > stok sistem)
            'harga_beli'   => [
                Rule::requiredIf(fn () => (int) $request->input('jumlah_fisik') > (int) $barang->stok_tersedia),
                'nullable', 'numeric', 'min:0',
            ],
        ], [
            'jumlah_fisik.required' => 'Jumlah fisik wajib diisi.',
            'jumlah_fisik.integer'  => 'Jumlah fisik harus berupa angka bulat.',
            'jumlah_fisik.min'      => 'Jumlah fisik tidak boleh negatif.',
            'alasan.required'       => 'Alasan penyesuaian wajib diisi.',
            'alasan.max'            => 'Alasan maksimal 150 karakter.',
            'harga_beli.required'   => 'Harga beli per unit wajib diisi karena stok bertambah.',
            'harga_beli.numeric'    => 'Harga beli harus berupa angka.',
            'harga_beli.min'        => 'Harga beli tidak boleh negatif.',
        ]);

        try {
            $hasil = $stokService->sesuaikan(
                $barang->id,
                (int) $data['jumlah_fisik'],
                $data['alasan'],
                Auth::id(),
                isset($data['harga_beli']) ? (float) $data['harga_beli'] : null
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $nilai = number_format($hasil['nilai'], 0, ',', '.');

        $pesan = $hasil['arah'] === 'tambah'
            ? "Stok disesuaikan: bertambah {$hasil['jumlah']} unit. Nilai persediaan bertambah Rp {$nilai}."
            : "Stok disesuaikan: berkurang {$hasil['jumlah']} unit. Nilai persediaan berkurang Rp {$nilai}.";

        return redirect()->route('stok.show', $barang->id)->with('success', $pesan);
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
                Auth::id()
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