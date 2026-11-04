@extends('layouts.app')

@section('title', 'Log Notifikasi')

@push('styles')
<style>
    .banner-info {
        background: #fff8c4;
        border: 1px solid #e0c34a;
        color: #8a6d00;
        border-radius: 8px;
        padding: 12px 20px;
        font-size: .74rem;
        text-align: center;
        line-height: 1.6;
    }
    .panel-filter { background: var(--abu-panel); border-radius: 12px; padding: 16px 18px; }
    .panel-filter .form-select,
    .panel-filter .kolom-cari { height: 40px; border: 0; border-radius: 8px; font-size: .82rem; }
    .kolom-cari { background: #fff; width: 100%; padding: 0 12px 0 38px; outline: none; }
    .kotak-cari { position: relative; flex: 1; }
    .kotak-cari i.ikon-kiri { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #777; }
    .btn-cari {
        height: 40px; width: 44px; border: 0; border-radius: 8px;
        background: #fff; color: #111; font-size: 1rem;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .btn-cari:hover { background: #f0f0f0; }

    .tabel-log { border: 1px solid #d9d9d9; border-radius: 16px; overflow: hidden; background: #fff; }
    .tabel-log table { margin: 0; }
    .tabel-log thead th {
        background: #efefef; text-align: center; font-size: .85rem; font-weight: 700;
        padding: 16px 12px; border-bottom: 1px solid #e0e0e0;
    }
    .tabel-log td { padding: 18px 14px; vertical-align: middle; border-color: #e6e6e6; font-size: .8rem; }
    .tabel-log td + td, .tabel-log th + th { border-left: 1px solid #e6e6e6; }
    .kol-waktu { width: 170px; text-align: center; }
    .kol-penerima { width: 200px; text-align: center; }
    .kol-status { width: 160px; text-align: center; }
    .teks-pesan { white-space: pre-line; word-break: break-word; }
    .penerima-nama { font-weight: 700; font-size: .85rem; }
    .st-terkirim { color: #1b7a1b; font-weight: 600; }
    .st-gagal { color: #c0392b; font-weight: 600; }
    .st-menunggu { color: #777; font-weight: 600; }
    .ket-gagal { font-size: .68rem; color: #777; margin-top: 4px; word-break: break-word; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Log Notifikasi &amp; Integrasi WA</h1>
    <button type="button" class="btn-abu" disabled title="Template pesan belum dibuat">
        <i class="bi bi-pencil-square"></i> Edit Template Chat
    </button>
</div>

<div class="banner-info mb-3">
    Setiap kacamata yang ditandai dengan status "Siap Diambil" pada halaman Pesanan Kacamata akan secara otomatis
    mengirimkan notifikasi kepada customer. Pastikan kacamata telah selesai diproses dan siap diambil sebelum
    mengubah status menjadi "Siap Diambil".
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="chip">Terkirim: <strong>{{ $ringkasan['terkirim'] }}</strong></span>
    <span class="chip">Gagal: <strong>{{ $ringkasan['gagal'] }}</strong></span>
    <span class="chip">Menunggu: <strong>{{ $ringkasan['menunggu'] }}</strong></span>
</div>

<form method="GET" action="{{ route('notifikasi.index') }}" class="panel-filter mb-4">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <select name="status" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
            <option value="">Semua status</option>
            <option value="terkirim" @selected($status === 'terkirim')>Terkirim</option>
            <option value="gagal" @selected($status === 'gagal')>Gagal</option>
            <option value="menunggu" @selected($status === 'menunggu')>Menunggu</option>
        </select>

        <div class="kotak-cari">
            <i class="bi bi-search ikon-kiri"></i>
            <input type="text" name="q" value="{{ $q }}" class="kolom-cari" placeholder="Cari nama, nomor WA, atau isi pesan">
        </div>

        <button type="submit" class="btn-cari" title="Cari"><i class="bi bi-search"></i></button>
    </div>
</form>

@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $e)
            <div>{{ $e }}</div>
        @endforeach
    </div>
@endif

<div class="tabel-log">
    <table class="table align-middle">
        <thead>
            <tr>
                <th class="kol-waktu">Waktu Terkirim</th>
                <th class="kol-penerima">Penerima &amp; No WA</th>
                <th>Isi Pesan</th>
                <th class="kol-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                @php
                    $waktu = $log->dikirim_pada ?? $log->created_at;
                    $nama = $log->customer_id ? ($namaCustomer[$log->customer_id] ?? null) : null;
                    $kelasStatus = match ($log->status) {
                        'terkirim' => 'st-terkirim',
                        'gagal' => 'st-gagal',
                        default => 'st-menunggu',
                    };
                @endphp
                <tr>
                    <td class="kol-waktu mono">{{ $waktu->translatedFormat('d M Y, H.i') }}</td>
                    <td class="kol-penerima">
                        <div class="penerima-nama">{{ $nama ?? 'Tanpa nama' }}</div>
                        <div class="mono" style="font-size: .72rem;">{{ $log->tujuan }}</div>
                    </td>
                    <td class="teks-pesan">{{ $log->pesan }}</td>
                    <td class="kol-status">
                        <div class="{{ $kelasStatus }}">{{ ucfirst($log->status) }}</div>
                        @if($log->status === 'gagal' && $log->respons)
                            <div class="ket-gagal">{{ \Illuminate\Support\Str::limit($log->respons, 80) }}</div>
                        @endif
                        @if($log->status !== 'terkirim')
                            <form method="POST" action="{{ route('notifikasi.kirim-ulang', $log->id) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="btn-abu" style="padding: 4px 12px; font-size: .72rem;">
                                    <i class="bi bi-arrow-repeat"></i> Kirim ulang
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-5">Belum ada log notifikasi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $logs->links('pagination::bootstrap-5') }}
</div>
@endsection