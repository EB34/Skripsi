@extends('layouts.app')

@section('title', 'Nota Penjualan')

@push('styles')
<style>
    .nota { max-width: 440px; }
    .nota table { font-size: .82rem; }
    @media print {
        .topbar, .sidebar, .no-print { display: none !important; }
        .content { padding: 0 !important; }
        .nota { border: 0 !important; max-width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $metode = [
        'tunai'    => 'Tunai',
        'qris'     => 'QRIS',
        'transfer' => 'Transfer Bank',
        'kartu'    => 'Kartu Debit/Kredit',
    ];
    $bisaTutup = $penjualan->status === 'berjalan' && ! $penjualan->pesanan && $penjualan->sisa_bayar <= 0;
@endphp

<div class="no-print d-flex gap-2 mb-3 flex-wrap">
    <a href="{{ route('penjualan.create') }}" class="btn-abu"><i class="bi bi-plus-lg"></i> Transaksi Baru</a>
    <a href="{{ route('penjualan.index') }}" class="btn-abu"><i class="bi bi-clock-history"></i> Riwayat</a>
    <button type="button" class="btn-abu" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Nota</button>
    @if ($penjualan->pesanan)
        <a href="{{ route('pesanan.index', ['q' => $penjualan->pesanan->nomor_pesanan]) }}" class="btn-abu">
            <i class="bi bi-eyeglasses"></i> Pesanan {{ $penjualan->pesanan->nomor_pesanan }}
        </a>
    @endif
    @if ($bisaTutup)
        <form action="{{ route('penjualan.tutup', $penjualan) }}" method="POST"
              onsubmit="return confirm('Tandai nota ini selesai tanpa pesanan pengerjaan?')">
            @csrf
            <button type="submit" class="btn-abu"><i class="bi bi-check2-circle"></i> Selesaikan Tanpa Pesanan</button>
        </form>
    @endif
</div>

<div class="card nota">
    <div class="card-body">
        <div class="text-center mb-3">
            <h5 class="mb-0 fw-bold">Solo Optik</h5>
            <div class="text-muted" style="font-size:.75rem">Nota Penjualan</div>
        </div>

        <table class="table table-borderless table-sm mb-2">
            <tr><td>No. Nota</td><td class="text-end mono">{{ $penjualan->nomor_nota }}</td></tr>
            <tr><td>Tanggal</td><td class="text-end">{{ $penjualan->tanggal->format('d M Y') }}</td></tr>
            <tr><td>Pelanggan</td><td class="text-end">{{ $penjualan->customer->nama ?? 'Umum' }}</td></tr>
            <tr><td>Kasir</td><td class="text-end">{{ $penjualan->kasir->nama ?? '-' }}</td></tr>
            <tr><td>Status</td><td class="text-end">{{ $penjualan->status_label }}</td></tr>
        </table>

        <table class="table table-sm align-middle">
            <thead>
                <tr><th>Barang</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr>
            </thead>
            <tbody>
                @foreach ($penjualan->detail as $d)
                    <tr>
                        <td>
                            {{ $d->barang->merk }}<br><span class="mono text-muted" style="font-size:.7rem">{{ $d->barang->kode_model }}</span>
                            @if ($d->keterangan)
                                <br><span style="font-size:.72rem">{{ $d->keterangan }}</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $d->jumlah }}</td>
                        <td class="text-end">{{ number_format($d->harga_jual, 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($d->harga_jual * $d->jumlah, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="table table-borderless table-sm mb-2">
            <tr><td>Subtotal</td><td class="text-end">Rp {{ number_format($penjualan->subtotal, 0, ',', '.') }}</td></tr>
            <tr><td>Diskon</td><td class="text-end">- Rp {{ number_format($penjualan->diskon, 0, ',', '.') }}</td></tr>
            <tr class="fw-bold"><td>Total Bayar</td><td class="text-end">Rp {{ number_format($penjualan->total, 0, ',', '.') }}</td></tr>
        </table>

        <div class="fw-semibold mb-1" style="font-size:.8rem">Pembayaran</div>
        <table class="table table-sm align-middle mb-2">
            <tbody>
                @foreach ($penjualan->pembayaran as $b)
                    <tr>
                        <td>
                            {{ \App\Models\PembayaranPenjualan::JENIS[$b->jenis] ?? $b->jenis }}
                            <div class="text-muted" style="font-size:.7rem">
                                {{ $b->dibayar_pada->format('d M Y H:i') }} · {{ $metode[$b->metode] ?? $b->metode }}
                                @if ($b->metode === 'tunai' && $b->uang_diterima !== null)
                                    · diterima Rp {{ number_format($b->uang_diterima, 0, ',', '.') }},
                                    kembali Rp {{ number_format($b->uang_diterima - $b->jumlah, 0, ',', '.') }}
                                @endif
                            </div>
                        </td>
                        <td class="text-end">Rp {{ number_format($b->jumlah, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="table table-borderless table-sm mb-0">
            <tr><td>Total Dibayar</td><td class="text-end">Rp {{ number_format($penjualan->total_dibayar, 0, ',', '.') }}</td></tr>
            @if ($penjualan->status === 'batal')
                <tr class="fw-bold"><td>Dibatalkan</td><td class="text-end">DP hangus</td></tr>
            @elseif ($penjualan->sisa_bayar > 0)
                <tr class="fw-bold text-danger"><td>Sisa Pembayaran</td><td class="text-end">Rp {{ number_format($penjualan->sisa_bayar, 0, ',', '.') }}</td></tr>
            @else
                <tr class="fw-bold"><td>Status Bayar</td><td class="text-end">Lunas</td></tr>
            @endif
        </table>

        <div class="text-center text-muted mt-3" style="font-size:.72rem">Terima kasih atas kunjungan Anda</div>
    </div>
</div>
@endsection