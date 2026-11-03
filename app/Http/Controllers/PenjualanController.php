<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Penjualan;
use App\Services\PesananService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class PenjualanController extends Controller
{
    public function index()
    {
        $penjualan = Penjualan::with(['customer', 'kasir'])
            ->withSum('pembayaran', 'jumlah')
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15);

        return view('penjualan.index', compact('penjualan'));
    }

    public function create()
    {
        $customer = Customer::with('refraksi')->orderBy('nama')->get();

        return view('penjualan.create', [
            'barang'              => Barang::orderBy('jenis')->orderBy('kode_model')->get(),
            'customer'            => $customer,
            'refraksiPerCustomer' => PesananService::refraksiPerCustomer($customer),
        ]);
    }

    public function store(Request $request, StokService $stok, PesananService $pesananService)
    {
        $data = $request->validate(array_merge([
            'customer_id'        => ['required', 'exists:customer,id'],
            'metode_pembayaran'  => ['required', Rule::in(['tunai', 'qris', 'transfer', 'kartu'])],
            'diskon'             => ['nullable', 'numeric', 'min:0'],
            'uang_diterima'      => ['nullable', 'numeric', 'min:0'],
            'bayar_dp'           => ['nullable', 'boolean'],
            'buat_pesanan'       => ['nullable', 'boolean'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.barang_id'  => ['required', 'exists:barang,id'],
            'items.*.jumlah'     => ['required', 'integer', 'min:1'],
        ], PesananService::aturan()), [
            'customer_id.required' => 'Customer belum dipilih.',
            'items.required' => 'Keranjang masih kosong.',
            'items.min'      => 'Keranjang masih kosong.',
        ], PesananService::namaAtribut());

        // Gabungkan baris barang yang sama menjadi satu jumlah
        $jumlahPerBarang = [];
        foreach ($data['items'] as $item) {
            $id = (int) $item['barang_id'];
            $jumlahPerBarang[$id] = ($jumlahPerBarang[$id] ?? 0) + (int) $item['jumlah'];
        }

        // Harga SELALU diambil dari database, bukan dari kiriman browser
        $barang = Barang::whereIn('id', array_keys($jumlahPerBarang))->get()->keyBy('id');

        $subtotal      = 0;
        $adaFrame      = false;
        $barisLensa    = 0;
        $qtyLensa      = 0;
        $subtotalLensa = 0;

        foreach ($jumlahPerBarang as $id => $qty) {
            $nilai = $barang[$id]->harga_jual * $qty;
            $subtotal += $nilai;

            $jenis = strtolower((string) $barang[$id]->jenis);
            if ($jenis === 'frame') {
                $adaFrame = true;
            }
            if ($jenis === 'lensa') {
                $barisLensa++;
                $qtyLensa += $qty;
                $subtotalLensa += $nilai;
            }
        }

        // Aturan lensa: satu nota satu jenis lensa, dihitung per biji (1 = satu mata, 2 = sepasang)
        if ($barisLensa > 1 || $qtyLensa > 2) {
            return back()->withInput()->with('error', 'Satu nota hanya boleh berisi satu jenis lensa dengan jumlah 1 atau 2 biji.');
        }

        $diskon = (float) ($data['diskon'] ?? 0);
        if ($diskon > $subtotal) {
            return back()->withInput()->with('error', 'Diskon tidak boleh melebihi subtotal belanja.');
        }
        $total = $subtotal - $diskon;

        $bayarDp     = ! empty($data['bayar_dp']);
        $buatPesanan = ! empty($data['buat_pesanan']);

        // DP: hanya untuk nota berisi frame + lensa. Nominal tetap = harga lensa (diskon tidak mengubahnya).
        if ($bayarDp) {
            if (! $adaFrame || $barisLensa < 1) {
                return back()->withInput()->with('error', 'DP hanya untuk transaksi yang berisi frame dan lensa.');
            }
            if ($total <= $subtotalLensa) {
                return back()->withInput()->with('error', 'DP tidak tersedia karena total bayar tidak lebih besar dari harga lensa. Silakan bayar penuh.');
            }
            $buatPesanan = true;   // transaksi DP selalu disertai pesanan pengerjaan
        }

        $dibayarSekarang = $bayarDp ? (float) $subtotalLensa : (float) $total;

        $uang = null;
        if ($data['metode_pembayaran'] === 'tunai') {
            $uang = (float) ($data['uang_diterima'] ?? 0);
            if ($uang < $dibayarSekarang) {
                return back()->withInput()->with('error', $bayarDp
                    ? 'Uang yang diterima kurang dari nominal DP.'
                    : 'Uang yang diterima kurang dari total bayar.');
            }
        }

        // Nota berisi lensa atau berpesanan tetap "berjalan" sampai pesanannya selesai/batal
        // (atau ditutup manual di halaman nota). Selain itu langsung selesai.
        $status = ($barisLensa > 0 || $buatPesanan) ? 'berjalan' : 'selesai';

        try {
            [$penjualan, $pesanan] = DB::transaction(function () use (
                $data, $jumlahPerBarang, $barang, $diskon, $total, $uang, $dibayarSekarang,
                $bayarDp, $buatPesanan, $status, $stok, $pesananService
            ) {
                // Nomor nota: INV-ddmmyyyy-urutan di hari itu
                $urut  = Penjualan::whereDate('tanggal', now()->toDateString())->count() + 1;
                $nomor = 'INV-' . now()->format('dmY') . '-' . $urut;

                $p = Penjualan::create([
                    'nomor_nota'        => $nomor,
                    'tanggal'           => now()->toDateString(),
                    'customer_id'       => $data['customer_id'],
                    'user_id'           => Auth::id(),
                    'metode_pembayaran' => $data['metode_pembayaran'],
                    'diskon'            => $diskon,
                    'total'             => $total,
                    'uang_diterima'     => $uang,
                    'status'            => $status,
                ]);

                foreach ($jumlahPerBarang as $id => $qty) {
                    // FIFO: kurangi stok, kurangi batch, catat mutasi, dan kembalikan HPP baris ini
                    $hpp = $stok->keluarFifo($id, $qty, 'terjual', 'penjualan', $p->id);

                    $p->detail()->create([
                        'barang_id'  => $id,
                        'jumlah'     => $qty,
                        'harga_jual' => $barang[$id]->harga_jual,
                        'hpp'        => $hpp,   // total modal untuk baris ini (bukan per unit)
                    ]);
                }

                $p->pembayaran()->create([
                    'jenis'         => $bayarDp ? 'dp' : 'penuh',
                    'metode'        => $data['metode_pembayaran'],
                    'jumlah'        => $dibayarSekarang,
                    'uang_diterima' => $uang,
                    'dibayar_pada'  => now(),
                    'user_id'       => Auth::id(),
                ]);

                $pesanan = $buatPesanan
                    ? $pesananService->buatDariNota($p, $data['pesanan'] ?? [], Auth::id())
                    : null;

                return [$p, $pesanan];
            });
        } catch (RuntimeException $e) {
            // Misalnya stok tidak cukup atau ukuran pesanan belum sesuai: semua perubahan otomatis dibatalkan
            return back()->withInput()->with('error', $e->getMessage());
        }

        $pesan = "Transaksi {$penjualan->nomor_nota} berhasil disimpan.";
        if ($pesanan) {
            $pesan .= " Pesanan {$pesanan->nomor_pesanan} dicatat.";
        }

        return redirect()->route('penjualan.show', $penjualan)->with('success', $pesan);
    }

    public function show(Penjualan $penjualan)
    {
        $penjualan->load([
            'customer', 'kasir', 'detail.barang', 'pesanan',
            'pembayaran' => fn ($q) => $q->with('kasir')->orderBy('id'),
        ]);

        return view('penjualan.show', compact('penjualan'));
    }

    /** Menutup nota berstatus "berjalan" yang sudah lunas dan memang tidak memerlukan pesanan. */
    public function tutup(Penjualan $penjualan)
    {
        $penjualan->loadMissing('pesanan');

        if ($penjualan->status !== 'berjalan' || $penjualan->pesanan || $penjualan->sisa_bayar > 0) {
            return back()->with('error', 'Nota ini tidak bisa ditutup tanpa pesanan.');
        }

        $penjualan->update(['status' => 'selesai']);

        return back()->with('success', "Nota {$penjualan->nomor_nota} ditandai selesai.");
    }
}