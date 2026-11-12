@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .dash-card {
        background: var(--abu-kartu);
        border-radius: 10px;
        padding: 16px 18px;
    }
    .dash-judul { font-size: .95rem; font-weight: 700; margin: 0; }
    .dash-sub { font-size: .75rem; color: #555; }

    .stat-label { font-size: .68rem; font-weight: 600; letter-spacing: .04em; color: #555; text-transform: uppercase; }
    .stat-angka { font-size: 1.9rem; font-weight: 700; line-height: 1.2; }
    .stat-angka.rp { font-size: 1.35rem; }
    .stat-ket { font-size: .72rem; color: #555; }
    .stat-link {
        display: block; margin-top: 10px; padding-top: 8px;
        border-top: 1px solid #d0d0d0;
        font-size: .75rem; font-weight: 600; color: #111; text-decoration: none;
    }
    .stat-link:hover { text-decoration: underline; color: #000; }
    .stat-kuning { background: var(--kuning); }
    .stat-kuning .stat-ket, .stat-kuning .stat-label { color: #3d3000; }
    .stat-kuning .stat-link { border-color: rgba(0, 0, 0, .15); }
    .btn-abu.btn-kuning { background: var(--kuning); }
    .btn-abu.btn-kuning:hover { background: var(--kuning-aktif); color: #000; }

    .pesanan-item { background: #fff; border-radius: 8px; padding: 12px 14px; margin-bottom: 10px; }
    .pesanan-item:last-child { margin-bottom: 0; }
    .nomor-chip {
        background: #fff3b0; border: 1px solid #e0c34a;
        border-radius: 4px; padding: 1px 8px; font-size: .68rem;
    }
    .st-pesanan { font-size: .66rem; font-weight: 600; border-radius: 999px; padding: 2px 10px; border: 1px solid transparent; }
    .st-menunggu     { background: #fff1cc; color: #8a5a00; border-color: #f0c35a; }
    .st-dikerjakan   { background: #dbeafe; color: #1e4fa3; border-color: #9fc0f0; }
    .st-siap_diambil { background: #d6f5d6; color: #1b7a1b; border-color: #8fd48f; }
    .resep-baris { display: flex; gap: 18px; flex-wrap: wrap; font-size: .72rem; margin-top: 8px; padding-top: 8px; border-top: 1px dashed #d0d0d0; }
    .resep-baris b { color: #555; }

    .kritis-item { background: #fff; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .ringkas-baris { display: flex; justify-content: space-between; align-items: center; font-size: .78rem; padding: 6px 0; border-bottom: 1px solid #d8d8d8; }
    .ringkas-baris:last-child { border-bottom: 0; }
    .ringkas-judul { font-size: .68rem; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .04em; margin: 12px 0 2px; }
</style>
@endpush

@section('content')
@php
    $rp  = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $uk  = fn ($v) => $v === null ? '-' : (((float) $v > 0 ? '+' : '') . number_format((float) $v, 2, '.', ''));
    $labelStatus = \App\Models\PesananKacamata::STATUS;
@endphp

{{-- ===== Sapaan + tombol cepat ===== --}}
<div class="dash-card mb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h1 class="page-title">Dashboard {{ $isOwner ? 'Owner' : 'Staff' }}</h1>
        <div class="dash-sub mt-1">
            Selamat bekerja, <strong>{{ auth()->user()->nama }}</strong>.
            {{ $isOwner ? 'Ringkasan operasional dan keuangan toko.' : 'Ringkasan pekerjaan Anda hari ini.' }}
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($isOwner)
        <a href="{{ route('penjualan.create') }}" class="btn-abu btn-kuning"><i class="bi bi-cart3"></i> Kasir Baru</a>
        @endif
        <a href="{{ route('pesanan.index') }}" class="btn-abu"><i class="bi bi-eyeglasses"></i> Pesanan Kacamata</a>
        @if ($isOwner)
            <a href="{{ route('pembelian.index') }}" class="btn-abu"><i class="bi bi-truck"></i> Buat PO ke Supplier</a>
        @else
            <a href="{{ route('stok.rusak') }}" class="btn-abu"><i class="bi bi-exclamation-triangle"></i> Lapor Barang Rusak</a>
        @endif
    </div>
</div>

{{-- ===== Pilihan periode ===== --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="{{ route('dashboard', ['periode' => 'minggu']) }}" class="chip {{ $periode === 'minggu' ? 'active' : '' }}">Minggu ini</a>
    <a href="{{ route('dashboard', ['periode' => 'bulan']) }}" class="chip {{ $periode === 'bulan' ? 'active' : '' }}">Bulan ini</a>
    <span class="dash-sub ms-1">Periode: {{ $labelPeriode }}</span>
</div>

{{-- ===== Empat kartu statistik ===== --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="dash-card h-100">
            <div class="stat-label">Stok Menipis</div>
            <div class="stat-angka">{{ $stokMenipis }}</div>
            <div class="stat-ket">item di bawah batas minimum</div>
            <a href="{{ route('stok.index') }}" class="stat-link">Lihat Monitoring Stok &rarr;</a>
        </div>
    </div>

    @if ($isOwner)
        <div class="col-6 col-xl-3">
            <div class="dash-card h-100">
                <div class="stat-label">Pesanan Berjalan</div>
                <div class="stat-angka">{{ $pesananMenunggu + $pesananDikerjakan }}</div>
                <div class="stat-ket">{{ $pesananMenunggu }} menunggu, {{ $pesananDikerjakan }} dikerjakan</div>
                <a href="{{ route('pesanan.index') }}" class="stat-link">Buka Pesanan Kacamata &rarr;</a>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dash-card h-100">
                <div class="stat-label">Siap Diambil</div>
                <div class="stat-angka">{{ $pesananSiap }}</div>
                <div class="stat-ket">kacamata menunggu diambil pelanggan</div>
                <a href="{{ route('pesanan.index') }}" class="stat-link">Lihat pesanan &rarr;</a>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dash-card stat-kuning h-100">
                <div class="stat-label">Uang Masuk {{ $periode === 'bulan' ? 'Bulan Ini' : 'Minggu Ini' }}</div>
                <div class="stat-angka rp">{{ $rp($uangMasuk) }}</div>
                <div class="stat-ket">{{ $jumlahNota }} nota, termasuk DP dan pelunasan</div>
                <a href="{{ route('penjualan.index') }}" class="stat-link">Lihat riwayat penjualan &rarr;</a>
            </div>
        </div>
    @else
        <div class="col-6 col-xl-3">
            <div class="dash-card h-100">
                <div class="stat-label">Pesanan dengan status menunggu</div>
                <div class="stat-angka">{{ $pesananBelumAda }}</div>
                <div class="stat-ket">pesanan menunggu untuk diambil</div>
                <a href="{{ route('pesanan.index') }}" class="stat-link">Ambil pesanan &rarr;</a>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dash-card h-100">
                <div class="stat-label">Pesanan Saya</div>
                <div class="stat-angka">{{ $pesananSaya }}</div>
                <div class="stat-ket">sedang saya kerjakan</div>
                <a href="{{ route('pesanan.index') }}" class="stat-link">Buka pesanan saya &rarr;</a>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dash-card stat-kuning h-100">
                <div class="stat-label">Siap Diambil</div>
                <div class="stat-angka">{{ $pesananSiap }}</div>
                <div class="stat-ket">pelanggan perlu dihubungi</div>
                <a href="{{ route('pesanan.index') }}" class="stat-link">Lihat pesanan &rarr;</a>
            </div>
        </div>
    @endif
</div>

<div class="row g-3">
    {{-- ===== Antrean pesanan ===== --}}
    <div class="col-xl-8">
        <div class="dash-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2 class="dash-judul">Antrean Pesanan Kacamata</h2>
                    <div class="dash-sub">Pesanan yang belum selesai, yang siap diambil paling atas</div>
                </div>
                <a href="{{ route('pesanan.index') }}" class="chip">Lihat semua</a>
            </div>

            @forelse ($antrean as $p)
                @php
                    $frame = $p->frame ? trim($p->frame->merk . ' ' . $p->frame->kode_model) : 'Frame pelanggan';
                    $lensa = $p->lensa
                        ? trim($p->lensa->merk . ' ' . $p->lensa->kode_model)
                        : ($p->jumlah_lensa > 0 ? 'Lensa dari nota' : 'Lensa lama pelanggan');
                @endphp
                <div class="pesanan-item">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div>
                            <span class="nomor-chip mono">{{ $p->nomor_pesanan }}</span>
                            <span class="st-pesanan st-{{ $p->status }} ms-1">{{ $labelStatus[$p->status] ?? $p->status }}</span>
                        </div>
                        <div class="text-end">
                            @if ($p->petugas)
                                <span class="dash-sub"><i class="bi bi-person"></i> {{ $p->petugas->nama }}</span>
                            @elseif ($p->status === 'menunggu')
                                <form action="{{ route('pesanan.ambil', $p->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-primary btn-sm py-0 px-2" style="font-size:.72rem">Ambil Pesanan</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2" style="font-weight:700;font-size:.88rem">{{ $p->customer?->nama ?? '-' }}</div>
                    <div class="dash-sub">Frame: {{ $frame }} | Lensa: {{ $lensa }}</div>
                    <div class="resep-baris mono">
                        <span><b>OD</b> SPH {{ $uk($p->od_sphere) }} | CYL {{ $uk($p->od_cylinder) }} | AX {{ $p->od_axis ?? '-' }}</span>
                        <span><b>OS</b> SPH {{ $uk($p->os_sphere) }} | CYL {{ $uk($p->os_cylinder) }} | AX {{ $p->os_axis ?? '-' }}</span>
                    </div>
                </div>
            @empty
                <div class="text-center dash-sub py-4">Tidak ada pesanan yang sedang berjalan.</div>
            @endforelse
        </div>
    </div>

    {{-- ===== Panel kanan ===== --}}
    <div class="col-xl-4">
        <div class="dash-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="dash-judul"><i class="bi bi-exclamation-triangle"></i> Stok Kritis</h2>
                <a href="{{ route('stok.index') }}" class="dash-sub text-decoration-none">Detail</a>
            </div>
            @forelse ($stokKritis as $b)
                <div class="kritis-item">
                    <div>
                        <div style="font-weight:600;font-size:.82rem">{{ $b->merk }}</div>
                        <div class="dash-sub mono">{{ $b->kode_model }}</div>
                    </div>
                    <span class="badge-stok {{ $b->stok_tersedia <= 0 ? 'habis' : 'menipis' }}">
                        Sisa {{ $b->stok_tersedia }} / Min {{ $b->stok_minimum }}
                    </span>
                </div>
            @empty
                <div class="dash-sub text-center py-2">Semua stok masih di atas batas minimum.</div>
            @endforelse
        </div>

        <div class="dash-card">
            <h2 class="dash-judul">Ringkasan {{ $periode === 'bulan' ? 'Bulan Ini' : 'Minggu Ini' }}</h2>

            @if ($isOwner)
                <div class="ringkas-judul">Barang Rusak</div>
                <div class="ringkas-baris"><span>Dilaporkan</span><strong>{{ $rusakDilaporkan }}</strong></div>
                <div class="ringkas-baris">
                    <span>Menunggu keputusan Anda</span>
                    <a href="{{ route('stok.rusak') }}" class="text-decoration-none"><strong>{{ $rusakMenunggu }}</strong> &rarr;</a>
                </div>

                <div class="ringkas-judul">Retur ke Supplier</div>
                @foreach (\App\Models\ReturSupplier::STATUS as $kode => $label)
                    <div class="ringkas-baris"><span>{{ $label }}</span><strong>{{ $returPerStatus[$kode] ?? 0 }}</strong></div>
                @endforeach
                <a href="{{ route('retur.supplier') }}" class="stat-link">Buka Retur ke Supplier &rarr;</a>

                <div class="ringkas-judul">Saat Ini</div>
                <div class="ringkas-baris"><span>Piutang nota berjalan</span><strong>{{ $rp($piutang) }}</strong></div>
            @else
                <div class="ringkas-baris"><span>Nota dicatat</span><strong>{{ $jumlahNota }}</strong></div>
                <div class="ringkas-baris">
                    <span>Barang rusak yang dilaporkan</span>
                    <a href="{{ route('stok.rusak') }}" class="text-decoration-none"><strong>{{ $rusakSaya }}</strong> &rarr;</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection