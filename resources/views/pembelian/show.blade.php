@extends('layouts.app')

@section('title', 'Hasil Penerimaan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="page-title">Hasil Penerimaan {{ $pembelian->nomor_po }}</h1>
        <div class="text-muted" style="font-size:.85rem">
            Supplier: {{ $pembelian->supplier->nama }} &nbsp;|&nbsp;
            Dipesan: {{ $pembelian->tanggal->format('d M Y') }} &nbsp;|&nbsp;
            Diverifikasi: {{ $pembelian->diterima_pada?->format('d M Y H:i') ?? '-' }}
        </div>
    </div>
    <a href="{{ route('pembelian.index') }}" class="btn-abu"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="card mb-4">
    <div class="table-responsive">
        <table class="table mb-0 align-middle" style="font-size:.85rem">
            <thead class="table-light">
                <tr>
                    <th>Barang</th>
                    <th class="text-end">Dipesan</th>
                    <th class="text-end">Diterima</th>
                    <th class="text-end">Cacat</th>
                    <th class="text-end">Masuk Stok</th>
                    <th class="text-end">Harga Beli</th>
                    <th>Keterangan Cacat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pembelian->detail as $d)
                    <tr>
                        <td>{{ $d->barang->merk }} <span class="mono text-muted">({{ $d->barang->kode_model }})</span></td>
                        <td class="text-end">{{ $d->jumlah_dipesan }}</td>
                        <td class="text-end">{{ $d->jumlah_diterima }}</td>
                        <td class="text-end {{ $d->jumlah_cacat > 0 ? 'text-danger fw-semibold' : '' }}">{{ $d->jumlah_cacat }}</td>
                        <td class="text-end">{{ $d->jumlah_diterima - $d->jumlah_cacat }}</td>
                        <td class="text-end">Rp {{ number_format($d->harga_beli, 0, ',', '.') }}</td>
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
                    <li>{{ $rd->jumlah }} pcs {{ $rd->barang->merk }} ({{ $rd->barang->kode_model }}) - status: {{ $r->status }}</li>
                @endforeach
            @endforeach
        </ul>
    </div>
@endif
@endsection