<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    private function aturan(?Supplier $supplier = null): array
    {
        return [
            'nama_perusahaan' => [
                'required', 'string', 'max:255',
                Rule::unique('supplier', 'nama_perusahaan')->ignore($supplier?->id),
            ],
            'wa_perusahaan'   => ['nullable', 'string', 'max:20'],
            'email'           => ['nullable', 'email', 'max:255'],
            'alamat'          => ['nullable', 'string'],
            'kontak'          => ['nullable', 'array'],
            'kontak.*.id'     => ['nullable', 'integer'],
            'kontak.*.nama'   => ['nullable', 'string', 'max:255'],
            'kontak.*.wa'     => ['nullable', 'string', 'max:20'],
        ];
    }

    private function pesan(): array
    {
        return [
            'nama_perusahaan.unique' => 'Supplier dengan nama perusahaan ini sudah ada. '
                . 'Untuk menambah orang yang bisa dihubungi, tambahkan contact person pada supplier tersebut.',
        ];
    }

    // Spasi di awal/akhir dibuang dan spasi ganda dijadikan satu, supaya "PT  Maju" dan "PT Maju" dianggap sama
    private function siapkan(Request $request): void
    {
        $request->merge([
            'nama_perusahaan' => preg_replace('/\s+/', ' ', trim((string) $request->input('nama_perusahaan'))),
        ]);
    }

    /**
     * Merapikan daftar contact person dari form. Baris kosong dibuang.
     * Nomor WA tanpa nama dan nama kembar dalam satu supplier ditolak.
     */
    private function bersihkanKontak(array $kontak): array
    {
        $hasil = [];
        $sudah = [];

        foreach ($kontak as $i => $k) {
            $nama = preg_replace('/\s+/', ' ', trim((string) ($k['nama'] ?? '')));
            $wa   = trim((string) ($k['wa'] ?? ''));

            if ($nama === '' && $wa === '') {
                continue;
            }
            if ($nama === '') {
                throw ValidationException::withMessages([
                    "kontak.{$i}.nama" => 'Nama contact person wajib diisi kalau nomor WA diisi.',
                ]);
            }

            $kunci = mb_strtolower($nama);
            if (isset($sudah[$kunci])) {
                throw ValidationException::withMessages([
                    "kontak.{$i}.nama" => "Contact person \"{$nama}\" diisi lebih dari satu kali.",
                ]);
            }
            $sudah[$kunci] = true;

            $hasil[] = [
                'id'   => ! empty($k['id']) ? (int) $k['id'] : null,
                'nama' => $nama,
                'wa'   => $wa !== '' ? $wa : null,
            ];
        }

        return $hasil;
    }

    // Samakan contact person di database dengan isi form: perbarui, tambah, dan hapus yang dibuang
    private function simpanKontak(Supplier $supplier, array $kontak): void
    {
        $dipertahankan = [];

        foreach ($kontak as $k) {
            $baris = $k['id'] ? $supplier->kontak()->whereKey($k['id'])->first() : null;

            if ($baris) {
                $baris->update(['nama' => $k['nama'], 'wa' => $k['wa']]);
            } else {
                $baris = $supplier->kontak()->create(['nama' => $k['nama'], 'wa' => $k['wa']]);
            }

            $dipertahankan[] = $baris->id;
        }

        $supplier->kontak()->whereNotIn('id', $dipertahankan)->delete();
    }

    public function index(Request $request)
    {
        $supplier = Supplier::with('kontak')
            ->when($request->q, function ($query, $cari) {
                $query->where(function ($w) use ($cari) {
                    $w->where('nama_perusahaan', 'like', "%{$cari}%")
                      ->orWhereHas('kontak', fn ($k) => $k->where('nama', 'like', "%{$cari}%"));
                });
            })
            ->orderBy('nama_perusahaan')
            ->paginate(10)
            ->withQueryString();

        // Semua supplier (bukan hanya halaman ini): dipakai pengecekan nama di form
        // dan untuk membuka form ubah dari supplier di halaman mana pun.
        $daftarSupplier = Supplier::with('kontak')
            ->orderBy('nama_perusahaan')
            ->get()
            ->map(fn ($s) => [
                'id'              => $s->id,
                'nama_perusahaan' => $s->nama_perusahaan,
                'wa_perusahaan'   => $s->wa_perusahaan,
                'email'           => $s->email,
                'alamat'          => $s->alamat,
                'update'          => route('supplier.update', $s),
                'kontak'          => $s->kontak->map(fn ($k) => [
                    'id' => $k->id, 'nama' => $k->nama, 'wa' => $k->wa,
                ])->values(),
            ])
            ->values();

        return view('supplier.index', compact('supplier', 'daftarSupplier'));
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store(Request $request)
    {
        $this->siapkan($request);
        $data   = $request->validate($this->aturan(), $this->pesan());
        $kontak = $this->bersihkanKontak($data['kontak'] ?? []);

        DB::transaction(function () use ($data, $kontak) {
            $supplier = Supplier::create(collect($data)->except('kontak')->all());
            $this->simpanKontak($supplier, $kontak);
        });

        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('supplier.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->siapkan($request);
        $data   = $request->validate($this->aturan($supplier), $this->pesan());
        $kontak = $this->bersihkanKontak($data['kontak'] ?? []);

        DB::transaction(function () use ($supplier, $data, $kontak) {
            $supplier->update(collect($data)->except('kontak')->all());
            $this->simpanKontak($supplier, $kontak);
        });

        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();
        } catch (QueryException $e) {
            return back()->with('error', 'Supplier tidak bisa dihapus karena sudah punya riwayat pembelian.');
        }

        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil dihapus.');
    }
}