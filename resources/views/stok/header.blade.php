@push('styles')
<style>
    .btn-lapor {
        display: inline-flex; align-items: center; gap: 6px;
        background: #d90000; color: #fff;
        border: 0; border-radius: 6px;
        padding: 8px 14px;
        font-size: .8rem; font-weight: 500;
        text-decoration: none;
    }
    .btn-lapor:hover { background: #b80000; color: #fff; }

    .stok-tabs {
        display: flex; flex-wrap: wrap; gap: 24px;
        border-bottom: 1px solid #d9d9d9;
        margin: 20px 0 18px;
    }
    .stok-tab {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 2px; margin-bottom: -1px;
        font-size: .85rem; font-weight: 600;
        color: #1e1e1e; text-decoration: none;
        border-bottom: 2px solid transparent;
    }
    .stok-tab:hover { color: #000; }
    .stok-tab.aktif { color: #1f8fb8; border-bottom-color: #1f8fb8; }
    .stok-tab.aktif-rusak { color: #e8502a; border-bottom-color: #e8502a; }

    /* Tabel gaya mockup: sudut membulat, header abu, garis pemisah kolom */
    .tabel-mockup {
        background: #fff;
        border: 1px solid #d9d9d9;
        border-radius: 12px;
        overflow: hidden;
    }
    .tabel-mockup .table { margin: 0; }
    .tabel-mockup .table th {
        background: #ececec;
        text-align: center;
        font-size: .78rem; font-weight: 700;
        padding: 12px 10px;
        border-bottom: 1px solid #d9d9d9;
    }
    .tabel-mockup .table td {
        text-align: center;
        font-size: .78rem;
        padding: 12px 10px;
        vertical-align: middle;
        color: #1e1e1e;
    }
    .tabel-mockup .table th + th,
    .tabel-mockup .table td + td { border-left: 1px solid #e3e3e3; }
    .tabel-mockup .table tbody tr:last-child td { border-bottom: 0; }
    .sku-kecil { font-size: .7rem; color: #555; }

    .btn-import {
        display: inline-flex; align-items: center; gap: 6px;
        background: #1f8fb8; color: #fff;
        border: 0; border-radius: 6px;
        padding: 8px 14px;
        font-size: .8rem; font-weight: 500;
        text-decoration: none;
    }
    .btn-import:hover { background: #17769a; color: #fff; }
</style>
@endpush

<div class="d-flex justify-content-between align-items-start">
    <h1 class="page-title">Pengelolaan Stok &amp; Log Mutasi</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('lokasi.index') }}" class="btn-abu">Kelola Lokasi</a>

        @if (auth()->user()->isOwner())
            <a href="{{ route('stok.import') }}" class="btn-import">
                <i class="bi bi-file-earmark-arrow-up"></i> Import Excel
            </a>
        @endif

        <button type="button" class="btn-lapor" data-bs-toggle="modal" data-bs-target="#modalLaporRusak">
            <i class="bi bi-exclamation-circle"></i> Lapor Barang Rusak/Cacat
        </button>
    </div>
</div>

<nav class="stok-tabs">
    <a class="stok-tab {{ $aktif === 'monitoring' ? 'aktif' : '' }}" href="{{ route('stok.index') }}">
        <i class="bi bi-clipboard-data"></i> Monitoring Stok Real Time
    </a>
    <a class="stok-tab {{ $aktif === 'mutasi' ? 'aktif' : '' }}" href="{{ route('stok.mutasi') }}">
        <i class="bi bi-clock-history"></i> Riwayat Mutasi Stok
    </a>
    <a class="stok-tab {{ $aktif === 'rusak' ? 'aktif-rusak' : '' }}" href="{{ route('stok.rusak') }}">
        <i class="bi bi-exclamation-circle"></i> Penanganan Barang Rusak
    </a>
</nav>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif