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
    .rt-tab.mati { color: #999; cursor: not-allowed; }
    .rt-tab.mati:hover { background: transparent; color: #999; }
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
    <span class="rt-tab mati" title="Belum dibuat"><i class="bi bi-person-x"></i> Retur dari Customer</span>
    <span class="rt-tab mati" title="Belum dibuat"><i class="bi bi-tools"></i> Penggantian Kesalahan Toko</span>
</nav>