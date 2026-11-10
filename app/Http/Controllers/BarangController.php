<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\KategoriBarang;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BarangController extends Controller
{
    // Jenis barang yang punya ukuran SPH/CYL
    private const JENIS_BERUKURAN = ['lensa', 'softlens'];

    // Ukuran harus kelipatan 0,25 (misalnya -2.25, bukan -2.20)
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

    // Satu aturan untuk tambah dan ubah.
    // Kode model tidak dicek keunikannya: kode yang sama boleh dipakai berulang (misalnya beberapa frame dengan kode sama).
    private function aturan(): array
    {
        return [
            'kode_model' => ['required', 'string', 'max:255'],
            'jenis'      => ['required', Rule::in(['frame', 'lensa', 'softlens'])],
            'merk'       => ['required', 'string', 'max:255'],
            // Kategori opsional, tapi kalau diisi harus kategori dengan jenis yang sama dengan jenis barang
            'kategori_id' => ['nullable', Rule::exists('kategori_barang', 'id')->where('jenis', request('jenis'))],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'tanpa_stok' => ['boolean'],
            // SPH wajib untuk lensa dan softlens, kecuali lensa custom (ukurannya diisi saat transaksi)
            'sph'        => [
                'nullable',
                Rule::requiredIf(fn () => in_array(request('jenis'), self::JENIS_BERUKURAN, true) && ! request()->boolean('tanpa_stok')),
                'numeric', 'between:-20,20', $this->kelipatanSeperempat(),
            ],
            'cyl'        => ['nullable', 'numeric', 'between:-10,10', $this->kelipatanSeperempat()],
            // Gambar produk (opsional). hapus_gambar hanya dipakai saat ubah.
            'gambar'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_gambar' => ['nullable', 'boolean'],
        ];
    }

    // Spasi ganda dijadikan satu supaya "FR  001" dan "FR 001" tidak lolos sebagai dua kode berbeda.
    // Ukuran berlaku untuk lensa dan softlens: frame dikosongkan, CYL yang kosong dianggap 0.
    // "Tanpa stok" (lensa custom) hanya berlaku untuk jenis lensa dan membuat SPH/CYL dikosongkan.
    private function siapkan(Request $request): void
    {
        $request->merge([
            'kode_model' => preg_replace('/\s+/', ' ', trim((string) $request->input('kode_model'))),
            'merk'       => preg_replace('/\s+/', ' ', trim((string) $request->input('merk'))),
        ]);

        $jenis  = $request->input('jenis');
        $custom = $jenis === 'lensa' && $request->boolean('tanpa_stok');

        // Checkbox yang tidak dicentang tidak dikirim browser, jadi nilainya dipastikan true/false di sini
        $request->merge(['tanpa_stok' => $custom]);

        if ($custom || ! in_array($jenis, self::JENIS_BERUKURAN, true)) {
            $request->merge(['sph' => null, 'cyl' => null]);
        } elseif ($request->input('cyl') === null || $request->input('cyl') === '') {
            $request->merge(['cyl' => 0]);
        }
    }

    // Pesan error untuk validasi selain kode model
    private function pesan(): array
    {
        return [
            'sph.required'       => 'SPH wajib diisi untuk lensa dan softlens (kecuali lensa custom).',
            'kategori_id.exists' => 'Kategori tidak sesuai dengan jenis barang.',
            'gambar.image'       => 'File gambar harus berupa gambar (JPG, PNG, atau WEBP).',
            'gambar.mimes'       => 'Format gambar harus JPG, PNG, atau WEBP.',
            'gambar.max'         => 'Ukuran gambar maksimal 2 MB.',
            'gambar.uploaded'    => 'Gambar gagal diunggah. Ukuran file mungkin melebihi batas server (2 MB).',
        ];
    }

    private function namaAtribut(): array
    {
        return ['sph' => 'SPH', 'cyl' => 'CYL', 'kategori_id' => 'Kategori', 'gambar' => 'Gambar'];
    }

    /**
     * Menyimpan file gambar dan menyiapkan kolom `gambar`.
     * - File baru diunggah: disimpan, file lama (kalau ada) dihapus.
     * - Centang "hapus gambar" saat ubah: file lama dihapus, kolom dikosongkan.
     * - Tidak ada keduanya: kolom `gambar` tidak diubah.
     * Objek file dan flag hapus_gambar dikeluarkan dari data supaya tidak ikut masuk ke create/update.
     */
    private function prosesGambar(Request $request, array $data, ?Barang $barang = null): array
    {
        unset($data['gambar'], $data['hapus_gambar']);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('barang', 'public');

            if ($barang?->gambar) {
                Storage::disk('public')->delete($barang->gambar);
            }
        } elseif ($barang && $barang->gambar && $request->boolean('hapus_gambar')) {
            Storage::disk('public')->delete($barang->gambar);
            $data['gambar'] = null;
        }

        return $data;
    }

    public function index(Request $request)
    {
        $barang = Barang::query()
            ->with('kategori')
            ->when($request->q, function ($query, $cari) {
                $query->where(function ($w) use ($cari) {
                    $w->where('kode_model', 'like', "%{$cari}%")
                        ->orWhere('merk', 'like', "%{$cari}%")
                        // Mengetik nama kategori (misalnya "plastik" atau "photochromic") ikut mencari
                        ->orWhereHas('kategori', fn ($k) => $k->where('nama', 'like', "%{$cari}%"));

                    // Mengetik angka (misalnya -2.25 atau -2,25) mencari lensa/softlens dengan SPH itu
                    $angka = str_replace(',', '.', trim($cari));
                    if (is_numeric($angka)) {
                        $w->orWhere('sph', (float) $angka);
                    }
                });
            })
            ->when($request->jenis, fn ($query, $jenis) => $query->where('jenis', $jenis))
            ->when($request->kategori, fn ($query, $id) => $query->where('kategori_id', $id))
            // Diurutkan merk A-Z, lalu kode model A-Z untuk merk yang sama
            ->orderBy('merk')
            ->orderBy('kode_model')
            ->paginate(15)
            ->withQueryString();

        return view('barang.index', [
            'barang'         => $barang,
            'daftarKategori' => KategoriBarang::orderBy('jenis')->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->siapkan($request);
        $data = $request->validate($this->aturan(), $this->pesan(), $this->namaAtribut());

        Barang::create($this->prosesGambar($request, $data));

        return redirect()->route('barang.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(Request $request, Barang $barang)
    {
        $this->siapkan($request);
        $data = $request->validate($this->aturan(), $this->pesan(), $this->namaAtribut());

        $barang->update($this->prosesGambar($request, $data, $barang));

        return redirect()->route('barang.index')->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(Barang $barang)
    {
        $gambar = $barang->gambar;

        try {
            $barang->delete();
        } catch (QueryException $e) {
            return back()->with('error', 'Barang tidak bisa dihapus karena sudah dipakai di transaksi atau riwayat stok.');
        }

        // File hanya dihapus setelah barang benar-benar terhapus dari database
        if ($gambar) {
            Storage::disk('public')->delete($gambar);
        }

        return redirect()->route('barang.index')->with('success', 'Barang berhasil dihapus.');
    }
}