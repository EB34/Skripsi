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

    /* Panel pencarian (sama dengan tab Riwayat Mutasi Stok) */
    .panel-filter {
        display: flex; align-items: center; gap: 16px;
        background: #efefef;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
    .select-kategori {
        height: 38px; border: 0; border-radius: 8px;
        padding: 0 10px; font-size: .8rem; background: #fff; max-width: 200px;
    }
    .btn-reset {
        font-size: .78rem; color: #555; white-space: nowrap;
        text-decoration: none;
    }
    .btn-reset:hover { color: #000; text-decoration: underline; }
    .label-kategori { font-size: .78rem; color: #555; white-space: nowrap; }

    /* Ukuran lensa/softlens (SPH / CYL) */
    .ukuran-lensa { font-weight: 600; color: #1f5f87; white-space: nowrap; }
</style>
@endpush

@section('content')
@php
    $labelKategori = \App\Models\KategoriBarang::LABEL;
@endphp
<div class="container-fluid">
    @include('stok.header', ['aktif' => 'monitoring'])

    {{-- Pencarian: kode SKU, merk, nama barang, kategori (material/tipe), atau ukuran SPH. Plus filter kategori. --}}
    <form method="GET" action="{{ route('stok.index') }}" class="panel-filter">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" name="cari" value="{{ request('cari') }}"
                   placeholder="Cari SKU, nama barang, merk, material/tipe, atau ukuran SPH">
        </div>

        @if ($daftarKategori->isNotEmpty())
            <span class="label-kategori">Material/Tipe:</span>
            <select name="kategori" class="select-kategori" onchange="this.form.submit()">
                <option value="">Semua material/tipe</option>
                @foreach ($daftarKategori->groupBy('jenis') as $jenisGrup => $grup)
                    <optgroup label="{{ $labelKategori[$jenisGrup] ?? ucfirst($jenisGrup) }}">
                        @foreach ($grup as $k)
                            <option value="{{ $k->id }}" @selected((string) request('kategori') === (string) $k->id)>{{ $k->nama }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        @endif

        @if(request('cari') || request('kategori'))
            <a href="{{ route('stok.index') }}" class="btn-reset">Reset</a>
        @endif
    </form>

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
                        <th>Material/Tipe</th>
                        <th>Ukuran</th>
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
                        <td>{{ $b->kategori?->nama ?? '-' }}</td>
                        <td>
                            @if ($b->ukuran_label)
                                <span class="ukuran-lensa">{{ $b->ukuran_label }}</span>
                            @else
                                -
                            @endif
                        </td>
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
                    <tr>
                        <td colspan="10" class="text-muted py-4">
                            @if(request('cari'))
                                Tidak ada barang yang cocok dengan "{{ request('cari') }}".
                            @else
                                Belum ada data barang.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftarBarang->links() }}</div>
</div>

@include('stok.modal_lapor')
@endsection