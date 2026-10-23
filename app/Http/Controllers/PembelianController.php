<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailReturSupplier;
use App\Models\Pembelian;
use App\Models\ReturSupplier;
use App\Models\Supplier;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PembelianController extends Controller
{
    public function index()
    {
        $pembelian = Pembelian::with(['supplier', 'detail.barang'])
            ->latest('tanggal')
            ->latest('id')
            ->paginate(10);

        return view('pembelian.index', [
            'pembelian' => $pembelian,
            'supplier'  => Supplier::orderBy('nama_perusahaan')->get(),     // untuk popup buat PO
            'barang'    => Barang::orderBy('kode_model')->get(),  // untuk popup buat PO
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'        => ['required', 'exists:supplier,id'],
            'tanggal'            => ['required', 'date'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.barang_id'  => ['required', 'exists:barang,id'],
            'items.*.jumlah'     => ['required', 'integer', 'min:1'],
            'items.*.harga_beli' => ['required', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Tambahkan minimal satu barang.',
        ]);

        $pembelian = DB::transaction(function () use ($data) {
            // Nomor PO: PO-ddmmyyyy-urutan di hari itu, contoh PO-31082026-1
            $urut  = Pembelian::whereDate('tanggal', $data['tanggal'])->count() + 1;
            $nomor = 'PO-' . date('dmY', strtotime($data['tanggal'])) . '-' . $urut;

            $p = Pembelian::create([
                'nomor_po'    => $nomor,
                'tanggal'     => $data['tanggal'],
                'supplier_id' => $data['supplier_id'],
                'status'      => 'menunggu_pengiriman',
            ]);

            foreach ($data['items'] as $item) {
                $p->detail()->create([
                    'barang_id'      => $item['barang_id'],
                    'jumlah_dipesan' => $item['jumlah'],
                    'harga_beli'     => $item['harga_beli'],
                    'sisa_jumlah'    => 0,
                ]);
            }

            return $p;
        });

        return redirect()->route('pembelian.index')
            ->with('success', "Pesanan {$pembelian->nomor_po} berhasil dibuat.");
    }

    public function show(Pembelian $pembelian)
    {
        $pembelian->load(['supplier', 'detail.barang', 'retur.detail.barang']);

        return view('pembelian.show', compact('pembelian'));
    }

    /**
     * Verifikasi penerimaan fisik (popup checklist): stok masuk, barang cacat otomatis diretur.
     */
    public function terima(Request $request, Pembelian $pembelian, StokService $stok)
    {
        $request->validate([
            'item'               => ['required', 'array'],
            'item.*.diterima'    => ['required', 'integer', 'min:0'],
            'item.*.cacat'       => ['required', 'integer', 'min:0'],
            'item.*.keterangan'  => ['nullable', 'string', 'max:255'],
            'catatan_penerima'   => ['nullable', 'string'],
        ]);

        $pembelian->load('detail.barang');

        // Periksa aturan antar-angka dulu, sebelum menyimpan apa pun
        foreach ($pembelian->detail as $d) {
            $in = $request->input("item.{$d->id}");

            if (! $in) {
                return back()->with('error', 'Data barang pada checklist tidak lengkap.');
            }

            $diterima = (int) $in['diterima'];
            $cacat    = (int) $in['cacat'];
            $kode     = $d->barang->kode_model;

            if ($diterima > $d->jumlah_dipesan) {
                return back()->with('error', "{$kode}: jumlah diterima melebihi jumlah dipesan ({$d->jumlah_dipesan}).");
            }
            if ($cacat > $diterima) {
                return back()->with('error', "{$kode}: jumlah cacat tidak boleh lebih besar dari jumlah diterima.");
            }
        }

        try {
            DB::transaction(function () use ($request, $pembelian, $stok) {
                // Ambil ulang dengan kunci, supaya pesanan yang sama tidak bisa diverifikasi dua kali
                $p = Pembelian::lockForUpdate()->with('detail')->findOrFail($pembelian->id);

                if ($p->status !== 'menunggu_pengiriman') {
                    throw new RuntimeException('Pesanan ini sudah diverifikasi sebelumnya.');
                }

                $lengkap    = true;
                $returItems = [];

                foreach ($p->detail as $d) {
                    $in       = $request->input("item.{$d->id}");
                    $diterima = (int) $in['diterima'];
                    $cacat    = (int) $in['cacat'];
                    $bagus    = $diterima - $cacat;      // hanya unit bagus yang masuk stok

                    $d->update([
                        'jumlah_diterima'  => $diterima,
                        'jumlah_cacat'     => $cacat,
                        'keterangan_cacat' => $in['keterangan'] ?? null,
                        'sisa_jumlah'      => $bagus,     // batch FIFO baru
                        'tanggal_masuk'    => $bagus > 0 ? now() : null,
                    ]);

                    if ($bagus > 0) {
                        $stok->barangMasuk($d->barang_id, $bagus, 'pembelian', $p->id);
                    }
                    if ($cacat > 0) {
                        $returItems[] = ['barang_id' => $d->barang_id, 'jumlah' => $cacat];
                    }
                    if ($diterima < $d->jumlah_dipesan || $cacat > 0) {
                        $lengkap = false;
                    }
                }

                // Unit cacat otomatis diajukan retur ke supplier
                if ($returItems) {
                    $retur = ReturSupplier::create([
                        'pembelian_id' => $p->id,
                        'tanggal'      => now()->toDateString(),
                        'status'       => 'diajukan',
                    ]);

                    foreach ($returItems as $r) {
                        DetailReturSupplier::create([
                            'retur_supplier_id' => $retur->id,
                            'barang_id'         => $r['barang_id'],
                            'jumlah'            => $r['jumlah'],
                            'alasan'            => 'cacat',
                        ]);
                    }
                }

                $p->update([
                    'status'           => $lengkap ? 'diterima_lengkap' : 'diterima_sebagian',
                    'catatan_penerima' => $request->catatan_penerima,
                    'diterima_pada'    => now(),
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pembelian.index')
            ->with('success', "Penerimaan {$pembelian->nomor_po} berhasil diverifikasi dan stok sudah diperbarui.");
    }
}