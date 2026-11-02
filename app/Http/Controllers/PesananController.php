<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\PesananKacamata;
use App\Models\Refraksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $query = PesananKacamata::with(['customer', 'refraksi', 'frame', 'lensa', 'petugas', 'pencatat']);

        if ($cari !== '') {
            $query->where(function ($w) use ($cari) {
                $w->where('nomor_pesanan', 'like', "%{$cari}%")
                  ->orWhereHas('customer', function ($c) use ($cari) {
                      $c->where('nama', 'like', "%{$cari}%")
                        ->orWhere(Customer::KOLOM_WA, 'like', "%{$cari}%");
                  });
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

        return view('pesanan.index', [
            'pesanan'   => $pesanan,
            'statistik' => $statistik,
            'cari'      => $cari,
            'status'    => $status,
            // Data untuk modal "Buat Pesanan Baru"
            'customer'  => Customer::with('refraksi')->orderBy('nama')->get(),
            'frame'     => $this->daftarBarang('frame'),
            'lensa'     => $this->daftarBarang('lensa'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'      => ['required', 'exists:customer,id'],
            'refraksi_id'      => ['required', 'exists:refraksi,id'],
            'frame_id'         => ['nullable', 'exists:barang,id'],
            'lensa_id'         => ['required', 'exists:barang,id'],
            'estimasi_selesai' => ['nullable', 'date', 'after_or_equal:today'],
            'keterangan'       => ['nullable', 'string', 'max:255'],
        ], [
            'customer_id.required'            => 'Pelanggan belum dipilih.',
            'refraksi_id.required'            => 'Resep (data refraksi) belum dipilih.',
            'lensa_id.required'               => 'Lensa belum dipilih.',
            'estimasi_selesai.after_or_equal' => 'Estimasi selesai tidak boleh sebelum hari ini.',
        ]);

        // Resep harus milik pelanggan yang dipilih
        $resepSesuai = Refraksi::where('id', $data['refraksi_id'])
            ->where('customer_id', $data['customer_id'])
            ->exists();

        if (! $resepSesuai) {
            return back()->withInput()->withErrors(['refraksi_id' => 'Data refraksi tidak sesuai dengan pelanggan yang dipilih.']);
        }

        if (! empty($data['frame_id']) && ! $this->punyaJenis((int) $data['frame_id'], 'frame')) {
            return back()->withInput()->withErrors(['frame_id' => 'Barang yang dipilih bukan frame.']);
        }

        if (! $this->punyaJenis((int) $data['lensa_id'], 'lensa')) {
            return back()->withInput()->withErrors(['lensa_id' => 'Barang yang dipilih bukan lensa.']);
        }

        $pesanan = DB::transaction(function () use ($data) {
            // Nomor pesanan: ORD-ddmmyyyy-urutan di hari itu
            $urut  = PesananKacamata::whereDate('created_at', now()->toDateString())->count() + 1;
            $nomor = 'ORD-' . now()->format('dmY') . '-' . $urut;

            return PesananKacamata::create([
                'nomor_pesanan'    => $nomor,
                'customer_id'      => $data['customer_id'],
                'refraksi_id'      => $data['refraksi_id'],
                'frame_id'         => $data['frame_id'] ?? null,
                'lensa_id'         => $data['lensa_id'],
                'estimasi_selesai' => $data['estimasi_selesai'] ?? null,
                'keterangan'       => $data['keterangan'] ?? null,
                'status'           => 'menunggu',
                'dibuat_oleh'      => Auth::id(),
            ]);
        });

        return redirect()->route('pesanan.index')
            ->with('success', "Pesanan {$pesanan->nomor_pesanan} berhasil dicatat.");
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
                    throw new RuntimeException("Pesanan {$p->nomor_pesanan} sudah diambil petugas lain.");
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
     * Ubah status ke depan saja: dikerjakan -> siap_diambil -> selesai.
     * - siap_diambil: hanya petugas yang mengerjakan (atau Owner).
     * - selesai (pelanggan sudah mengambil): petugas mana pun.
     */
    public function ubahStatus(Request $request, PesananKacamata $pesanan)
    {
        $request->validate([
            'status' => ['required', Rule::in(['siap_diambil', 'selesai'])],
        ]);

        $baru = $request->input('status');

        try {
            DB::transaction(function () use ($pesanan, $baru) {
                $p = PesananKacamata::lockForUpdate()->findOrFail($pesanan->id);

                /** @var \App\Models\User $user */
                $user = Auth::user();   // komentar @var di atas supaya editor mengenali isOwner()

                if ($baru === 'siap_diambil') {
                    if ($p->status !== 'dikerjakan') {
                        throw new RuntimeException('Hanya pesanan yang sedang dikerjakan yang bisa diubah menjadi Siap Diambil.');
                    }
                    if ((int) $p->petugas_id !== (int) $user->id && ! $user->isOwner()) {
                        throw new RuntimeException('Pesanan ini sedang dikerjakan petugas lain.');
                    }

                    $p->update(['status' => 'siap_diambil', 'siap_diambil_pada' => now()]);

                    // TODO modul Notifikasi WhatsApp: kirim pesan "kacamata siap diambil" ke pelanggan di sini
                    return;
                }

                if ($p->status !== 'siap_diambil') {
                    throw new RuntimeException('Hanya pesanan yang siap diambil yang bisa diselesaikan.');
                }

                $p->update(['status' => 'selesai', 'selesai_pada' => now()]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $label = PesananKacamata::STATUS[$baru];

        return back()->with('success', "Pesanan {$pesanan->nomor_pesanan} diubah menjadi {$label}.");
    }

    /** Daftar barang menurut jenis (frame/lensa). Huruf besar/kecil di kolom jenis diabaikan. */
    private function daftarBarang(string $jenis)
    {
        return Barang::whereRaw('LOWER(jenis) = ?', [$jenis])
            ->orderBy('merk')
            ->orderBy('kode_model')
            ->get();
    }

    private function punyaJenis(int $barangId, string $jenis): bool
    {
        return Barang::whereKey($barangId)->whereRaw('LOWER(jenis) = ?', [$jenis])->exists();
    }
}