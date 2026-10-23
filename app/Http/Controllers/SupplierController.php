<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    private function aturan(): array
    {
        return [
            'nama_perusahaan' => ['required', 'string', 'max:255'],
            'nama_kontak'     => ['nullable', 'string', 'max:255'],
            'wa_kontak'       => ['nullable', 'string', 'max:20'],
            'wa_perusahaan'   => ['nullable', 'string', 'max:20'],
            'email'           => ['nullable', 'email', 'max:255'],
            'alamat'          => ['nullable', 'string'],
        ];
    }

    public function index(Request $request)
    {
        $supplier = Supplier::query()
            ->when($request->q, fn ($query, $cari) => $query->where('nama_perusahaan', 'like', "%{$cari}%"))
            ->orderBy('nama_perusahaan')
            ->paginate(10)
            ->withQueryString();

        return view('supplier.index', compact('supplier'));
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store(Request $request)
    {
        Supplier::create($request->validate($this->aturan()));

        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('supplier.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($request->validate($this->aturan()));

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