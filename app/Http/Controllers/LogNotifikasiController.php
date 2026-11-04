<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LogNotifikasi;
use App\Services\NotifikasiService;
use Illuminate\Http\Request;

class LogNotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $logs = LogNotifikasi::query()
            ->when(in_array($status, ['menunggu', 'terkirim', 'gagal'], true), fn ($qr) => $qr->where('status', $status))
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($w) use ($q) {
                    $w->where('tujuan', 'like', "%{$q}%")
                        ->orWhere('pesan', 'like', "%{$q}%")
                        ->orWhereIn('customer_id', Customer::where('nama', 'like', "%{$q}%")->pluck('id'));
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Nama penerima diambil sekali untuk semua baris di halaman ini
        $namaCustomer = Customer::whereIn('id', $logs->pluck('customer_id')->filter()->unique())
            ->pluck('nama', 'id');

        $ringkasan = [
            'terkirim' => LogNotifikasi::where('status', 'terkirim')->count(),
            'gagal' => LogNotifikasi::where('status', 'gagal')->count(),
            'menunggu' => LogNotifikasi::where('status', 'menunggu')->count(),
        ];

        return view('notifikasi.index', compact('logs', 'ringkasan', 'status', 'q', 'namaCustomer'));
    }

    public function kirimUlang(LogNotifikasi $log, NotifikasiService $service)
    {
        if ($log->status === 'terkirim') {
            return back()->with('error', 'Pesan ini sudah terkirim.');
        }

        $hasil = $service->kirimUlang($log);

        return $hasil->status === 'terkirim'
            ? back()->with('success', 'Pesan berhasil dikirim ulang.')
            : back()->with('error', 'Pengiriman ulang gagal: ' . $hasil->respons);
    }
}