@extends('layouts.app')

@section('title', 'Kerugian Operasional')

@push('styles')
<style>
    .krg-owner {
        display: inline-flex; align-items: center; gap: 6px;
        background: #e8e8e8; border-radius: 999px;
        padding: 4px 14px; font-size: .72rem; font-weight: 500; color: #1e1e1e;
    }
    .krg-filter {
        background: #d9d9d9; border-radius: 10px; padding: 12px;
        display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;
    }
    .krg-filter label { display: block; font-size: .7rem; font-weight: 600; color: #1e1e1e; margin-bottom: 3px; }
    .krg-filter .form-control, .krg-filter .form-select { font-size: .8rem; border: 0; border-radius: 8px; }

    .krg-kartu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 14px; margin: 18px 0;
    }
    .krg-kartu { background: #efefef; border-radius: 10px; padding: 12px 16px; }
    .krg-kartu .label { font-size: .7rem; color: #444; }
    .krg-kartu .nilai { font-size: 1.1rem; font-weight: 700; color: #1e1e1e; }
    .krg-kartu .unit { font-size: .68rem; color: #666; }
    .krg-kartu.total { background: #fdcb2f; }
    .krg-kartu.total .label, .krg-kartu.total .unit { color: #1e1e1e; }

    .krg-tabel { background: #fff; border: 1px solid #d9d9d9; border-radius: 10px; overflow: hidden; }
    .krg-tabel table { margin: 0; font-size: .8rem; color: #1e1e1e; }
    .krg-tabel thead th {
        background: #d9d9d9; font-size: .75rem; font-weight: 600;
        border-bottom: 0; white-space: nowrap;
    }
    .krg-jenis {
        display: inline-block; border-radius: 999px; padding: 2px 10px;
        font-size: .68rem; font-weight: 600; background: #fff1cc; color: #8a5a00; border: 1px solid #f0c35a;
    }
    .krg-jenis.write_off_rusak { background: #fddede; color: #a31515; border-color: #f0a0a0; }
    .krg-jenis.selisih_opname { background: #cfe5f7; color: #1c4e80; border-color: #8fbbe0; }
    .krg-sku { font-size: .68rem; color: #666; }
    .krg-ref { font-size: .68rem; color: #666; }
    .krg-angka { text-align: right; white-space: nowrap; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title">Kerugian Operasional</h1>
        <!-- <span class="krg-owner"><i class="bi bi-lock"></i> Khusus Owner</span> -->
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filter --}}
    <form method="GET" action="{{ route('kerugian.index') }}" class="krg-filter">
        <div>
            <label for="fJenis">Jenis kerugian</label>
            <select name="jenis" id="fJenis" class="form-select">
                <option value="">Semua jenis</option>
                @foreach ($jenisOpsi as $kode => $label)
                    <option value="{{ $kode }}" {{ $jenis === $kode ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="fDari">Dari tanggal</label>
            <input type="date" name="dari" id="fDari" class="form-control" value="{{ $dari }}">
        </div>
        <div>
            <label for="fSampai">Sampai tanggal</label>
            <input type="date" name="sampai" id="fSampai" class="form-control" value="{{ $sampai }}">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Terapkan</button>
            <a href="{{ route('kerugian.index') }}" class="btn-abu">Reset</a>
        </div>
    </form>

    {{-- Ringkasan periode --}}
    <div class="krg-kartu-grid">
        <div class="krg-kartu total">
            <div class="label">Total kerugian {{ ($dari || $sampai) ? 'periode terpilih' : 'seluruh waktu' }}</div>
            <div class="nilai">Rp {{ number_format($totalPeriode, 0, ',', '.') }}</div>
        </div>
        @foreach ($ringkas as $r)
            <div class="krg-kartu">
                <div class="label">{{ $r['label'] }}</div>
                <div class="nilai">Rp {{ number_format($r['nilai'], 0, ',', '.') }}</div>
                <div class="unit">{{ $r['unit'] }} unit</div>
            </div>
        @endforeach
    </div>

    {{-- Daftar --}}
    <div class="krg-tabel">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Tanggal &amp; Waktu</th>
                        <th>Jenis</th>
                        <th>Nama &amp; SKU Barang</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Nilai (Rp)</th>
                        <th>Keterangan</th>
                        <th>Dicatat oleh</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($daftar as $k)
                    <tr>
                        <td class="mono">{{ $k->tanggal->locale('id')->translatedFormat('d M Y, H.i') }}</td>
                        <td><span class="krg-jenis {{ $k->jenis }}">{{ $k->jenis_label }}</span></td>
                        <td>
                            @if ($k->barang)
                                <div class="fw-bold">{{ ucfirst($k->barang->jenis ?? '') }} {{ $k->barang->merk }}</div>
                                <div class="mono krg-sku">{{ $k->barang->kode_model }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end">{{ $k->jumlah }}</td>
                        <td class="krg-angka">{{ number_format($k->nilai, 0, ',', '.') }}</td>
                        <td>
                            {{ $k->keterangan ?: '-' }}
                            @if ($k->referensi_tipe === 'barang_rusak')
                                <div class="krg-ref">Laporan barang rusak #{{ $k->referensi_id }}</div>
                            @elseif ($k->referensi_tipe === 'opname')
                                <div class="krg-ref">Koreksi stok (opname)</div>
                            @endif
                        </td>
                        <td>{{ $k->user?->nama ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted py-4 text-center">Belum ada kerugian yang tercatat untuk filter ini.</td></tr>
                @endforelse
                </tbody>
                @if ($daftar->total() > 0)
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Total sesuai filter ({{ $daftar->total() }} catatan)</td>
                            <td class="krg-angka">{{ number_format($totalTampil, 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>
@endsection