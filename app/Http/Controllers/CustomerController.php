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
            ->paginate(10)
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
        $data['no_whatsapp'] = $this->rapikanNomor($data['no_whatsapp'] ?? null);

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
        $data['no_whatsapp'] = $this->rapikanNomor($data['no_whatsapp'] ?? null);

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
