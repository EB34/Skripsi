<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangController extends Controller
{
    // Satu aturan untuk tambah dan ubah. Saat ubah, barang itu sendiri dikecualikan dari cek "unik".
    private function aturan(?Barang $barang = null): array
    {
        return [
            'kode_model' => ['required', 'string', 'max:255', Rule::unique('barang', 'kode_model')->ignore($barang?->id)],
            'jenis'      => ['required', Rule::in(['frame', 'lensa', 'softlens'])],
            'merk'       => ['required', 'string', 'max:255'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function index(Request $request)
    {
        $barang = Barang::query()
            ->when($request->q, function ($query, $cari) {
                $query->where(function ($w) use ($cari) {
                    $w->where('kode_model', 'like', "%{$cari}%")
                    ->orWhere('merk', 'like', "%{$cari}%");
                });
            })
            ->when($request->jenis, fn ($query, $jenis) => $query->where('jenis', $jenis))
            ->orderBy('kode_model')
            ->paginate(12)
            ->withQueryString();

        return view('barang.index', compact('barang'));
    }

    public function store(Request $request)
    {
        Barang::create($request->validate($this->aturan()));

        return redirect()->route('barang.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(Request $request, Barang $barang)
    {
        $barang->update($request->validate($this->aturan($barang)));

        return redirect()->route('barang.index')->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(Barang $barang)
    {
        try {
            $barang->delete();
        } catch (QueryException $e) {
            return back()->with('error', 'Barang tidak bisa dihapus karena sudah dipakai di transaksi atau riwayat stok.');
        }

        return redirect()->route('barang.index')->with('success', 'Barang berhasil dihapus.');
    }
}