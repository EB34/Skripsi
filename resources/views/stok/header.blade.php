<div class="d-flex justify-content-between align-items-start mb-3">
    <h4 class="mb-0">Pengelolaan Stok &amp; Log Mutasi</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('lokasi.index') }}" class="btn btn-outline-secondary btn-sm">Kelola Lokasi</a>
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalLaporRusak">
            Lapor Barang Rusak/Cacat
        </button>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $aktif === 'monitoring' ? 'active' : '' }}" href="{{ route('stok.index') }}">
            Monitoring Stok Real Time
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $aktif === 'mutasi' ? 'active' : '' }}" href="{{ route('stok.mutasi') }}">
            Riwayat Mutasi Stok
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $aktif === 'rusak' ? 'active' : '' }}" href="{{ route('stok.rusak') }}">
            Penanganan Barang Rusak
        </a>
    </li>
</ul>

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