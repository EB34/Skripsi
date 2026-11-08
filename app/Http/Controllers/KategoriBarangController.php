<?php

namespace App\Http\Controllers;

use App\Models\KategoriBarang;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriBarangController extends Controller
{
    // Spasi ganda dijadikan satu supaya "Blue  Ray" dan "Blue Ray" tidak lolos sebagai dua nama berbeda
    private function bersihkan(Request $request): void
    {
        $request->merge(['nama' => preg_replace('/\s+/', ' ', trim((string) $request->input('nama')))]);
    }

    private function pesan(): array
    {
        return [
            'jenis.required' => 'Jenis wajib dipilih.',
            'jenis.in'       => 'Jenis tidak valid.',
            'nama.required'  => 'Nama kategori wajib diisi.',
            'nama.max'       => 'Nama kategori maksimal 100 karakter.',
            'nama.unique'    => 'Nama kategori itu sudah ada di jenis yang sama.',
        ];
    }

    public function index(Request $request)
    {
        // Tab aktif: frame (bawaan), lensa, atau softlens
        $jenis = $request->input('jenis');
        if (! array_key_exists($jenis, KategoriBarang::JENIS)) {
            $jenis = 'frame';
        }

        $daftar = KategoriBarang::query()
            ->where('jenis', $jenis)
            ->withCount('barang')
            ->when($request->q, fn ($query, $cari) => $query->where('nama', 'like', "%{$cari}%"))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('kategori.index', [
            'daftar' => $daftar,
            'jenis'  => $jenis,
        ]);
    }

    public function store(Request $request)
    {
        $this->bersihkan($request);

        $data = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(KategoriBarang::JENIS))],
            'nama'  => [
                'required', 'string', 'max:100',
                Rule::unique('kategori_barang', 'nama')->where('jenis', $request->input('jenis')),
            ],
        ], $this->pesan());

        KategoriBarang::create($data);

        return redirect()->route('kategori.index', ['jenis' => $data['jenis']])
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, KategoriBarang $kategori)
    {
        $this->bersihkan($request);

        // Jenis tidak bisa diubah karena barang yang memakainya sudah terikat ke jenis itu
        $data = $request->validate([
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('kategori_barang', 'nama')->where('jenis', $kategori->jenis)->ignore($kategori->id),
            ],
        ], $this->pesan());

        $kategori->update($data);

        return redirect()->route('kategori.index', ['jenis' => $kategori->jenis])
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(KategoriBarang $kategori)
    {
        if ($kategori->barang()->exists()) {
            return redirect()->route('kategori.index', ['jenis' => $kategori->jenis])
                ->with('error', "Kategori {$kategori->nama} masih dipakai {$kategori->barang()->count()} barang, jadi tidak bisa dihapus.");
        }

        $kategori->delete();

        return redirect()->route('kategori.index', ['jenis' => $kategori->jenis])
            ->with('success', 'Kategori berhasil dihapus.');
    }
}