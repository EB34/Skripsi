<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;
use Carbon\Carbon;

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
        return view('penjualan.create', [
            'barang'   => Barang::orderBy('jenis')->orderBy('kode_model')->get(),
            'customer' => Customer::orderBy('nama')->get(),
        ]);
    }

    /** True kalau nilai berkelipatan 0,25 (toleransi pembulatan desimal). */
    private function kelipatan025($nilai): bool
    {
        $x = (float) $nilai * 4;

        return abs($x - round($x)) < 0.00001;
    }

    public function store(Request $request, StokService $stok)
    {
        $data = $request->validate([
            'customer_id'        => ['required', 'exists:customer,id'],
            'metode_pembayaran'  => ['required', Rule::in(['tunai', 'qris', 'transfer', 'kartu'])],
            'diskon'             => ['nullable', 'numeric', 'min:0'],
            'uang_diterima'      => ['nullable', 'numeric', 'min:0'],
            'bayar_dp'           => ['nullable', 'boolean'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.barang_id'  => ['required', 'exists:barang,id'],
            'items.*.jumlah'     => ['required', 'integer', 'min:1'],
            // Hanya dipakai untuk lensa custom (tanpa stok)
            'items.*.harga'      => ['nullable', 'numeric', 'min:0'],
            'items.*.sph'        => ['nullable', 'numeric', 'between:-20,20'],
            'items.*.cyl'        => ['nullable', 'numeric', 'between:-10,10'],
            'items.*.axis'       => ['nullable', 'integer', 'between:0,180'],
            'items.*.add'        => ['nullable', 'numeric', 'between:0,10'],
            'tanggal_transaksi' => ['nullable', 'date', 'before_or_equal:now'],
        ], [
            'customer_id.required' => 'Customer belum dipilih.',
            'items.required'       => 'Keranjang masih kosong.',
            'items.min'            => 'Keranjang masih kosong.',
        ]);

        $barang = Barang::whereIn('id', collect($data['items'])->pluck('barang_id')->unique())
            ->get()
            ->keyBy('id');

        // Barang biasa digabung per barang. Lensa custom tidak digabung: tiap baris = 1 biji dengan harga/ukuran sendiri.
        $jumlahBiasa = [];
        $barisCustom = [];

        foreach ($data['items'] as $item) {
            $b = $barang[(int) $item['barang_id']];

            if ($b->tanpa_stok) {
                $nama  = "{$b->merk} ({$b->kode_model})";
                $harga = $item['harga'] ?? null;
                $sph   = $item['sph'] ?? null;
                $cyl   = $item['cyl'] ?? null;
                $axis  = $item['axis'] ?? null;
                $add   = $item['add'] ?? null;

                if ($harga === null || $harga === '') {
                    return back()->withInput()->with('error', "Harga untuk {$nama} wajib diisi.");
                }
                if ($sph === null || $sph === '') {
                    return back()->withInput()->with('error', "SPH untuk {$nama} wajib diisi.");
                }
                foreach (['SPH' => $sph, 'CYL' => $cyl, 'ADD' => $add] as $label => $nilai) {
                    if ($nilai !== null && $nilai !== '' && ! $this->kelipatan025($nilai)) {
                        return back()->withInput()->with('error', "{$label} untuk {$nama} harus berkelipatan 0,25 (contoh: -2.25).");
                    }
                }

                // AXIS hanya berarti kalau ada silinder: wajib diisi saat CYL terisi, dikosongkan saat CYL kosong/0
                $adaCyl = $cyl !== null && $cyl !== '' && (float) $cyl != 0.0;
                if ($adaCyl && ($axis === null || $axis === '')) {
                    return back()->withInput()->with('error', "AXIS untuk {$nama} wajib diisi karena CYL terisi.");
                }
                if (! $adaCyl) {
                    $cyl  = ($cyl === null || $cyl === '') ? null : $cyl;
                    $axis = null;
                }

                $barisCustom[] = [
                    'barang'     => $b,
                    'jumlah'     => 1,
                    'harga'      => (float) $harga,
                    'sph'        => $sph,
                    'cyl'        => $cyl,
                    'axis'       => $axis,
                    'add'        => ($add === '' ? null : $add),
                    'keterangan' => DetailPenjualan::susunUkuran($sph, $cyl, $axis, $add),
                    'custom'     => true,
                ];
            } else {
                $jumlahBiasa[$b->id] = ($jumlahBiasa[$b->id] ?? 0) + (int) $item['jumlah'];
            }
        }

        // Baris barang biasa: harga SELALU diambil dari database, bukan dari kiriman browser
        $baris = [];
        foreach ($jumlahBiasa as $id => $qty) {
            $baris[] = [
                'barang'     => $barang[$id],
                'jumlah'     => $qty,
                'harga'      => (float) $barang[$id]->harga_jual,
                'sph'        => null,
                'cyl'        => null,
                'axis'       => null,
                'add'        => null,
                'keterangan' => null,
                'custom'     => false,
            ];
        }
        $baris = array_merge($baris, $barisCustom);

        $subtotal      = 0;
        $adaFrame      = false;
        $adaLensa      = false;
        $subtotalLensa = 0;

        foreach ($baris as $r) {
            $nilai = $r['harga'] * $r['jumlah'];
            $subtotal += $nilai;

            $jenis = strtolower((string) $r['barang']->jenis);
            if ($jenis === 'frame') {
                $adaFrame = true;
            }
            if ($jenis === 'lensa') {
                $adaLensa = true;
                $subtotalLensa += $nilai;
            }
        }

        $diskon = (float) ($data['diskon'] ?? 0);
        if ($diskon > $subtotal) {
            return back()->withInput()->with('error', 'Diskon tidak boleh melebihi subtotal belanja.');
        }
        $total = $subtotal - $diskon;

        $bayarDp = ! empty($data['bayar_dp']);

        // DP: hanya untuk nota berisi frame + lensa. Nominal tetap = harga semua lensa di nota (diskon tidak mengubahnya).
        if ($bayarDp) {
            if (! $adaFrame || ! $adaLensa) {
                return back()->withInput()->with('error', 'DP hanya untuk transaksi yang berisi frame dan lensa.');
            }
            if ($total <= $subtotalLensa) {
                return back()->withInput()->with('error', 'DP tidak tersedia karena total bayar tidak lebih besar dari harga lensa. Silakan bayar penuh.');
            }
        }

        $dibayarSekarang = $bayarDp ? (float) $subtotalLensa : (float) $total;

        // Uang diterima hanya relevan untuk tunai; metode lain (QRIS, transfer, kartu) dibayar pas
        $uang = null;
        if ($data['metode_pembayaran'] === 'tunai') {
            $uang = (float) ($data['uang_diterima'] ?? 0);
            if ($uang < $dibayarSekarang) {
                return back()->withInput()->with('error', $bayarDp
                    ? 'Uang yang diterima kurang dari nominal DP.'
                    : 'Uang yang diterima kurang dari total bayar.');
            }
        }

        // Nota berisi lensa atau DP tetap "berjalan" sampai pesanannya selesai/batal
        // (atau ditutup manual di halaman nota). Selain itu langsung selesai.
        $status = ($adaLensa || $bayarDp) ? 'berjalan' : 'selesai';

        try {
            $penjualan = DB::transaction(function () use (
                $data, $baris, $diskon, $total, $uang, $dibayarSekarang, $bayarDp, $status, $stok
            ) {
                // Nomor nota: INV-ddmmyyyy-urutan di hari itu
                $urut  = Penjualan::whereDate('tanggal', now()->toDateString())->count() + 1;
                $nomor = 'INV-' . now()->format('dmY') . '-' . $urut;
                $waktu = ! empty($data['tanggal_transaksi'])
                ? Carbon::parse($data['tanggal_transaksi'])
                : now();

                $p = Penjualan::create([
                    'nomor_nota'        => $nomor,
                    'tanggal'           => $waktu->toDateString(),
                    'customer_id'       => $data['customer_id'],
                    'user_id'           => Auth::id(),
                    'metode_pembayaran' => $data['metode_pembayaran'],
                    'diskon'            => $diskon,
                    'total'             => $total,
                    'uang_diterima'     => $uang,
                    'status'            => $status,
                    'created_at'         => $waktu,
                    'updated_at'         => $waktu,
                ]);

                foreach ($baris as $r) {
                    $idBarang = $r['barang']->id;

                    // Barang biasa: FIFO mengurangi stok, batch, dan mencatat mutasi, lalu mengembalikan HPP baris ini.
                    // Lensa custom: tidak ada stok yang dipotong. HPP diisi 0 dulu karena harga beli dari supplier belum diketahui.
                    $hpp = $r['custom']
                        ? 0.0
                        : $stok->keluarFifo($idBarang, $r['jumlah'], 'terjual', 'penjualan', $p->id, null, null, $waktu);

                    $p->detail()->create([
                        'barang_id'  => $idBarang,
                        'jumlah'     => $r['jumlah'],
                        'harga_jual' => $r['harga'],
                        'hpp'        => $hpp,   // total modal untuk baris ini (bukan per unit)
                        'keterangan' => $r['keterangan'],
                        'lensa_sph'  => $r['sph'],
                        'lensa_cyl'  => $r['cyl'],
                        'lensa_axis' => $r['axis'],
                        'lensa_add'  => $r['add'],
                    ]);
                }

                $p->pembayaran()->create([
                    'jenis'         => $bayarDp ? 'dp' : 'penuh',
                    'metode'        => $data['metode_pembayaran'],
                    'jumlah'        => $dibayarSekarang,
                    'uang_diterima' => $uang,
                    'dibayar_pada'  => $waktu,
                    'user_id'       => Auth::id(),
                ]);

                return $p;
            });
        } catch (RuntimeException $e) {
            // Misalnya stok tidak cukup: semua perubahan otomatis dibatalkan
            return back()->withInput()->with('error', $e->getMessage());
        }

        $pesan = "Transaksi {$penjualan->nomor_nota} berhasil disimpan.";
        if ($bayarDp) {
            $pesan .= ' DP Rp ' . number_format($dibayarSekarang, 0, ',', '.')
                . ' diterima. Buat pesanannya lewat menu Pesanan Kacamata > Buat Pesanan Baru.';
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