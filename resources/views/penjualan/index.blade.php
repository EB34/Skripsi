@extends('layouts.app')

@section('title', 'Riwayat Penjualan')

@section('content')
@php
    $metode = ['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'kartu' => 'Kartu'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Riwayat Penjualan</h1>
    <a href="{{ route('penjualan.create') }}" class="btn-abu"><i class="bi bi-plus-lg"></i> Transaksi Baru</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" style="font-size:.85rem">
            <thead class="table-light">
                <tr>
                    <th>No. Nota</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Kasir</th>
                    <th>Metode</th>
                    <th class="text-end">Total</th>
                    <th style="width: 110px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($penjualan as $p)
                    <tr>
                        <td class="mono">{{ $p->nomor_nota }}</td>
                        <td>{{ $p->tanggal->format('d M Y') }}</td>
                        <td>{{ $p->customer->nama ?? 'Umum' }}</td>
                        <td>{{ $p->kasir->nama ?? '-' }}</td>
                        <td>{{ $metode[$p->metode_pembayaran] }}</td>
                        <td class="text-end">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                        <td><a href="{{ route('penjualan.show', $p) }}" class="btn-abu">Lihat Nota</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada transaksi penjualan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $penjualan->links() }}
</div>
@endsection