<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIOPS') - Solo Optik</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --kuning: #fdcb2f;
            --kuning-aktif: #ffe11a;
            --abu-sidebar: #dcdcdc;
            --abu-panel: #d9d9d9;
            --abu-kartu: #efefef;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
            color: #111;
            background: #fff;
        }
        .mono { font-family: 'JetBrains Mono', ui-monospace, Consolas, monospace; }

        /* ---------- Bilah atas ---------- */
        .topbar {
            height: 68px;
            background: var(--kuning);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 14px;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .logo-box {
            width: 140px; height: 40px;
            background: #d9d9d9;
            border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }
        .topbar-pills { display: flex; gap: 12px; }
        .pill-info {
            background: #e8e8e8;
            border-radius: 999px;
            padding: 3px 14px;
            font-size: .72rem;
            font-weight: 500;
        }
        .user-card {
            background: #fff;
            border: 0;
            border-radius: 10px;
            padding: 8px 18px;
            min-width: 190px;
            text-align: left;
            line-height: 1.25;
        }
        .user-card.dropdown-toggle::after { display: none; }
        .user-card .nama { font-size: .82rem; font-weight: 600; }
        .user-card .peran { font-size: .68rem; color: #555; }

        /* ---------- Sidebar + isi ---------- */
        .shell { display: flex; min-height: calc(100vh - 68px); }
        .sidebar {
            width: 250px;
            flex-shrink: 0;
            background: var(--abu-sidebar);
            padding: 14px 10px;
        }
        .side-link {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px;
            margin-bottom: 6px;
            border-radius: 8px;
            color: #111;
            text-decoration: none;
            font-size: .82rem;
            font-weight: 500;
        }
        .side-link i { font-size: 1.05rem; }
        .side-link:hover { background: rgba(0, 0, 0, .07); color: #000; }
        .side-link.active { background: var(--kuning-aktif); font-weight: 600; }
        .content { flex: 1; min-width: 0; padding: 26px 30px; }
        .page-title { font-size: 1.35rem; font-weight: 700; margin: 0; }

        /* ---------- Komponen bersama ---------- */
        .panel-abu { background: var(--abu-panel); border-radius: 10px; padding: 12px; }
        .search-box { position: relative; flex: 1; }
        .search-box i {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%); color: #777;
        }
        .search-box input {
            width: 100%; height: 38px;
            border: 0; border-radius: 8px;
            padding: 0 12px 0 36px;
            font-size: .82rem;
            background: #fff;
            outline: none;
        }
        .chip {
            display: inline-flex; align-items: center; gap: 6px;
            background: #fff;
            border-radius: 999px;
            padding: 6px 16px;
            font-size: .78rem; font-weight: 500;
            color: #111; text-decoration: none;
        }
        .chip:hover { background: #f4f4f4; color: #000; }
        .chip.active { background: var(--kuning); }
        .btn-abu {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--abu-panel);
            border: 0; border-radius: 8px;
            padding: 8px 16px;
            font-size: .8rem; font-weight: 500;
            color: #111; text-decoration: none;
        }
        .btn-abu:hover { background: #c9c9c9; color: #000; }
        .icon-btn {
            border: 0; background: #fff;
            border-radius: 6px;
            width: 28px; height: 28px;
            display: inline-flex; align-items: center; justify-content: center;
            color: #111; font-size: .85rem;
            text-decoration: none;
        }
        .icon-btn:hover { background: #e2e2e2; color: #000; }

        /* ---------- Kartu barang ---------- */
        .barang-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 18px;
            margin-top: 20px;
        }
        .barang-card {
            background: var(--abu-kartu);
            border-radius: 10px;
            overflow: hidden;
            display: flex; flex-direction: column;
        }
        .barang-foto {
            height: 130px;
            background: #d6d6d6;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.6rem; color: #9a9a9a;
        }
        .barang-body { padding: 12px 14px; flex: 1; display: flex; flex-direction: column; }
        .barang-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .kode-chip {
            background: #fff3b0; border: 1px solid #e0c34a;
            border-radius: 4px; padding: 1px 8px;
            font-size: .65rem;
        }
        .barang-nama { font-weight: 700; font-size: .92rem; }
        .barang-info { font-size: .7rem; color: #444; margin: 4px 0 10px; line-height: 1.5; }
        .barang-foot {
            margin-top: auto;
            border-top: 1px solid #d0d0d0;
            padding-top: 8px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .harga-label { font-size: .62rem; color: #555; }
        .harga { font-weight: 700; font-size: .85rem; }
        .badge-stok {
            font-size: .65rem; font-weight: 600;
            border-radius: 999px; padding: 2px 8px;
            background: #d6f5d6; color: #1b7a1b; border: 1px solid #8fd48f;
        }
        .badge-stok.menipis { background: #fff1cc; color: #8a5a00; border-color: #f0c35a; }
        .badge-stok.habis { background: #fddede; color: #a31515; border-color: #f0a0a0; }

        /* ---------- Kartu pelanggan ---------- */
        .pelanggan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 14px;
            margin-top: 20px;
        }
        .pelanggan-card {
            background: var(--abu-kartu);
            border-radius: 10px;
            padding: 14px 16px;
            display: flex; justify-content: space-between; align-items: center; gap: 10px;
        }
        .pelanggan-nama { font-weight: 700; font-size: .92rem; }
        .pelanggan-wa { font-size: .75rem; color: #2a6fb5; }

        /* ---------- Warna tombol Bootstrap disesuaikan dengan tema ---------- */
        .btn-primary {
            --bs-btn-color: #000;
            --bs-btn-bg: var(--kuning);
            --bs-btn-border-color: var(--kuning);
            --bs-btn-hover-color: #000;
            --bs-btn-hover-bg: #f0bb15;
            --bs-btn-hover-border-color: #f0bb15;
            --bs-btn-active-color: #000;
            --bs-btn-active-bg: #e5b010;
            --bs-btn-active-border-color: #e5b010;
            font-weight: 600;
        }
        .form-control:focus, .form-select:focus {
            border-color: #d9a400;
            box-shadow: 0 0 0 .2rem rgba(253, 203, 47, .35);
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- ===== Bilah atas ===== --}}
<header class="topbar">
    <div class="logo-box">Logo</div>

    <div class="topbar-pills">
        <span class="pill-info">{{ $stokMenipis }} Item Stok Menipis</span>
        <span class="pill-info">{{ $pesananAktif }} Pesanan Aktif</span>
    </div>

    <div class="dropdown">
        <button class="user-card dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="nama">{{ auth()->user()->nama }}</div>
            <div class="peran">{{ auth()->user()->isOwner() ? 'Owner Bisnis' : 'Petugas' }}</div>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Keluar</button>
                </form>
            </li>
        </ul>
    </div>
</header>

<div class="shell">

    {{-- ===== Sidebar ===== --}}
    @php
        // [label, ikon, nama route (null = belum dibuat), pola route untuk menandai menu aktif]
        $menu = [
            ['Dashboard',           'bi-grid-1x2',        'dashboard',      'dashboard'],
            ['Data Barang',         'bi-box-seam',        'barang.index',   'barang.*'],
            ['Pengelolaan Stok',    'bi-boxes',           null,             null],
            ['Transaksi Penjualan', 'bi-cart3',           'penjualan.create', 'penjualan.*'],
            ['Pembelian Supplier',  'bi-truck',           'pembelian.index', 'pembelian.*'],
            ['Pesanan Kacamata',    'bi-eyeglasses',      null,             null],
            ['Data Pelanggan',      'bi-people',          'customer.index', 'customer.*'],
            ['Data Supplier',       'bi-shop',            'supplier.index',   'supplier.*'],
            ['Modul Retur',         'bi-arrow-left-right', null,            null],
            ['Laporan Laba Rugi',   'bi-graph-up',        null,             null],
            ['Notifikasi Whatsapp', 'bi-whatsapp',        null,             null],
        ];
    @endphp

    <aside class="sidebar">
        @foreach ($menu as [$label, $ikon, $rute, $pola])
            @php($aktif = $pola && request()->routeIs($pola))
            <a href="{{ $rute ? route($rute) : '#' }}" class="side-link {{ $aktif ? 'active' : '' }}">
                <i class="bi {{ $ikon }}"></i> {{ $label }}
            </a>
        @endforeach
    </aside>

    {{-- ===== Isi halaman ===== --}}
    <main class="content">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>