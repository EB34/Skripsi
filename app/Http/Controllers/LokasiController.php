<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\StokLokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LokasiController extends Controller
{
    public function index()
    {
        $daftarLokasi = Lokasi::withSum('stokLokasi as total_stok', 'jumlah')
            ->orderByDesc('is_gudang')
            ->orderBy('id')
            ->get();

        return view('lokasi.index', compact('daftarLokasi'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:lokasi,nama'],
        ], [
            'nama.required' => 'Nama lokasi wajib diisi.',
            'nama.unique'   => 'Nama lokasi sudah dipakai.',
        ]);

        Lokasi::create([
            'nama'      => trim($data['nama']),
            'is_gudang' => $request->boolean('is_gudang'),
        ]);

        return redirect()->route('lokasi.index')->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function update(Request $request, Lokasi $lokasi)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('lokasi', 'nama')->ignore($lokasi->id)],
        ], [
            'nama.required' => 'Nama lokasi wajib diisi.',
            'nama.unique'   => 'Nama lokasi sudah dipakai.',
        ]);

        $jadiGudang = $request->boolean('is_gudang');

        // Minimal harus ada satu gudang karena barang masuk (PO) selalu ke gudang
        if ($lokasi->is_gudang && !$jadiGudang && $this->gudangLain($lokasi) === 0) {
            return back()->with('error', 'Minimal harus ada satu gudang. Jadikan lokasi lain sebagai gudang dulu.');
        }

        $lokasi->update([
            'nama'      => trim($data['nama']),
            'is_gudang' => $jadiGudang,
        ]);

        return redirect()->route('lokasi.index')->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Lokasi $lokasi)
    {
        if ($lokasi->is_gudang && $this->gudangLain($lokasi) === 0) {
            return back()->with('error', 'Gudang terakhir tidak bisa dihapus.');
        }

        $totalStok = (int) StokLokasi::where('lokasi_id', $lokasi->id)->sum('jumlah');

        if ($totalStok > 0) {
            return back()->with(
                'error',
                "Lokasi {$lokasi->nama} masih berisi {$totalStok} unit barang. Pindahkan dulu lewat Pindah Lokasi."
            );
        }

        DB::transaction(function () use ($lokasi) {
            // Baris stok berjumlah 0 tidak berarti apa-apa, bersihkan supaya FK tidak menghalangi
            StokLokasi::where('lokasi_id', $lokasi->id)->delete();
            $lokasi->delete();
        });

        return redirect()->route('lokasi.index')->with('success', 'Lokasi berhasil dihapus.');
    }

    /** Jumlah gudang selain lokasi ini. */
    private function gudangLain(Lokasi $lokasi): int
    {
        return Lokasi::where('is_gudang', true)->where('id', '!=', $lokasi->id)->count();
    }
}