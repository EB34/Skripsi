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

    // Spasi ganda dijadikan satu supaya "FR  001" dan "FR 001" tidak lolos sebagai dua kode berbeda
    private function siapkan(Request $request): void
    {
        $request->merge([
            'kode_model' => preg_replace('/\s+/', ' ', trim((string) $request->input('kode_model'))),
            'merk'       => preg_replace('/\s+/', ' ', trim((string) $request->input('merk'))),
        ]);
    }

    // Pesan "kode sudah dipakai" menyebut barang mana yang memakainya
    private function pesan(Request $request, ?Barang $barang = null): array
    {
        $kode = (string) $request->input('kode_model');

        $pemakai = $kode === '' ? null : Barang::where('kode_model', $kode)
            ->when($barang, fn ($q) => $q->where('id', '!=', $barang->id))
            ->first();

        return [
            'kode_model.unique' => $pemakai
                ? "Kode model {$pemakai->kode_model} sudah dipakai oleh barang {$pemakai->merk}. "
                    . 'Gunakan kode lain, atau tambah stoknya lewat Pembelian Supplier.'
                : 'Kode model sudah dipakai barang lain.',
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
        $this->siapkan($request);
        Barang::create($request->validate($this->aturan(), $this->pesan($request)));

        return redirect()->route('barang.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(Request $request, Barang $barang)
    {
        $this->siapkan($request);
        $barang->update($request->validate($this->aturan($barang), $this->pesan($request, $barang)));

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