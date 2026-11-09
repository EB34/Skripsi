<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPembelian;
use App\Models\DetailPenjualan;
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
    public function index(Request $request)
    {
        $isOwner = $request->user()->isOwner();

        $pembelian = Pembelian::with([
                'supplier', 'detail.barang', 'retur',
                'detail.detailPenjualan.penjualan.customer',
            ])
            ->latest('tanggal')
            ->latest('id')
            ->paginate(10);

        // Data untuk popup Buat PO: hanya Owner yang boleh membuat PO, jadi staff tidak perlu datanya
        $supplier = collect();
        $barang   = collect();
        $custom   = collect();

        if ($isOwner) {
            $supplier = Supplier::with('kontak')->orderBy('nama_perusahaan')->get();

            // PO barang stok: lensa custom tidak ikut (dipesan lewat jenis "Lensa Custom")
            $barang = Barang::where('tanpa_stok', false)->orderBy('kode_model')->get();

            // Biji lensa custom yang masih menunggu dipesan: nota berjalan dan belum punya PO
            $custom = DetailPenjualan::with(['barang', 'penjualan.customer'])
                ->whereHas('barang', fn ($q) => $q->where('tanpa_stok', true))
                ->whereHas('penjualan', fn ($q) => $q->where('status', 'berjalan'))
                ->whereDoesntHave('pembelianCustom')
                ->orderBy('id')
                ->get();
        }

        return view('pembelian.index', compact('pembelian', 'supplier', 'barang', 'custom', 'isOwner'));
    }

    public function store(Request $request)
    {
        $custom = $request->input('jenis') === 'custom';

        $rules = [
            'supplier_id'        => ['required', 'exists:supplier,id'],
            'tanggal'            => ['required', 'date'],
            'jenis'              => ['required', 'in:stok,custom'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.harga_beli' => ['required', 'numeric', 'min:0'],
        ];

        $rules += $custom
            ? ['items.*.detail_penjualan_id' => ['required', 'distinct', 'exists:detail_penjualan,id']]
            : [
                'items.*.barang_id' => ['required', 'exists:barang,id'],
                'items.*.jumlah'    => ['required', 'integer', 'min:1'],
            ];

        $data = $request->validate($rules, [
            'items.required' => 'Tambahkan minimal satu barang.',
        ]);

        try {
            $pembelian = DB::transaction(function () use ($data, $custom) {
                if ($custom) {
                    $ids  = collect($data['items'])->pluck('detail_penjualan_id');
                    $baris = DetailPenjualan::with(['barang', 'penjualan'])
                        ->whereIn('id', $ids)
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    foreach ($data['items'] as $item) {
                        $dp = $baris[$item['detail_penjualan_id']] ?? null;

                        if (! $dp || ! $dp->barang->tanpa_stok) {
                            throw new RuntimeException('Ada baris yang bukan lensa custom.');
                        }
                        if ($dp->penjualan->status !== 'berjalan') {
                            throw new RuntimeException("{$dp->label_nota}: nota tidak lagi berjalan, tidak bisa dipesan.");
                        }
                        if (DetailPembelian::where('detail_penjualan_id', $dp->id)->exists()) {
                            throw new RuntimeException("{$dp->label_nota}: lensa ini sudah pernah dipesan ke supplier.");
                        }
                    }
                } else {
                    $ada = Barang::whereIn('id', collect($data['items'])->pluck('barang_id'))
                        ->where('tanpa_stok', true)->exists();
                    if ($ada) {
                        throw new RuntimeException('Lensa custom dipesan lewat jenis "Lensa Custom", bukan sebagai barang stok.');
                    }
                }

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
                    if ($custom) {
                        $dp = $baris[$item['detail_penjualan_id']];
                        $p->detail()->create([
                            'barang_id'           => $dp->barang_id,
                            'detail_penjualan_id' => $dp->id,
                            'keterangan_ukuran'   => $dp->keterangan,
                            'jumlah_dipesan'      => 1,
                            'harga_beli'          => $item['harga_beli'],
                            'sisa_jumlah'         => 0,
                        ]);
                    } else {
                        $p->detail()->create([
                            'barang_id'      => $item['barang_id'],
                            'jumlah_dipesan' => $item['jumlah'],
                            'harga_beli'     => $item['harga_beli'],
                            'sisa_jumlah'    => 0,
                        ]);
                    }
                }

                return $p;
            });
        } catch (RuntimeException $e) {
            // withErrors + withInput supaya popup Buat PO terbuka lagi dengan isian tadi
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('pembelian.index')
            ->with('success', "Pesanan {$pembelian->nomor_po} berhasil dibuat.");
    }

    public function show(Request $request, Pembelian $pembelian)
    {
        $pembelian->load([
            'supplier', 'detail.barang', 'retur.detail.barang',
            'detail.detailPenjualan.penjualan.customer',
        ]);

        $isOwner = $request->user()->isOwner();

        return view('pembelian.show', compact('pembelian', 'isOwner'));
    }

    /**
     * Verifikasi penerimaan fisik (popup checklist).
     * Barang stok: stok masuk, cacat otomatis diretur.
     * Lensa custom: tidak masuk stok, HPP baris nota diisi dari harga beli PO.
     */
    public function terima(Request $request, Pembelian $pembelian, StokService $stok)
    {
        $request->validate([
            'nomor_invoice_supplier' => ['nullable', 'string', 'max:100'],
            'item'                   => ['required', 'array'],
            'item.*.diterima'        => ['required', 'integer', 'min:0'],
            'item.*.cacat'           => ['required', 'integer', 'min:0'],
            'item.*.keterangan'      => ['nullable', 'string', 'max:255'],
            'catatan_penerima'       => ['nullable', 'string'],
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
            if ($d->detail_penjualan_id && $cacat > 0) {
                return back()->with('error', "{$kode} (custom): lensa custom cacat belum bisa dicatat di checklist ini. Hubungi Owner.");
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
                    $bagus    = $diterima - $cacat;
                    $isCustom = $d->detail_penjualan_id !== null;

                    if ($isCustom) {
                        // Lensa custom: bukan batch FIFO, tidak masuk stok
                        $d->update([
                            'jumlah_diterima'  => $diterima,
                            'jumlah_cacat'     => 0,
                            'keterangan_cacat' => null,
                            'sisa_jumlah'      => 0,
                            'tanggal_masuk'    => null,
                        ]);

                        // HPP baris nota = harga beli x jumlah diterima
                        if ($diterima > 0) {
                            DetailPenjualan::whereKey($d->detail_penjualan_id)
                                ->update(['hpp' => (float) $d->harga_beli * $diterima]);
                        }
                    } else {
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
                            $returItems[] = [
                                'barang_id'  => $d->barang_id,
                                'jumlah'     => $cacat,
                                'keterangan' => $in['keterangan'] ?? null,
                            ];
                        }
                    }

                    if ($diterima < $d->jumlah_dipesan || $cacat > 0) {
                        $lengkap = false;
                    }
                }

                // Unit cacat otomatis diajukan retur ke supplier (jenis retur dipilih Owner saat validasi)
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
                            'keterangan'        => $r['keterangan'],
                        ]);
                    }
                }

                $invoice = trim((string) $request->input('nomor_invoice_supplier'));

                $p->update([
                    'status'                 => $lengkap ? 'diterima_lengkap' : 'diterima_sebagian',
                    'catatan_penerima'       => $request->catatan_penerima,
                    'diterima_pada'          => now(),
                    'nomor_invoice_supplier' => $invoice !== '' ? $invoice : null,
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pembelian.index')
            ->with('success', "Penerimaan {$pembelian->nomor_po} berhasil diverifikasi dan stok sudah diperbarui.");
    }
}