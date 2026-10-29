@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('stok.index') }}" class="text-decoration-none small">&larr; Kembali ke Pengelolaan Stok</a>
        <h4 class="mb-0">Kelola Lokasi Stok</h4>
        <div class="text-muted small">
            Tempat penyimpanan barang, misalnya gudang, etalase, atau laci. Barang dari PO selalu masuk ke gudang.
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if($daftarLokasi->where('is_gudang', true)->isEmpty())
        <div class="alert alert-warning">
            Belum ada lokasi bertanda <strong>Gudang</strong>. Tambahkan satu dulu, karena barang dari PO masuk ke gudang.
        </div>
    @endif

    {{-- Tambah lokasi --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('lokasi.store') }}" class="row g-2 align-items-center">
                @csrf
                <div class="col-md-5">
                    <input type="text" name="nama" maxlength="100" value="{{ old('nama') }}"
                           class="form-control" placeholder="Nama lokasi baru, contoh: Laci 2" required>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_gudang" value="1" id="tambahGudang">
                        <label class="form-check-label" for="tambahGudang">Tempat penyimpanan (gudang)</label>
                    </div>
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-warning">+ Tambah Lokasi</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar lokasi --}}
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama Lokasi</th>
                        <th>Gudang</th>
                        <th class="text-end">Total Stok</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($daftarLokasi as $l)
                    <tr>
                        <td>
                            <input type="text" name="nama" form="ubah{{ $l->id }}" maxlength="100"
                                   value="{{ $l->nama }}" class="form-control form-control-sm" required>
                        </td>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_gudang" value="1"
                                       id="gudang{{ $l->id }}" form="ubah{{ $l->id }}" @checked($l->is_gudang)>
                                <label class="form-check-label" for="gudang{{ $l->id }}">Gudang</label>
                            </div>
                        </td>
                        <td class="text-end">{{ (int) ($l->total_stok ?? 0) }}</td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <form id="ubah{{ $l->id }}" method="POST" action="{{ route('lokasi.update', $l->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <button class="btn btn-sm btn-outline-secondary">Simpan</button>
                                </form>
                                <form method="POST" action="{{ route('lokasi.destroy', $l->id) }}"
                                      onsubmit="return confirm('Hapus lokasi {{ $l->nama }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada lokasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="small text-muted mt-2">
        Saat barang keluar (terjual, rusak, koreksi), stok diambil dari lokasi non-gudang lebih dulu, baru gudang.
    </div>
</div>
@endsection