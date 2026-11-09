@extends('layouts.app')

@push('styles')
<style>
    .panel-filter {
        display: flex; align-items: center; gap: 16px;
        background: #efefef;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
    .tipe-label { font-size: .78rem; color: #555; white-space: nowrap; }
    .tipe-btn {
        background: #fff; color: #1e1e1e;
        border: 0; border-radius: 999px;
        min-width: 230px;
        padding: 7px 16px;
        font-size: .8rem;
    }
    .tipe-menu {
        padding: 0;
        border: 1px solid #d9d9d9;
        border-radius: 6px;
        min-width: 260px;
        overflow: hidden;
    }
    .tipe-menu .dropdown-item {
        text-align: center;
        font-size: .8rem;
        padding: 10px 14px;
        border-bottom: 1px solid #e3e3e3;
    }
    .tipe-menu li:last-child .dropdown-item { border-bottom: 0; }
    .tipe-menu .dropdown-item.active { background: #fff3b0; color: #1e1e1e; }
    .tipe-menu .dropdown-item:hover { background: #f4f4f4; }

    .naik { color: #1f9d4a; font-weight: 600; }
    .turun { color: #e03131; font-weight: 600; }
</style>
@endpush

@section('content')
@php
    // Label di dropdown mengikuti mockup (lebih panjang dari label di tabel).
    // Hanya mengganti kunci yang memang ada di MutasiStok::JENIS.
    $labelDropdown = $jenisMutasi;
    foreach ([
        'barang_masuk' => 'Stok Masuk (Pembelian Supplier)',
        'penjualan'    => 'Stok Terjual (POS Kasir)',
    ] as $kunci => $teks) {
        if (isset($labelDropdown[$kunci])) {
            $labelDropdown[$kunci] = $teks;
        }
    }
    $tipeAktif = request('tipe');
@endphp

<div class="container-fluid">
    @include('stok.header', ['aktif' => 'mutasi'])

    <form method="GET" action="{{ route('stok.mutasi') }}" class="panel-filter">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" name="cari" value="{{ request('cari') }}"
                   placeholder="Cari transaksi mutasi, SKU, no ref">
        </div>
        @if($tipeAktif)
            <input type="hidden" name="tipe" value="{{ $tipeAktif }}">
        @endif

        <span class="tipe-label">Tipe Mutasi:</span>
        <div class="dropdown">
            <button class="tipe-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                {{ $labelDropdown[$tipeAktif] ?? 'Semua Tipe Mutasi' }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end tipe-menu">
                <li>
                    <a class="dropdown-item {{ $tipeAktif ? '' : 'active' }}"
                        href="{{ route('stok.mutasi', array_filter(['cari' => request('cari')])) }}">
                        Semua Tipe Mutasi
                    </a>
                </li>
                @foreach($labelDropdown as $kunci => $teks)
                    <li>
                        <a class="dropdown-item {{ $tipeAktif === $kunci ? 'active' : '' }}"
                           href="{{ route('stok.mutasi', array_filter(['cari' => request('cari'), 'tipe' => $kunci])) }}">
                            {{ $teks }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </form>

    <div class="tabel-mockup">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Tanggal &amp; Waktu</th>
                        <th>Nama &amp; SKU Barang</th>
                        <th>Jenis Mutasi</th>
                        <th>Perubahan</th>
                        <th>Stok Akhir</th>
                        <th>No Referensi</th>
                        <th>Keterangan</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($mutasi as $m)
                    <tr>
                        <td class="mono">
                            {{ \Carbon\Carbon::parse($m->tanggal)->locale('id')->translatedFormat('d M Y, H.i') }}
                        </td>
                        <td>
                            <div class="fw-bold">{{ ucfirst($m->barang?->jenis ?? '') }} {{ $m->barang?->merk ?? '-' }}</div>
                            <div class="mono sku-kecil">{{ $m->barang?->kode_model }}</div>
                        </td>
                        <td class="fw-semibold">{{ $m->jenis_label }}</td>
                        <td>
                            @if($m->jumlah > 0)
                                <span class="naik"><i class="bi bi-arrow-up"></i> +{{ $m->jumlah }}</span>
                            @elseif($m->jumlah < 0)
                                <span class="turun"><i class="bi bi-arrow-down"></i> {{ $m->jumlah }}</span>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td>{{ $m->saldo_setelah }}</td>
                        <td class="mono">{{ $m->no_referensi }}</td>
                        <td>{{ $m->keterangan_tampil }}</td>
                        <td>{{ $m->user?->nama ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted py-4">Belum ada mutasi stok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $mutasi->links() }}</div>
</div>

@include('stok.modal_lapor')
@endsection