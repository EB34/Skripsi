@extends('layouts.app')

@section('title', 'Input Stok via Excel')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Input Stok via Excel</h1>
    <a href="{{ route('stok.import.template') }}" class="btn-abu text-decoration-none">
        <i class="bi bi-download"></i> Unduh Template
    </a>
</div>

@if (session('daftarError'))
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Tidak ada data yang disimpan. Perbaiki baris berikut lalu unggah ulang:</div>
        <ul class="mb-0">
            @foreach (session('daftarError') as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel-abu">
    <form method="POST" action="{{ route('stok.import.proses') }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label class="form-label">File Excel (.xlsx)</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Tanggal &amp; jam cadangan (opsional)</label>
            <input type="datetime-local" name="tanggal_default" class="form-control" style="max-width:260px"
                   value="{{ old('tanggal_default') }}">
            <div class="form-text">Dipakai untuk input data dengan tanggal/jam yang ditentukan sendiri.(jadi kalau tanggal/jam kosong, nantinya akan diisi tanggal dari sini)</div>
        </div>

        <button class="btn btn-primary" type="submit">Proses &amp; Simpan</button>
    </form>
</div>

<!-- <div class="panel-abu mt-3">
    <div class="fw-semibold mb-2">Format kolom (baris pertama = judul kolom)</div>
    <table class="table table-sm mb-2">
        <thead><tr><th>kode_model</th><th>jumlah</th><th>harga_beli</th><th>tanggal_masuk</th></tr></thead>
        <tbody>
            <tr><td>FR-001</td><td>10</td><td>150000</td><td>2026-11-07 14:30 (Sabtu)</td></tr>
            <tr><td>FR-002</td><td>5</td><td>175000</td><td>2026-11-09 10:15 (Senin)</td></tr>
        </tbody>
    </table> -->
    <ul class="small text-muted mb-0">
        <li>Kode model harus sudah ada di Data Barang. Lensa custom tidak bisa diisi stok.</li>
        <li>Harga beli angka polos, tanpa titik atau koma ribuan.</li>
        <li>Tanggal boleh sel tanggal Excel, atau teks 2026-11-07 14:30 atau 07/11/2026 14:30 (hari/bulan/tahun).</li>
        <li>Stok ditambahkan ke stok yang sudah ada, bukan menggantikannya.</li>
    </ul>
</div>
@endsection