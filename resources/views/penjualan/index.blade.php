@extends('layouts.app')

@section('title', 'Riwayat Penjualan')

@push('styles')
<style>
    .badge-nota {
        display: inline-block;
        font-size: .72rem;
        border: 1px solid;
        border-radius: 4px;
        padding: 2px 8px;
    }
    .badge-nota.st-berjalan { background: #fff1cc; color: #8a5a00; border-color: #f0c35a; }
    .badge-nota.st-selesai  { background: #d6f5d6; color: #1b7a1b; border-color: #8fd48f; }
    .badge-nota.st-batal    { background: #fddede; color: #a31515; border-color: #f0a0a0; }
</style>
@endpush

@section('content')
@php
    $metode = ['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'kartu' => 'Kartu'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Riwayat Penjualan</h1>
    <a href="{{ route('penjualan.create') }}" class="btn-abu"><i class="bi bi-plus-lg"></i> Transaksi Baru</a>
</div>

<div class="tabel-abu">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>No. Nota</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Kasir</th>
                    <th>Metode</th>
                    <th>Total</th>
                    <th>Pembayaran</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($penjualan as $p)
                    <tr>
                        <td class="mono">{{ $p->nomor_nota }}</td>
                        <td>{{ $p->tanggal->format('d M Y') }}</td>
                        <td class="fw-bold">{{ $p->customer->nama ?? 'Umum' }}</td>
                        <td>{{ $p->kasir->nama ?? '-' }}</td>
                        <td>{{ $metode[$p->metode_pembayaran] ?? '-' }}</td>
                        <td class="fw-bold">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                        <td>
                            @if ($p->status === 'batal')
                                <span class="text-muted">DP hangus</span>
                            @elseif ($p->sisa_bayar > 0)
                                <span class="text-danger">DP, sisa Rp {{ number_format($p->sisa_bayar, 0, ',', '.') }}</span>
                            @else
                                Lunas
                            @endif
                        </td>
                        <td>
                            <span class="badge-nota st-{{ $p->status }}">{{ $p->status_label }}</span>
                        </td>
                        <td><a href="{{ route('penjualan.show', $p) }}" class="btn-restock">Lihat Nota</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-muted py-4">Belum ada transaksi penjualan.</td>
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