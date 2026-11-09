<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\ReturSupplier;
use App\Services\ReturSupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class ReturSupplierController extends Controller
{
    /** Tab Retur ke Supplier. Owner dan Petugas (nominal uang hanya tampil untuk Owner di view). */
    public function index(Request $request)
    {
        $status = $request->input('status');
        if (! array_key_exists((string) $status, ReturSupplier::STATUS)) {
            $status = null;
        }

        $query = ReturSupplier::with(['pembelian.supplier.kontak', 'pembelian.detail', 'detail.barang']);
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

        // PO yang barangnya sudah diterima, beserta barang di dalamnya (untuk popup Buat Retur Baru).
        // Tidak ada harga di sini, jadi aman untuk Petugas.
        $poOpsi = Pembelian::with(['supplier', 'detail.barang'])
            ->where('status', '!=', 'menunggu_pengiriman')
            ->latest('tanggal')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (Pembelian $p) => [
                'id'    => $p->id,
                'label' => $p->nomor_po
                    . ' · ' . ($p->supplier?->nama_perusahaan ?? '-')
                    . ' · ' . ($p->tanggal?->format('d/m/Y') ?? '-'),
                'items' => $p->detail->unique('barang_id')->map(fn ($d) => [
                    'id'    => $d->barang_id,
                    'label' => trim(($d->barang?->merk ?? '') . ' (' . ($d->barang?->kode_model ?? '') . ')'),
                    'stok'  => (int) ($d->barang?->stok_tersedia ?? 0),
                ])->values(),
            ])
            ->values();

        return view('retur.supplier', compact('daftar', 'status', 'hitung', 'poOpsi'));
    }

    /** Tombol "Buat Retur Baru". Owner dan Petugas. */
    public function store(Request $request, ReturSupplierService $layanan)
    {
        $v = Validator::make($request->all(), [
            'pembelian_id' => ['required', 'exists:pembelian,id'],
            'barang_id'    => ['required', 'exists:barang,id'],
            'jumlah'       => ['required', 'integer', 'min:1'],
            'alasan'       => ['required', Rule::in(array_keys(ReturSupplier::ALASAN))],
            'keterangan'   => ['required', 'string', 'max:255'],
            'jenis_retur'  => ['required', Rule::in(array_keys(ReturSupplier::JENIS))],
        ], [
            'pembelian_id.required' => 'PO asal wajib dipilih.',
            'barang_id.required'    => 'Barang wajib dipilih.',
            'jumlah.required'       => 'Jumlah wajib diisi.',
            'jumlah.integer'        => 'Jumlah harus berupa angka bulat.',
            'jumlah.min'            => 'Jumlah minimal 1.',
            'alasan.required'       => 'Alasan retur wajib dipilih.',
            'keterangan.required'   => 'Keterangan wajib diisi.',
            'jenis_retur.required'  => 'Pilih jenis retur: ganti barang atau potong tagihan.',
        ]);
        if ($v->fails()) {
            return back()->withInput()->with('error', $v->errors()->first());
        }

        try {
            $retur = $layanan->buatManual(
                (int) $request->input('pembelian_id'),
                (int) $request->input('barang_id'),
                (int) $request->input('jumlah'),
                $request->input('alasan'),
                (string) $request->input('keterangan'),
                $request->input('jenis_retur'),
                Auth::id()
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('retur.supplier')
            ->with('success', "{$retur->nomor_retur} dibuat dan stok layak jual sudah dikurangi. Menunggu validasi Owner untuk menerbitkan surat jalan.");
    }

    /**
     * Owner memvalidasi retur: pilih jenis retur, pastikan no invoice supplier terisi,
     * lalu status jadi dikirim dan nomor surat jalan diterbitkan.
     */
    public function kirim(Request $request, ReturSupplier $retur)
    {
        $v = Validator::make($request->all(), [
            'jenis_retur'            => ['required', Rule::in(array_keys(ReturSupplier::JENIS))],
            'nomor_invoice_supplier' => ['required', 'string', 'max:100'],
        ], [
            'jenis_retur.required'            => 'Pilih jenis retur: ganti barang atau potong tagihan.',
            'nomor_invoice_supplier.required' => 'No. invoice dari supplier wajib diisi, karena dicetak di surat jalan.',
            'nomor_invoice_supplier.max'      => 'No. invoice maksimal 100 karakter.',
        ]);
        if ($v->fails()) {
            return back()->with('error', $v->errors()->first());
        }

        try {
            DB::transaction(function () use ($retur, $request) {
                $r = ReturSupplier::lockForUpdate()->findOrFail($retur->id);

                if ($r->status !== ReturSupplier::STATUS_DIAJUKAN) {
                    throw new RuntimeException('Retur ini sudah divalidasi sebelumnya.');
                }

                // Nomor invoice disimpan di PO asal (satu invoice untuk satu PO)
                Pembelian::where('id', $r->pembelian_id)
                    ->update(['nomor_invoice_supplier' => trim((string) $request->input('nomor_invoice_supplier'))]);

                // SJ-ddmmyyyy-urutan di hari itu, contoh SJ-04112026-1
                $prefix = 'SJ-' . now()->format('dmY') . '-';
                $urut   = ReturSupplier::where('nomor_surat_jalan', 'like', $prefix . '%')->count() + 1;

                $r->update([
                    'status'            => ReturSupplier::STATUS_DIKIRIM,
                    'jenis_retur'       => $request->input('jenis_retur'),
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

    /** Jenis "ganti barang": pengganti dari supplier sudah datang. Owner dan Petugas. */
    public function terimaPengganti(Request $request, ReturSupplier $retur, ReturSupplierService $layanan)
    {
        $v = Validator::make($request->all(), [
            'item'              => ['required', 'array'],
            'item.*.diterima'   => ['required', 'integer', 'min:0'],
            'item.*.cacat'      => ['required', 'integer', 'min:0'],
            'item.*.keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'item.required' => 'Data checklist tidak lengkap.',
        ]);
        if ($v->fails()) {
            return back()->with('error', $v->errors()->first());
        }

        try {
            $hasil = $layanan->terimaPengganti($retur->id, $request->input('item'), Auth::id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pesan = "{$hasil['nomor']} selesai: {$hasil['bagus']} unit pengganti masuk stok.";
        if ($hasil['cacat'] > 0) {
            $pesan .= " {$hasil['cacat']} unit cacat otomatis diajukan sebagai {$hasil['retur_baru']}.";
        }

        return redirect()->route('retur.supplier')->with('success', $pesan);
    }

    /** Jenis "potong tagihan": Owner mengisi nominal potongan lalu retur selesai. Dijaga role:owner di routes. */
    public function selesai(Request $request, ReturSupplier $retur, ReturSupplierService $layanan)
    {
        $v = Validator::make($request->all(), [
            'nilai_potongan' => ['required', 'numeric', 'min:1'],
        ], [
            'nilai_potongan.required' => 'Nominal potongan wajib diisi.',
            'nilai_potongan.numeric'  => 'Nominal potongan harus berupa angka.',
            'nilai_potongan.min'      => 'Nominal potongan minimal 1.',
        ]);
        if ($v->fails()) {
            return back()->with('error', $v->errors()->first());
        }

        try {
            $hasil = $layanan->selesaikanPotongan($retur->id, (float) $request->input('nilai_potongan'), Auth::id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('retur.supplier')
            ->with('success', "{$hasil->nomor_retur} selesai. Total tagihan PO berkurang Rp " . number_format($hasil->nilai_potongan, 0, ',', '.') . '.');
    }

    public function suratJalan(ReturSupplier $retur)
    {
        if (! $retur->nomor_surat_jalan) {
            return redirect()->route('retur.supplier')
                ->with('error', 'Surat jalan belum diterbitkan. Validasi retur terlebih dahulu.');
        }

        $retur->load(['pembelian.supplier.kontak', 'detail.barang']);

        return view('retur.cetak', ['retur' => $retur]);
    }
}