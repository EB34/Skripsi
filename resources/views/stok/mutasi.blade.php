@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('stok._header', ['aktif' => 'mutasi'])

    <form method="GET" action="{{ route('stok.mutasi') }}" class="row g-2 mb-3 align-items-center">
        <div class="col-md-6">
            <input type="text" name="cari" value="{{ request('cari') }}" class="form-control"
                   placeholder="Cari transaksi mutasi, SKU, no ref">
        </div>
        <div class="col-md-4 ms-auto">
            <div class="input-group">
                <span class="input-group-text">Tipe Mutasi:</span>
                <select name="tipe" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Tipe Mutasi</option>
                    @foreach($jenisMutasi as $kunci => $label)
                        <option value="{{ $kunci }}" @selected(request('tipe') === $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal &amp; Waktu</th>
                        <th>Nama &amp; SKU Barang</th>
                        <th>Jenis Mutasi</th>
                        <th class="text-center">Perubahan</th>
                        <th class="text-center">Stok Akhir</th>
                        <th>No Referensi</th>
                        <th>Keterangan</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($mutasi as $m)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($m->tanggal)->format('d/m/Y H.i') }}</td>
                        <td>
                            <div class="fw-semibold">{{ ucfirst($m->barang?->jenis ?? '') }} {{ $m->barang?->merk ?? '-' }}</div>
                            <div class="small text-muted font-monospace">{{ $m->barang?->kode_model }}</div>
                        </td>
                        <td>{{ $m->jenis_label }}</td>
                        <td class="text-center">
                            @if($m->jumlah > 0)
                                <span class="text-success fw-semibold">&uarr; +{{ $m->jumlah }}</span>
                            @elseif($m->jumlah < 0)
                                <span class="text-danger fw-semibold">&darr; {{ $m->jumlah }}</span>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $m->saldo_setelah }}</td>
                        <td class="font-monospace small">{{ $m->no_referensi }}</td>
                        <td class="small">{{ $m->keterangan_tampil }}</td>
                        <td class="small">{{ $m->user?->name ?? $m->user?->nama ?? $m->user?->username ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada mutasi stok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $mutasi->links() }}</div>
</div>

@include('stok._modal_lapor')
@endsection