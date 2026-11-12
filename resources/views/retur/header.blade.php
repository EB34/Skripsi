@php
    /** @var \App\Models\User $userHeader */
    $userHeader = auth()->user();
    $isOwnerHeader = $userHeader->isOwner();
@endphp

@once
@push('styles')
<style>
    .rt-tabs { display: flex; gap: 8px; border-bottom: 1px solid #ddd; margin-bottom: 18px; }
    .rt-tab {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 14px; font-size: .85rem; font-weight: 600;
        color: #1e1e1e; text-decoration: none;
        border-bottom: 2px solid transparent; margin-bottom: -1px;
    }
    .rt-tab:hover { color: #000; background: rgba(0, 0, 0, .04); }
    .rt-tab.aktif { color: #2a86a8; border-bottom-color: #2a86a8; }

    .rt-banner {
        background: #fff3b0; border: 1px solid #e0c34a; border-radius: 10px;
        padding: 10px 16px; font-size: .8rem; margin-bottom: 14px;
    }
    .rt-filter { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    /* .rt-tabel { background: #fff; border: 1px solid #d9d9d9; border-radius: 10px; overflow: hidden; }
    .rt-tabel table { margin: 0; font-size: .8rem; color: #1e1e1e; }
    .rt-tabel thead th { background: #d9d9d9; font-size: .75rem; font-weight: 600; border-bottom: 0; white-space: nowrap; } */
    .rt-status {
        display: inline-block; font-size: .7rem; font-weight: 600;
        padding: 3px 10px; border-radius: 999px; border: 1px solid;
    }
    .rt-status.dicatat { background: #fff1cc; color: #8a5a00; border-color: #f0c35a; }
    .rt-status.selesai { background: #d6f5d6; color: #1b7a1b; border-color: #8fd48f; }
    .rt-sub { font-size: .68rem; color: #666; }
    .rt-btn {
        display: inline-flex; align-items: center; gap: 6px;
        border: 0; border-radius: 6px; padding: 6px 12px;
        font-size: .74rem; font-weight: 600; text-decoration: none; cursor: pointer;
    }
    .rt-btn.teal { background: #2a86a8; color: #fff; }
    .rt-btn.teal:hover { background: #226f8c; color: #fff; }
    .rt-btn.abu { background: #d9d9d9; color: #1e1e1e; }
    .rt-btn.abu:hover { background: #c9c9c9; color: #000; }
    .rt-btn.hijau { background: #1f9d4a; color: #fff; }
    .rt-btn.hijau:hover { background: #188040; color: #fff; }
    .rt-kartu {
        display: inline-block; background: #efefef; border-radius: 10px;
        padding: 8px 16px; font-size: .78rem; margin-bottom: 14px;
    }
    .rt-kartu strong { font-size: 1rem; }
</style>
@endpush
@endonce

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Modul Retur &amp; Penggantian</h1>
</div>

<nav class="rt-tabs">
    <a href="{{ route('retur.supplier') }}" class="rt-tab {{ $aktif === 'supplier' ? 'aktif' : '' }}">
        <i class="bi bi-box-arrow-up"></i> Retur ke Supplier
    </a>
    <a href="{{ route('retur.customer') }}" class="rt-tab {{ $aktif === 'customer' ? 'aktif' : '' }}">
        <i class="bi bi-person-x"></i> Retur dari Customer
    </a>
    <a href="{{ route('retur.kesalahan') }}" class="rt-tab {{ $aktif === 'kesalahan' ? 'aktif' : '' }}">
        <i class="bi bi-tools"></i> Penggantian Kesalahan Toko
    </a>
</nav>