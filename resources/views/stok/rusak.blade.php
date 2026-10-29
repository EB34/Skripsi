@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('stok._header', ['aktif' => 'rusak'])

    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">Daftar Barang Rusak / Cacat</div>
                <div class="small text-muted">
                    Pencatatan barang yang dikeluarkan dari stok layak jual dan membutuhkan
                    tindak lanjut retur supplier atau write off.
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalLaporRusak">
                + Lapor Barang Rusak
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal &amp; Waktu</th>
                        <th>Nama &amp; SKU Barang</th>
                        <th class="text-center">Qty</th>
                        <th>Alasan Kerusakan</th>
                        <th>Status Tindak Lanjut</th>
                        <th>Pelapor</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($daftarRusak as $r)
                    <tr>
                        <td>{{ $r->created_at->format('d/m/Y H.i') }}</td>
                        <td>
                            <div class="fw-semibold">{{ ucfirst($r->barang?->jenis ?? '') }} {{ $r->barang?->merk ?? '-' }}</div>
                            <div class="small text-muted font-monospace">{{ $r->barang?->kode_model }}</div>
                        </td>
                        <td class="text-center">{{ $r->jumlah }} pcs</td>
                        <td class="fw-semibold">{{ $r->alasan }}</td>
                        <td>{{ $r->status_label }}</td>
                        <td class="small">{{ $r->pelapor?->name ?? $r->pelapor?->nama ?? $r->pelapor?->username ?? '-' }}</td>
                        <td class="text-center">
                            @if($r->status === 'menunggu_keputusan')
                                <div class="d-flex gap-1 justify-content-center">
                                    <form method="POST" action="{{ route('stok.rusak.putuskan', $r->id) }}"
                                          onsubmit="return confirm('Tandai barang ini untuk diretur ke supplier?')">
                                        @csrf
                                        <input type="hidden" name="keputusan" value="retur_supplier">
                                        <button class="btn btn-sm btn-outline-primary">Retur Supplier</button>
                                    </form>
                                    <form method="POST" action="{{ route('stok.rusak.putuskan', $r->id) }}"
                                          onsubmit="return confirm('Write off barang ini? Barang dianggap hilang dari persediaan.')">
                                        @csrf
                                        <input type="hidden" name="keputusan" value="write_off">
                                        <button class="btn btn-sm btn-outline-danger">Write Off</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-success fw-semibold">&#10003; Selesai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada laporan barang rusak.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftarRusak->links() }}</div>
</div>

@include('stok._modal_lapor')
@endsection