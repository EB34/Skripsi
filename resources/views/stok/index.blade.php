@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('stok._header', ['aktif' => 'monitoring'])

    @if($jumlahMenipis > 0)
        <div class="alert alert-warning py-2">
            Ada {{ $jumlahMenipis }} barang mencapai/di bawah batas minimum stok
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>SKU</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th class="text-center">Stok Fisik</th>
                        <th class="text-center">Batas Min</th>
                        <th style="min-width: 160px;">Indikator Stok</th>
                        <th>Lokasi</th>
                        <th class="text-center">Action Button</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($daftarBarang as $b)
                    @php
                        $status = $b->status_stok;
                        $warna  = ['aman' => 'success', 'menipis' => 'warning', 'habis' => 'danger'][$status];
                        $label  = ['aman' => 'Stok Aman', 'menipis' => 'Stok Menipis', 'habis' => 'Stok Habis'][$status];
                        // Bar penuh jika stok >= 4x batas minimum
                        $persen = min(100, (int) round($b->stok_tersedia / max($b->stok_minimum * 4, 1) * 100));
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('stok.show', $b->id) }}" class="text-decoration-none fw-semibold">
                                {{ $b->kode_model }}
                            </a>
                        </td>
                        <td class="fw-semibold">{{ ucfirst($b->jenis) }} {{ $b->merk }}</td>
                        <td>{{ $b->kategori_label }}</td>
                        <td class="text-center fw-bold">{{ $b->stok_tersedia }}</td>
                        <td class="text-center">{{ $b->stok_minimum }}</td>
                        <td>
                            <div class="d-flex justify-content-between small text-{{ $warna }}">
                                <span>{{ $label }}</span>
                                <span>{{ $b->stok_tersedia }} Unit</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-{{ $warna }}" style="width: {{ $persen }}%"></div>
                            </div>
                        </td>
                        <td class="small">
                            @forelse($b->stokLokasi->where('jumlah', '>', 0) as $sl)
                                <div>{{ $sl->lokasi->nama }}: <strong>{{ $sl->jumlah }}</strong></div>
                            @empty
                                -
                            @endforelse
                        </td>
                        <td class="text-center">
                            <a href="{{ \Illuminate\Support\Facades\Route::has('pembelian.index') ? route('pembelian.index') : '#' }}"
                               class="btn btn-sm btn-light border">+ Restock PO</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data barang.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftarBarang->links() }}</div>
</div>

@include('stok._modal_lapor')
@endsection