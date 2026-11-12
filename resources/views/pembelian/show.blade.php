@extends('layouts.app')

@section('title', 'Hasil Penerimaan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="page-title">Hasil Penerimaan {{ $pembelian->nomor_po }}</h1>
        <div class="text-muted" style="font-size:.85rem">
            Supplier: {{ $pembelian->supplier->nama_perusahaan }} &nbsp;|&nbsp;
            Dipesan: {{ $pembelian->tanggal->format('d M Y') }} &nbsp;|&nbsp;
            Diverifikasi: {{ $pembelian->diterima_pada?->format('d M Y H:i') ?? '-' }}
            &nbsp;|&nbsp; Invoice supplier: {{ $pembelian->nomor_invoice_supplier ?: '-' }}
        </div>
    </div>
    <a href="{{ route('pembelian.index') }}" class="btn-abu"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="tabel-abu mb-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Barang</th>
                    <th>Dipesan</th>
                    <th>Diterima</th>
                    <th>Cacat</th>
                    <th>Masuk Stok</th>
                    @if ($isOwner)
                        <th>Harga Beli</th>
                    @endif
                    <th>Keterangan Cacat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pembelian->detail as $d)
                    @php $isCustom = $d->detail_penjualan_id !== null; @endphp
                    <tr>
                        <td class="text-start">
                            <span class="fw-bold">{{ $d->barang->merk }}</span>
                            <span class="mono text-muted">({{ $d->barang->kode_model }})</span>
                            @if ($isCustom)
                                <span class="badge bg-secondary">Custom</span>
                                <div class="text-muted" style="font-size:.72rem">
                                    {{ $d->label_nota }}<br>Ukuran: {{ $d->keterangan_ukuran ?: '-' }}
                                    @if ($isOwner && $d->detailPenjualan)
                                        <br>HPP nota terisi: Rp {{ number_format($d->detailPenjualan->hpp, 0, ',', '.') }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>{{ $d->jumlah_dipesan }}</td>
                        <td>{{ $d->jumlah_diterima }}</td>
                        <td class="{{ $d->jumlah_cacat > 0 ? 'text-danger fw-semibold' : '' }}">{{ $d->jumlah_cacat }}</td>
                        <td class="fw-bold">{{ $isCustom ? '-' : $d->jumlah_diterima - $d->jumlah_cacat }}</td>
                        @if ($isOwner)
                            <td>Rp {{ number_format($d->harga_beli, 0, ',', '.') }}</td>
                        @endif
                        <td>{{ $d->keterangan_cacat ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if ($pembelian->catatan_penerima)
    <div class="card mb-4">
        <div class="card-body">
            <div class="fw-semibold mb-1" style="font-size:.85rem">Catatan Penerima</div>
            {{ $pembelian->catatan_penerima }}
        </div>
    </div>
@endif

@if ($pembelian->retur->isNotEmpty())
    <div class="alert alert-warning">
        <strong>Retur ke supplier sudah diajukan</strong> untuk unit cacat:
        <ul class="mb-0">
            @foreach ($pembelian->retur as $r)
                @foreach ($r->detail as $rd)
                    <li>{{ $rd->jumlah }} pcs {{ $rd->barang->merk }} ({{ $rd->barang->kode_model }}) - {{ $r->jenis_label }}, status: {{ $r->status_label }}</li>
                @endforeach
            @endforeach
        </ul>
    </div>
@endif
@endsection