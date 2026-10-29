@auth
<style>
    .pil-status {
        display: inline-block;
        background: #d9d9d9;
        color: #1e1e1e;
        font-size: .75rem;
        line-height: 1;
        padding: .4rem 1.1rem;
        border-radius: 999px;
        text-decoration: none;
        white-space: nowrap;
    }
    .pil-status:hover { background: #cccccc; color: #1e1e1e; }
</style>

<div class="d-flex gap-3 justify-content-center align-items-center">
    <a href="{{ route('stok.index') }}" class="pil-status">
        {{ $jumlahStokMenipis ?? 0 }} Item Stok Menipis
    </a>
    <a href="{{ \Illuminate\Support\Facades\Route::has('pesanan.index') ? route('pesanan.index') : '#' }}" class="pil-status">
        {{ $jumlahPesananAktif ?? 0 }} Pesanan Aktif
    </a>
</div>
@endauth