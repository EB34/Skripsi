@extends('layouts.app')

@push('styles')
<style>
    .banner-menipis {
        display: flex; align-items: center; gap: 10px;
        background: #fff4c4;
        border: 1px solid #e6c84a;
        border-radius: 8px;
        padding: 10px 16px;
        margin-bottom: 16px;
        font-size: .8rem;
        color: #1e1e1e;
    }
    .banner-menipis i { color: #d4a000; font-size: 1.05rem; }

    .ind-teks { display: flex; justify-content: space-between; font-size: .6rem; margin-bottom: 3px; }
    .ind-track { height: 4px; background: #e6e6e6; border-radius: 999px; overflow: hidden; }
    .ind-bar { height: 100%; border-radius: 999px; }
    .ind-aman .ind-teks { color: #2e9e2e; }   .ind-aman .ind-bar { background: #86d986; }
    .ind-menipis .ind-teks { color: #d98a1b; } .ind-menipis .ind-bar { background: #f2b45a; }
    .ind-habis .ind-teks { color: #d23c3c; }   .ind-habis .ind-bar { background: #ef7b7b; }

    .btn-restock {
        display: inline-block;
        background: #d9d9d9; color: #1e1e1e;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: .75rem; font-weight: 500;
        text-decoration: none;
    }
    .btn-restock:hover { background: #c9c9c9; color: #000; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('stok.header', ['aktif' => 'monitoring'])

    @if($jumlahMenipis > 0)
        <div class="banner-menipis">
            <i class="bi bi-exclamation-triangle"></i>
            <span class="mono">Ada {{ $jumlahMenipis }} Barang Mencapai/Di bawah batas Minimum Stok</span>
        </div>
    @endif

    <div class="tabel-mockup">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Stok Fisik</th>
                        <th>Batas Min</th>
                        <th style="min-width: 170px;">Indikator Stok</th>
                        <th>Lokasi</th>
                        <th>Action Button</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($daftarBarang as $b)
                    @php
                        $status = $b->status_stok;
                        $label  = ['aman' => 'Stok Aman', 'menipis' => 'Stok Menipis', 'habis' => 'Stok Habis'][$status];
                        // Bar penuh jika stok >= 4x batas minimum
                        $persen = min(100, (int) round($b->stok_tersedia / max($b->stok_minimum * 4, 1) * 100));
                        $lokasiIsi = $b->stokLokasi->where('jumlah', '>', 0);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('stok.show', $b->id) }}" class="mono text-dark text-decoration-none">
                                {{ $b->kode_model }}
                            </a>
                        </td>
                        <td class="fw-bold">{{ ucfirst($b->jenis) }} {{ $b->merk }}</td>
                        <td>{{ $b->kategori_label }}</td>
                        <td class="fw-bold">{{ $b->stok_tersedia }}</td>
                        <td>{{ $b->stok_minimum }}</td>
                        <td>
                            <div class="ind-{{ $status }}">
                                <div class="ind-teks">
                                    <span>{{ $label }}</span>
                                    <span>{{ $b->stok_tersedia }} Unit</span>
                                </div>
                                <div class="ind-track">
                                    <div class="ind-bar" @style(['width: ' . $persen . '%'])></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @forelse($lokasiIsi as $sl)
                                <div>
                                    {{ $sl->lokasi->nama }}
                                    @if($lokasiIsi->count() > 1)
                                        <span class="text-muted">({{ $sl->jumlah }})</span>
                                    @endif
                                </div>
                            @empty
                                -
                            @endforelse
                        </td>
                        <td>
                            <a href="{{ route('pembelian.index') }}" class="btn-restock">+ Restock PO</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted py-4">Belum ada data barang.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftarBarang->links() }}</div>
</div>

@include('stok.modal_lapor')
@endsection