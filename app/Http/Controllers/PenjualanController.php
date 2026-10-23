<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Penjualan;
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
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15);

        return view('penjualan.index', compact('penjualan'));
    }

    public function create()
    {
        return view('penjualan.create', [
            'barang'   => Barang::orderBy('jenis')->orderBy('kode_model')->get(),
            'customer' => Customer::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request, StokService $stok)
    {
        $data = $request->validate([
            'customer_id'        => ['required', 'exists:customer,id'],
            'metode_pembayaran'  => ['required', Rule::in(['tunai', 'qris', 'transfer', 'kartu'])],
            'diskon'             => ['nullable', 'numeric', 'min:0'],
            'uang_diterima'      => ['nullable', 'numeric', 'min:0'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.barang_id'  => ['required', 'exists:barang,id'],
            'items.*.jumlah'     => ['required', 'integer', 'min:1'],
        ], [
            'customer_id.required' => 'Customer belum dipilih.',
            'items.required' => 'Keranjang masih kosong.',
            'items.min'      => 'Keranjang masih kosong.',
        ]);

        // Gabungkan baris barang yang sama menjadi satu jumlah
        $jumlahPerBarang = [];
        foreach ($data['items'] as $item) {
            $id = (int) $item['barang_id'];
            $jumlahPerBarang[$id] = ($jumlahPerBarang[$id] ?? 0) + (int) $item['jumlah'];
        }

        // Harga SELALU diambil dari database, bukan dari kiriman browser
        $barang = Barang::whereIn('id', array_keys($jumlahPerBarang))->get()->keyBy('id');

        $subtotal = 0;
        foreach ($jumlahPerBarang as $id => $qty) {
            $subtotal += $barang[$id]->harga_jual * $qty;
        }

        $diskon = (float) ($data['diskon'] ?? 0);
        if ($diskon > $subtotal) {
            return back()->withInput()->with('error', 'Diskon tidak boleh melebihi subtotal belanja.');
        }
        $total = $subtotal - $diskon;

        $uang = null;
        if ($data['metode_pembayaran'] === 'tunai') {
            $uang = (float) ($data['uang_diterima'] ?? 0);
            if ($uang < $total) {
                return back()->withInput()->with('error', 'Uang yang diterima kurang dari total bayar.');
            }
        }

        try {
            $penjualan = DB::transaction(function () use ($data, $jumlahPerBarang, $barang, $diskon, $total, $uang, $stok) {
                // Nomor nota: INV-ddmmyyyy-urutan di hari itu
                $urut  = Penjualan::whereDate('tanggal', now()->toDateString())->count() + 1;
                $nomor = 'INV-' . now()->format('dmY') . '-' . $urut;

                $p = Penjualan::create([
                    'nomor_nota'        => $nomor,
                    'tanggal'           => now()->toDateString(),
                    'customer_id'       => $data['customer_id'] ?? null,
                    'user_id'           => Auth::id(),
                    'metode_pembayaran' => $data['metode_pembayaran'],
                    'diskon'            => $diskon,
                    'total'             => $total,
                    'uang_diterima'     => $uang,
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

                return $p;
            });
        } catch (RuntimeException $e) {
            // Misalnya stok tidak cukup: semua perubahan di atas otomatis dibatalkan
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('penjualan.show', $penjualan)
            ->with('success', "Transaksi {$penjualan->nomor_nota} berhasil disimpan.");
    }

    public function show(Penjualan $penjualan)
    {
        $penjualan->load(['customer', 'kasir', 'detail.barang']);

        return view('penjualan.show', compact('penjualan'));
    }
}