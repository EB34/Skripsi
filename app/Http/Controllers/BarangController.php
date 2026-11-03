<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangController extends Controller
{
    // Ukuran lensa harus kelipatan 0,25 (misalnya -2.25, bukan -2.20)
    private function kelipatanSeperempat(): \Closure
    {
        return function (string $atribut, $nilai, \Closure $gagal) {
            if ($nilai === null || $nilai === '') {
                return;
            }
            if (((int) round((float) $nilai * 100)) % 25 !== 0) {
                $gagal("{$atribut} harus kelipatan 0,25 (contoh: -2.25).");
            }
        };
    }

    // Satu aturan untuk tambah dan ubah. Saat ubah, barang itu sendiri dikecualikan dari cek "unik".
    private function aturan(?Barang $barang = null): array
    {
        return [
            'kode_model' => ['required', 'string', 'max:255', Rule::unique('barang', 'kode_model')->ignore($barang?->id)],
            'jenis'      => ['required', Rule::in(['frame', 'lensa', 'softlens'])],
            'merk'       => ['required', 'string', 'max:255'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'sph'        => ['nullable', 'required_if:jenis,lensa', 'numeric', 'between:-20,20', $this->kelipatanSeperempat()],
            'cyl'        => ['nullable', 'numeric', 'between:-10,10', $this->kelipatanSeperempat()],
        ];
    }

    // Spasi ganda dijadikan satu supaya "FR  001" dan "FR 001" tidak lolos sebagai dua kode berbeda.
    // Ukuran hanya berlaku untuk lensa: jenis lain dikosongkan, CYL lensa yang kosong dianggap 0.
    private function siapkan(Request $request): void
    {
        $request->merge([
            'kode_model' => preg_replace('/\s+/', ' ', trim((string) $request->input('kode_model'))),
            'merk'       => preg_replace('/\s+/', ' ', trim((string) $request->input('merk'))),
        ]);

        if ($request->input('jenis') !== 'lensa') {
            $request->merge(['sph' => null, 'cyl' => null]);
        } elseif ($request->input('cyl') === null || $request->input('cyl') === '') {
            $request->merge(['cyl' => 0]);
        }
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
            'sph.required_if' => 'SPH wajib diisi untuk lensa.',
        ];
    }

    private function namaAtribut(): array
    {
        return ['sph' => 'SPH', 'cyl' => 'CYL'];
    }

    public function index(Request $request)
    {
        $barang = Barang::query()
            ->when($request->q, function ($query, $cari) {
                $query->where(function ($w) use ($cari) {
                    $w->where('kode_model', 'like', "%{$cari}%")
                    ->orWhere('merk', 'like', "%{$cari}%");

                    // Mengetik angka (misalnya -2.25 atau -2,25) mencari lensa dengan SPH itu
                    $angka = str_replace(',', '.', trim($cari));
                    if (is_numeric($angka)) {
                        $w->orWhere('sph', (float) $angka);
                    }
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
        Barang::create($request->validate($this->aturan(), $this->pesan($request), $this->namaAtribut()));

        return redirect()->route('barang.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(Request $request, Barang $barang)
    {
        $this->siapkan($request);
        $barang->update($request->validate($this->aturan($barang), $this->pesan($request, $barang), $this->namaAtribut()));

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