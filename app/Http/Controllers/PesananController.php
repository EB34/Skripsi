<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Penjualan;
use App\Models\PesananKacamata;
use App\Services\PesananService;
use App\Services\StokPesananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        $cari   = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        if (! array_key_exists($status, PesananKacamata::STATUS)) {
            $status = '';
        }

        $query = PesananKacamata::with([
            'customer', 'refraksi', 'frame', 'lensa', 'petugas', 'pencatat',
            'penjualan' => fn ($q) => $q->withSum('pembayaran', 'jumlah'),
        ]);

        if ($cari !== '') {
            // Nomor yang diketik 0812... dicocokkan juga dengan format tersimpan 62812...
            $angka = preg_replace('/\D/', '', $cari);
            if (str_starts_with($angka, '0')) {
                $angka = '62' . substr($angka, 1);
            }

            $query->where(function ($w) use ($cari, $angka) {
                $w->where('nomor_pesanan', 'like', "%{$cari}%")
                  ->orWhereHas('customer', function ($c) use ($cari, $angka) {
                      $c->where('nama', 'like', "%{$cari}%")
                        ->orWhere(Customer::KOLOM_WA, 'like', "%{$cari}%");
                      if ($angka !== '') {
                          $c->orWhere(Customer::KOLOM_WA, 'like', "%{$angka}%");
                      }
                  })
                  ->orWhereHas('penjualan', fn ($n) => $n->where('nomor_nota', 'like', "%{$cari}%"));
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $pesanan = $query->latest('id')->paginate(15)->withQueryString();

        // Kartu statistik selalu menghitung semua pesanan (tidak ikut filter)
        $hitung = PesananKacamata::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statistik = [
            'total'        => (int) $hitung->sum(),
            'menunggu'     => (int) ($hitung['menunggu'] ?? 0),
            'dikerjakan'   => (int) ($hitung['dikerjakan'] ?? 0),
            'siap_diambil' => (int) ($hitung['siap_diambil'] ?? 0),
            'selesai'      => (int) ($hitung['selesai'] ?? 0),
        ];

        // Nota yang boleh dibuatkan pesanan: masih berjalan, belum punya pesanan, ada pelanggan, berisi frame atau lensa
        $notaTersedia = Penjualan::with(['customer', 'detail.barang'])
            ->where('status', 'berjalan')
            ->whereNotNull('customer_id')
            ->whereDoesntHave('pesanan')
            ->whereHas('detail.barang', fn ($q) => $q->whereRaw('LOWER(jenis) in (?, ?)', ['frame', 'lensa']))
            ->latest('id')
            ->limit(100)
            ->get();

        $customerNota = Customer::with('refraksi')
            ->whereIn('id', $notaTersedia->pluck('customer_id')->unique())
            ->get();

        return view('pesanan.index', [
            'pesanan'             => $pesanan,
            'statistik'           => $statistik,
            'cari'                => $cari,
            'status'              => $status,
            'notaTersedia'        => $notaTersedia,
            'refraksiPerCustomer' => PesananService::refraksiPerCustomer($customerNota),
        ]);
    }

    /** Tombol "Buat Pesanan Baru": pesanan dibuat dari nota yang sudah ada. */
    public function store(Request $request, PesananService $layanan)
    {
        $data = $request->validate(array_merge([
            'penjualan_id' => ['required', 'exists:penjualan,id'],
        ], PesananService::aturan()), [
            'penjualan_id.required' => 'Nota belum dipilih.',
        ], PesananService::namaAtribut());

        $nota = Penjualan::findOrFail($data['penjualan_id']);

        try {
            $pesanan = $layanan->buatDariNota($nota, $data['pesanan'] ?? [], Auth::id());
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['pesanan_gagal' => $e->getMessage()]);
        }

        return redirect()->route('pesanan.index')
            ->with('success', "Pesanan {$pesanan->nomor_pesanan} dicatat untuk nota {$nota->nomor_nota}.");
    }

    /**
     * Self-assignment: petugas mengambil pesanan dengan akunnya sendiri.
     * Status berubah menjadi "dikerjakan" dan pengerjaan tercatat atas nama petugas itu.
     */
    public function ambil(PesananKacamata $pesanan)
    {
        try {
            DB::transaction(function () use ($pesanan) {
                // Kunci baris supaya dua petugas yang menekan tombol bersamaan tidak sama-sama berhasil
                $p = PesananKacamata::lockForUpdate()->findOrFail($pesanan->id);

                if ($p->status !== 'menunggu' || $p->petugas_id !== null) {
                    throw new RuntimeException("Pesanan {$p->nomor_pesanan} sudah diambil petugas lain atau tidak lagi menunggu.");
                }

                $p->update([
                    'petugas_id'      => Auth::id(),
                    'status'          => 'dikerjakan',
                    'dikerjakan_pada' => now(),
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Pesanan {$pesanan->nomor_pesanan} kini Anda kerjakan.");
    }

    /**
     * Dikerjakan -> Siap Diambil. Hanya petugas yang mengerjakan (atau Owner).
     * Status "Selesai" tidak lewat sini, tetapi lewat selesaikan() karena disertai pelunasan.
     */
    public function ubahStatus(Request $request, PesananKacamata $pesanan)
    {
        if ($request->input('status') !== 'siap_diambil') {
            return back()->with('error', 'Perubahan status tidak valid.');
        }

        try {
            DB::transaction(function () use ($pesanan) {
                $p = PesananKacamata::lockForUpdate()->findOrFail($pesanan->id);

                /** @var \App\Models\User $user */
                $user = Auth::user();   // komentar @var di atas supaya editor mengenali isOwner()

                if ($p->status !== 'dikerjakan') {
                    throw new RuntimeException('Hanya pesanan yang sedang dikerjakan yang bisa diubah menjadi Siap Diambil.');
                }
                if ((int) $p->petugas_id !== (int) $user->id && ! $user->isOwner()) {
                    throw new RuntimeException('Pesanan ini sedang dikerjakan petugas lain.');
                }

                $p->update(['status' => 'siap_diambil', 'siap_diambil_pada' => now()]);

                // TODO modul Notifikasi WhatsApp: kirim pesan "kacamata siap diambil" ke pelanggan di sini
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Pesanan {$pesanan->nomor_pesanan} diubah menjadi Siap Diambil.");
    }

    /**
     * Pelanggan mengambil barang: sisa pembayaran dilunasi saat itu juga, lalu pesanan dan nota selesai.
     * Semua dalam satu transaksi database.
     */
    public function selesaikan(Request $request, PesananKacamata $pesanan)
    {
        $v = Validator::make($request->all(), [
            'metode_pembayaran' => ['nullable', Rule::in(['tunai', 'qris', 'transfer', 'kartu'])],
            'uang_diterima'     => ['nullable', 'numeric', 'min:0'],
        ]);
        if ($v->fails()) {
            return back()->with('error', $v->errors()->first());
        }

        $kembalian = null;
        $dilunasi  = 0.0;

        try {
            DB::transaction(function () use ($pesanan, $request, &$kembalian, &$dilunasi) {
                $p = PesananKacamata::lockForUpdate()->findOrFail($pesanan->id);

                if ($p->status !== 'siap_diambil') {
                    throw new RuntimeException('Hanya pesanan yang siap diambil yang bisa diselesaikan.');
                }

                $nota    = Penjualan::lockForUpdate()->findOrFail($p->penjualan_id);
                $dibayar = (float) $nota->pembayaran()->sum('jumlah');
                $sisa    = max((float) $nota->total - $dibayar, 0);

                if ($sisa > 0) {
                    $metode = $request->input('metode_pembayaran');
                    if (! $metode) {
                        throw new RuntimeException('Pilih metode pembayaran untuk pelunasan.');
                    }

                    $uang = null;
                    if ($metode === 'tunai') {
                        $uang = (float) $request->input('uang_diterima', 0);
                        if ($uang < $sisa) {
                            throw new RuntimeException('Uang yang diterima kurang dari sisa pembayaran.');
                        }
                        $kembalian = $uang - $sisa;
                    }

                    $nota->pembayaran()->create([
                        'jenis'         => 'pelunasan',
                        'metode'        => $metode,
                        'jumlah'        => $sisa,
                        'uang_diterima' => $uang,
                        'dibayar_pada'  => now(),
                        'user_id'       => Auth::id(),
                    ]);

                    $dilunasi = $sisa;
                }

                $p->update(['status' => 'selesai', 'selesai_pada' => now()]);
                $nota->update(['status' => 'selesai']);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pesan = "Pesanan {$pesanan->nomor_pesanan} selesai.";
        if ($dilunasi > 0) {
            $pesan .= ' Pelunasan Rp ' . number_format($dilunasi, 0, ',', '.') . ' diterima.';
            if ($kembalian !== null && $kembalian > 0) {
                $pesan .= ' Kembalian Rp ' . number_format($kembalian, 0, ',', '.') . '.';
            }
        }

        return back()->with('success', $pesan);
    }

    /**
     * Pembatalan pesanan yang masih berstatus DP: DP hangus (tidak dikembalikan), nota ikut berstatus batal.
     * Lensa tidak dikembalikan ke stok. Frame boleh dikembalikan ke stok kalau petugas mencentangnya.
     */
    public function batalkan(Request $request, PesananKacamata $pesanan, StokPesananService $stok)
    {
        $v = Validator::make($request->all(), [
            'alasan_batal'     => ['required', 'string', 'max:255'],
            'kembalikan_frame' => ['nullable', 'boolean'],
        ], [
            'alasan_batal.required' => 'Alasan pembatalan wajib diisi.',
        ]);
        if ($v->fails()) {
            return back()->with('error', $v->errors()->first());
        }

        $alasan     = trim((string) $request->input('alasan_batal'));
        $kembalikan = $request->boolean('kembalikan_frame');
        $frameBalik = false;

        try {
            DB::transaction(function () use ($pesanan, $alasan, $kembalikan, $stok, &$frameBalik) {
                $p = PesananKacamata::lockForUpdate()->findOrFail($pesanan->id);

                if (in_array($p->status, ['selesai', 'batal'], true)) {
                    throw new RuntimeException("Pesanan {$p->nomor_pesanan} sudah " . strtolower(PesananKacamata::STATUS[$p->status]) . ', tidak bisa dibatalkan.');
                }

                $nota    = Penjualan::lockForUpdate()->findOrFail($p->penjualan_id);
                $dibayar = (float) $nota->pembayaran()->sum('jumlah');
                $sisa    = max((float) $nota->total - $dibayar, 0);

                if ($sisa <= 0) {
                    throw new RuntimeException('Pesanan ini sudah lunas. Pembatalan dan pengembalian dana untuk pesanan lunas ditangani lewat Modul Retur (belum tersedia).');
                }

                if ($kembalikan) {
                    if (! $p->frame_id) {
                        throw new RuntimeException('Pesanan ini memakai frame bawaan pelanggan, jadi tidak ada frame toko yang bisa dikembalikan ke stok.');
                    }

                    $baris = $nota->detail()->where('barang_id', $p->frame_id)->lockForUpdate()->first();
                    if (! $baris) {
                        throw new RuntimeException('Baris frame pada nota tidak ditemukan.');
                    }

                    $bisaKembali = (int) $baris->jumlah - (int) $baris->jumlah_kembali;
                    if ($bisaKembali < 1) {
                        throw new RuntimeException('Frame pada nota ini sudah pernah dikembalikan ke stok.');
                    }

                    // Harga beli batch baru = HPP rata-rata per unit saat frame dijual
                    $hppPerUnit = $baris->jumlah > 0 ? round((float) $baris->hpp / (int) $baris->jumlah, 2) : 0.0;

                    $stok->kembalikanKeStok(
                        (int) $baris->barang_id,
                        $bisaKembali,
                        $hppPerUnit,
                        'penjualan',
                        $nota->id,
                        "Frame dikembalikan ke stok, pesanan {$p->nomor_pesanan} dibatalkan",
                        Auth::id()
                    );

                    $baris->increment('jumlah_kembali', $bisaKembali);
                    $frameBalik = true;
                }

                $p->update([
                    'status'             => 'batal',
                    'alasan_batal'       => $alasan,
                    'dibatalkan_pada'    => now(),
                    'dibatalkan_oleh'    => Auth::id(),
                    'frame_dikembalikan' => $frameBalik,
                ]);
                $nota->update(['status' => 'batal']);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pesan = "Pesanan {$pesanan->nomor_pesanan} dibatalkan, DP hangus.";
        if ($frameBalik) {
            $pesan .= ' Frame dikembalikan ke stok.';
        }

        return back()->with('success', $pesan);
    }
}