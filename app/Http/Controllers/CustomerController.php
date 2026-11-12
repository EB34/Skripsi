<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Database\QueryException;

class CustomerController extends Controller
{
    private function aturan(): array
    {
        return [
            'nama'        => ['required', 'string', 'max:255'],
            'no_whatsapp' => ['nullable', 'regex:/^[0-9+\s\-]{9,20}$/'],
        ];
    }

    private function pesan(): array
    {
        return ['no_whatsapp.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890'];
    }

    // 0812-345-678 / +62 812 345 678 / 812345678  ->  62812345678
    private function rapikanNomor(?string $nomor): ?string
    {
        if (! $nomor) {
            return null;
        }

        $nomor = preg_replace('/\D/', '', $nomor);   // buang semua selain angka

        if (str_starts_with($nomor, '0')) {
            $nomor = '62' . substr($nomor, 1);
        } elseif (str_starts_with($nomor, '8')) {
            $nomor = '62' . $nomor;
        }

        return $nomor;
    }

    // "John   Smith " -> "John Smith"
    private function rapikanNama(string $nama): string
    {
        return preg_replace('/\s+/', ' ', trim($nama));
    }

    /**
     * Mencari pelanggan yang namanya atau nomor WA-nya sama.
     * Parameter null berarti bagian itu tidak diperiksa. $kecualiId dipakai saat ubah data.
     * Mengembalikan daftar (array kosong kalau tidak ada) untuk ditampilkan di popup.
     */
    private function cariKembar(?string $nama, ?string $wa, ?int $kecualiId = null): array
    {
        if ($nama === null && $wa === null) {
            return [];
        }

        return Customer::query()
            ->withCount('penjualan')
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->where(function ($q) use ($nama, $wa) {
                if ($nama !== null) {
                    $q->orWhere('nama', $nama);
                }
                if ($wa !== null) {
                    $q->orWhere('no_whatsapp', $wa);
                }
            })
            ->orderBy('nama')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'nama'      => $c->nama,
                'wa'        => $c->no_whatsapp,
                'transaksi' => $c->penjualan_count,
                'sama_nama' => $nama !== null && mb_strtolower($c->nama) === mb_strtolower($nama),
                'sama_wa'   => $wa !== null && $c->no_whatsapp === $wa,
            ])
            ->all();
    }

    public function index(Request $request)
    {
        $customer = Customer::query()
            ->when($request->q, function ($query, $cari) {
                $query->where(function ($w) use ($cari) {
                    $w->where('nama', 'like', "%{$cari}%")
                    ->orWhere('no_whatsapp', 'like', "%{$cari}%");
                });
            })
            ->with('refraksi.user', 'penjualan.detail.barang')
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return view('customer.index', compact('customer'));
    }

    public function create()
    {
        return view('customer.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan(), $this->pesan());
        $data['nama']        = $this->rapikanNama($data['nama']);
        $data['no_whatsapp'] = $this->rapikanNomor($data['no_whatsapp'] ?? null);

        // Nama/nomor yang sama boleh dipakai lagi, tapi harus dikonfirmasi lewat popup dulu
        if (! $request->boolean('konfirmasi_duplikat')) {
            $kembar = $this->cariKembar($data['nama'], $data['no_whatsapp']);

            if ($kembar) {
                return back()->withInput()->with('duplikat', $kembar);
            }
        }

        Customer::create($data);

        return redirect()->route('customer.index')->with('success', 'Customer berhasil ditambahkan.');
    }

    public function edit(Customer $customer)
    {
        return view('customer.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate($this->aturan(), $this->pesan());
        $data['nama']        = $this->rapikanNama($data['nama']);
        $data['no_whatsapp'] = $this->rapikanNomor($data['no_whatsapp'] ?? null);

        // Hanya bagian yang diubah yang diperiksa, supaya pelanggan yang kebetulan punya "kembaran"
        // tidak memunculkan popup setiap kali datanya diedit.
        if (! $request->boolean('konfirmasi_duplikat')) {
            $kembar = $this->cariKembar(
                $data['nama'] !== $customer->nama ? $data['nama'] : null,
                $data['no_whatsapp'] !== $customer->no_whatsapp ? $data['no_whatsapp'] : null,
                $customer->id
            );

            if ($kembar) {
                return back()->withInput()->with('duplikat', $kembar);
            }
        }

        $customer->update($data);

        return redirect()->route('customer.index')->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();
        } catch (QueryException $e) {
            return back()->with('error', 'Customer tidak bisa dihapus karena sudah punya pesanan kacamata.');
        }

        return redirect()->route('customer.index')->with('success', 'Customer berhasil dihapus.');
    }
}