@extends('layouts.app')

@push('styles')
<style>
    .kartu-rusak {
        display: flex; justify-content: space-between; align-items: center; gap: 16px;
        background: #fff;
        border: 1px solid #d9d9d9;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 16px;
    }
    .kartu-rusak .judul { font-size: .85rem; font-weight: 700; }
    .kartu-rusak .sub { font-size: .7rem; color: #777; }

    .aksi-btn {
        border-radius: 4px;
        padding: 4px 8px;
        font-size: .68rem; line-height: 1.15;
        border: 1px solid transparent;
    }
    .aksi-retur { background: #cfe5f7; border-color: #8fbbe0; color: #1c4e80; }
    .aksi-retur:hover { background: #bcdaf2; }
    .aksi-write { background: #ea8a8a; border-color: #d65f5f; color: #5a0f0f; }
    .aksi-write:hover { background: #e27373; }
    .selesai { color: #1f9d4a; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('stok.header', ['aktif' => 'rusak'])

    <div class="kartu-rusak">
        <div>
            <div class="judul">Daftar Barang Rusak / Cacat</div>
            <div class="sub">
                Pencatatan barang yang dikeluarkan dari stok layak jual dan membutuhkan
                tindak lanjut retur supplier atau write off.
            </div>
        </div>
        <button type="button" class="btn-lapor" data-bs-toggle="modal" data-bs-target="#modalLaporRusak">
            <i class="bi bi-plus-lg"></i> Lapor Barang Rusak
        </button>
    </div>

    <div class="tabel-mockup">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Tanggal &amp; Waktu</th>
                        <th>Nama &amp; SKU Barang</th>
                        <th>Qty</th>
                        <th>Alasan Kerusakan</th>
                        <th>Status Tindak Lanjut</th>
                        <th>Pelapor</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($daftarRusak as $r)
                    <tr>
                        <td class="mono">
                            {{ $r->created_at->locale('id')->translatedFormat('d M Y, H.i') }}
                        </td>
                        <td>
                            <div class="fw-bold">{{ ucfirst($r->barang?->jenis ?? '') }} {{ $r->barang?->merk ?? '-' }}</div>
                            <div class="mono sku-kecil">{{ $r->barang?->kode_model }}</div>
                        </td>
                        <td>{{ $r->jumlah }} pcs</td>
                        <td class="fw-bold">{{ $r->alasan }}</td>
                        <td>{{ $r->status_label }}</td>
                        <td>{{ $r->pelapor?->nama ?? '-' }}</td>
                        <td>
                            @if($r->status === 'menunggu_keputusan')
                                <div class="d-flex gap-1 justify-content-center">
                                    <form method="POST" action="{{ route('stok.rusak.putuskan', $r->id) }}"
                                          onsubmit="return confirm('Tandai barang ini untuk diretur ke supplier?')">
                                        @csrf
                                        <input type="hidden" name="keputusan" value="retur_supplier">
                                        <button class="aksi-btn aksi-retur">Retur<br>Supplier</button>
                                    </form>
                                    <form method="POST" action="{{ route('stok.rusak.putuskan', $r->id) }}"
                                          onsubmit="return confirm('Write off barang ini? Barang dianggap hilang dari persediaan.')">
                                        @csrf
                                        <input type="hidden" name="keputusan" value="write_off">
                                        <button class="aksi-btn aksi-write">Write<br>Off</button>
                                    </form>
                                </div>
                            @else
                                <span class="selesai"><i class="bi bi-check-circle"></i> Selesai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted py-4">Belum ada laporan barang rusak.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftarRusak->links() }}</div>
</div>

@include('stok.modal_lapor')
@endsection