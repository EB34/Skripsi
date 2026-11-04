@extends('layouts.app')

@section('title', 'Modul Retur')

@push('styles')
<style>
    .rt-banner {
        background: #fff3b0; border: 1px solid #e0c34a; border-radius: 10px;
        padding: 10px 16px; font-size: .8rem; margin-bottom: 14px;
    }
    .rt-filter { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .rt-tabel { background: #fff; border: 1px solid #d9d9d9; border-radius: 10px; overflow: hidden; }
    .rt-tabel table { margin: 0; font-size: .8rem; color: #1e1e1e; }
    .rt-tabel thead th { background: #d9d9d9; font-size: .75rem; font-weight: 600; border-bottom: 0; white-space: nowrap; }
    .rt-status {
        display: inline-block; font-size: .7rem; font-weight: 600;
        padding: 3px 10px; border-radius: 999px; border: 1px solid;
    }
    .rt-status.diajukan { background: #fff1cc; color: #8a5a00; border-color: #f0c35a; }
    .rt-status.dikirim  { background: #d6ecff; color: #0b4f8a; border-color: #8fc4f0; }
    .rt-status.selesai  { background: #d6f5d6; color: #1b7a1b; border-color: #8fd48f; }
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
</style>
@endpush

@section('content')
@php
    /** @var \App\Models\User $user */
    $user    = auth()->user();
    $isOwner = $user->isOwner();
@endphp

<div class="container-fluid">
    @include('retur.header', ['aktif' => 'supplier'])

    @if ($isOwner && ($hitung['diajukan'] ?? 0) > 0)
        <div class="rt-banner">
            <i class="bi bi-exclamation-triangle"></i>
            Ada {{ $hitung['diajukan'] }} retur menunggu validasi Owner.
        </div>
    @endif

    <div class="rt-filter">
        <a href="{{ route('retur.supplier') }}" class="chip {{ ! $status ? 'active' : '' }}">Semua</a>
        @foreach (\App\Models\ReturSupplier::STATUS as $kode => $label)
            <a href="{{ route('retur.supplier', ['status' => $kode]) }}"
               class="chip {{ $status === $kode ? 'active' : '' }}">
                {{ $label }} ({{ $hitung[$kode] ?? 0 }})
            </a>
        @endforeach
    </div>

    <div class="rt-tabel">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No. Retur &amp; Tanggal</th>
                        <th>PO Asal &amp; Supplier</th>
                        <th>Barang Diretur</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($daftar as $r)
                    @php
                        $supplier   = $r->pembelian?->supplier;
                        $namaKontak = $supplier ? $supplier->kontak->pluck('nama')->implode(', ') : '';
                    @endphp
                    <tr>
                        <td>
                            <div class="mono fw-bold">{{ $r->nomor_retur }}</div>
                            <div class="rt-sub">{{ $r->tanggal_format }}</div>
                            @if ($r->nomor_surat_jalan)
                                <div class="mono rt-sub">{{ $r->nomor_surat_jalan }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="mono">{{ $r->pembelian?->nomor_po ?? '-' }}</div>
                            <div class="fw-bold">{{ $supplier?->nama_perusahaan ?? '-' }}</div>
                            @if ($namaKontak !== '')
                                <div class="rt-sub">{{ $namaKontak }}</div>
                            @endif
                        </td>
                        <td>
                            @foreach ($r->detail as $d)
                                <div>
                                    {{ $d->jumlah }} pcs {{ $d->barang?->merk ?? '-' }}
                                    <span class="mono rt-sub">({{ $d->barang?->kode_model }})</span>
                                    <span class="rt-sub">- {{ \App\Models\ReturSupplier::ALASAN[$d->alasan] ?? $d->alasan }}</span>
                                </div>
                            @endforeach
                        </td>
                        <td><span class="rt-status {{ $r->status }}">{{ $r->status_label }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1 justify-content-center">
                                {{-- Validasi dan tandai selesai: hanya Owner --}}
                                @if ($isOwner && $r->status === 'diajukan')
                                    <form method="POST" action="{{ route('retur.supplier.kirim', $r->id) }}"
                                          onsubmit="return confirm('Validasi retur ini dan terbitkan surat jalan?')">
                                        @csrf
                                        <button class="rt-btn teal"><i class="bi bi-check2-square"></i> Validasi &amp; Terbitkan Surat Jalan</button>
                                    </form>
                                @endif

                                {{-- Cetak: Owner dan Petugas --}}
                                <a href="{{ route('retur.supplier.nota', $r->id) }}" target="_blank" class="rt-btn abu">
                                    <i class="bi bi-printer"></i> Nota
                                </a>

                                @if ($r->nomor_surat_jalan)
                                    <a href="{{ route('retur.supplier.suratjalan', $r->id) }}" target="_blank" class="rt-btn abu">
                                        <i class="bi bi-printer"></i> Surat Jalan
                                    </a>
                                @endif

                                @if ($isOwner && $r->status === 'dikirim')
                                    <form method="POST" action="{{ route('retur.supplier.selesai', $r->id) }}"
                                          onsubmit="return confirm('Tandai retur ini selesai (supplier sudah menerima)?')">
                                        @csrf
                                        <button class="rt-btn hijau"><i class="bi bi-check-circle"></i> Tandai Selesai</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted py-4 text-center">Belum ada retur ke supplier.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>
@endsection