<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Refraksi;
use Illuminate\Http\Request;

class RefraksiController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $aturan = [
            'tanggal_periksa' => ['required', 'date', 'before_or_equal:today'],
            'pd_total'        => ['nullable', 'numeric', 'between:40,90'],
        ];

        foreach (['od', 'os'] as $sisi) {
            $aturan[$sisi . '_sphere']   = ['nullable', 'numeric', 'between:-20,20'];
            $aturan[$sisi . '_cylinder'] = ['nullable', 'numeric', 'between:-10,10'];
            $aturan[$sisi . '_axis']     = ['nullable', 'integer', 'between:0,180'];
            $aturan[$sisi . '_add']      = ['nullable', 'numeric', 'between:0,10'];
            $aturan[$sisi . '_mono_pd']  = ['nullable', 'numeric', 'between:10,50'];
        }

        $data = $request->validate($aturan);

        // Minimal satu nilai pemeriksaan harus diisi (selain tanggal)
        $adaNilai = collect($data)
            ->except('tanggal_periksa')
            ->contains(fn ($v) => $v !== null && $v !== '');

        if (! $adaNilai) {
            return back()
                ->withErrors(['nilai' => 'Isi minimal satu nilai hasil pemeriksaan.'])
                ->withInput();
        }

        $data['customer_id'] = $customer->id;
        $data['user_id'] = $request->user()->id;

        Refraksi::create($data);

        // buka_customer: supaya panel detail pelanggan ini terbuka lagi setelah halaman dimuat ulang
        return back()->with('buka_customer', $customer->id);
    }
}