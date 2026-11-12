@extends('layouts.app')

@section('title', 'Kategori Barang')

@push('styles')
<style>
    button.btn-abu { cursor: pointer; }
    /* .tabel-kategori { background: #efefef; border-radius: 10px; padding: 6px 14px; margin-top: 16px; }
    .tabel-kategori table { margin-bottom: 0; --bs-table-bg: transparent; } */
</style>
@endpush

@section('content')
@php
    $namaJenis = \App\Models\KategoriBarang::JENIS;
    $labelAtribut = \App\Models\KategoriBarang::LABEL;

    // Kalau popup ditolak validasi: popup dibuka lagi dengan isian tadi
    $buka = in_array(old('_form'), ['buat', 'ubah'], true) ? old('_form') : '';
    $lama = ['id' => old('_id'), 'nama' => old('nama')];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Kategori Barang</h1>
    <button type="button" class="btn-abu" id="btn-tambah">
        <i class="bi bi-plus-lg"></i> Tambah {{ $labelAtribut[$jenis] }}
    </button>
</div>

{{-- Tab per jenis + pencarian --}}
<form method="GET" action="{{ route('kategori.index') }}" class="panel-abu d-flex align-items-center gap-3">
    <input type="hidden" name="jenis" value="{{ $jenis }}">

    @foreach ([['frame', 'Frame', 'bi-eyeglasses'], ['lensa', 'Lensa', 'bi-record-circle'], ['softlens', 'Softlens', 'bi-eye']] as [$nilai, $label, $ikon])
        <a href="{{ route('kategori.index', ['jenis' => $nilai]) }}" class="chip {{ $jenis === $nilai ? 'active' : '' }}">
            <i class="bi {{ $ikon }}"></i> {{ $label }}
        </a>
    @endforeach

    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari {{ strtolower($labelAtribut[$jenis]) }}...">
    </div>

    @if (request('q'))
        <a href="{{ route('kategori.index', ['jenis' => $jenis]) }}" class="text-muted small text-decoration-none">Reset</a>
    @endif
</form>

<div class="tabel-abu mt-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>{{ $labelAtribut[$jenis] }}</th>
                    <th>Jumlah Barang</th>
                    <th style="width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($daftar as $k)
                <tr>
                    <td class="fw-bold">{{ $k->nama }}</td>
                    <td>{{ $k->barang_count }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <button type="button" class="icon-btn btn-ubah" title="Ubah"
                                    data-id="{{ $k->id }}" data-nama="{{ $k->nama }}">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form action="{{ route('kategori.destroy', $k) }}" method="POST"
                                  onsubmit="return confirm('Hapus {{ $k->nama }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="icon-btn" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-muted py-4">
                        @if (request('q'))
                            Tidak ada yang cocok dengan "{{ request('q') }}".
                        @else
                            Belum ada {{ strtolower($labelAtribut[$jenis]) }}. Klik tombol tambah di kanan atas.
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $daftar->links() }}</div>

{{-- ===== Popup: Tambah / Ubah ===== --}}
<div class="modal fade" id="modalKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="form-kategori" action="{{ route('kategori.store') }}"
                  data-url-simpan="{{ route('kategori.store') }}"
                  data-url-ubah="{{ route('kategori.update', '__ID__') }}">
                @csrf
                <input type="hidden" name="_method" value="PUT" id="field-method" disabled>
                <input type="hidden" name="_form" id="field-form" value="buat">
                <input type="hidden" name="_id" id="field-id" value="">
                <input type="hidden" name="jenis" value="{{ $jenis }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="judul-modal">Tambah {{ $labelAtribut[$jenis] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    @if ($buka && $errors->any())
                        <div class="alert alert-danger" id="alert-form">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <label class="form-label">Nama {{ $labelAtribut[$jenis] }}</label>
                    <input type="text" name="nama" class="form-control" maxlength="100" required
                           placeholder="{{ ['frame' => 'Contoh: Besi, Plastik, Titanium', 'lensa' => 'Contoh: Photochromic, Blue Ray', 'softlens' => 'Contoh: Harian, Bulanan'][$jenis] }}">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-abu" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="tombol-simpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="data-kategori" hidden
     data-buka="{{ $buka }}"
     data-lama="{{ json_encode($lama) }}"
     data-judul-tambah="Tambah {{ $labelAtribut[$jenis] }}"
     data-judul-ubah="Ubah {{ $labelAtribut[$jenis] }}"></div>
@endsection

@push('scripts')
<script>
    const dataEl = document.getElementById('data-kategori');
    const modal  = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalKategori'));
    const form   = document.getElementById('form-kategori');
    const judul  = document.getElementById('judul-modal');
    const tombolSimpan = document.getElementById('tombol-simpan');

    function bukaForm(mode, data = {}) {
        const ubah = mode === 'ubah';

        judul.textContent = ubah ? dataEl.dataset.judulUbah : dataEl.dataset.judulTambah;
        tombolSimpan.textContent = ubah ? 'Perbarui' : 'Simpan';
        form.action = ubah ? form.dataset.urlUbah.replace('__ID__', data.id) : form.dataset.urlSimpan;
        document.getElementById('field-method').disabled = !ubah;   // _method=PUT hanya saat ubah
        document.getElementById('field-form').value = mode;
        document.getElementById('field-id').value = data.id ?? '';
        form.elements['nama'].value = data.nama ?? '';

        modal.show();
    }

    function hapusAlert() {
        const alert = document.getElementById('alert-form');
        if (alert) alert.remove();
    }

    document.getElementById('btn-tambah').addEventListener('click', () => {
        hapusAlert();
        bukaForm('buat');
    });

    document.addEventListener('click', e => {
        const tombol = e.target.closest('.btn-ubah');
        if (!tombol) return;

        hapusAlert();
        bukaForm('ubah', { id: tombol.dataset.id, nama: tombol.dataset.nama });
    });

    // Kalau popup tadi ditolak validasi, buka lagi dengan isian yang tadi diketik
    if (dataEl.dataset.buka) {
        bukaForm(dataEl.dataset.buka, JSON.parse(dataEl.dataset.lama));
    }
</script>
@endpush