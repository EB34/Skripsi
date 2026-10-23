@extends('layouts.app')

@section('title', 'Data Barang')

@push('styles')
<style>
    button.btn-abu { cursor: pointer; }
</style>
@endpush

@section('content')
@php
    // Dipakai kalau popup ditolak validasi: popup dibuka lagi dengan isian tadi
    $buka = in_array(old('_form'), ['buat', 'ubah'], true) ? old('_form') : '';
    $lama = [
        'id'         => old('_id'),
        'kode_model' => old('kode_model'),
        'jenis'      => old('jenis'),
        'merk'       => old('merk'),
        'harga_jual' => old('harga_jual'),
    ];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Manajemen Data Barang</h1>
    <button type="button" class="btn-abu" id="btn-tambah">
        <i class="bi bi-plus-lg"></i> Tambah Barang Baru
    </button>
</div>

{{-- Panel pencarian + filter jenis --}}
<form method="GET" action="{{ route('barang.index') }}" class="panel-abu d-flex align-items-center gap-3">
    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode model atau merk...">
    </div>

    @if (request('jenis'))
        <input type="hidden" name="jenis" value="{{ request('jenis') }}">
    @endif

    @foreach ([['frame', 'Frame', 'bi-eyeglasses'], ['lensa', 'Lensa', 'bi-record-circle'], ['softlens', 'Softlens', 'bi-eye']] as [$nilai, $label, $ikon])
        <a href="{{ route('barang.index', array_filter(['q' => request('q'), 'jenis' => request('jenis') === $nilai ? null : $nilai])) }}"
           class="chip {{ request('jenis') === $nilai ? 'active' : '' }}">
            <i class="bi {{ $ikon }}"></i> {{ $label }}
        </a>
    @endforeach
</form>

{{-- Kartu barang --}}
<div class="barang-grid">
    @forelse ($barang as $b)
        @php
            $kelasStok = $b->stok_tersedia <= 0 ? 'habis' : ($b->stok_tersedia <= $batasStok ? 'menipis' : '');
            $ikonJenis = ['frame' => 'bi-eyeglasses', 'lensa' => 'bi-record-circle', 'softlens' => 'bi-eye'][$b->jenis] ?? 'bi-box';
        @endphp

        <div class="barang-card">
            <div class="barang-foto"><i class="bi {{ $ikonJenis }}"></i></div>

            <div class="barang-body">
                <div class="barang-top">
                    <span class="kode-chip mono">{{ $b->kode_model }}</span>
                    <span class="badge-stok {{ $kelasStok }}">Stok: {{ $b->stok_tersedia }}</span>
                </div>

                <div class="barang-nama">{{ $b->merk }}</div>
                <div class="barang-info">
                    Jenis: {{ ucfirst($b->jenis) }}<br>
                    Stok rusak: {{ $b->stok_rusak }}
                </div>

                <div class="barang-foot">
                    <div>
                        <div class="harga-label">Harga Jual</div>
                        <div class="harga">Rp {{ number_format($b->harga_jual, 0, ',', '.') }}</div>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="icon-btn btn-ubah" title="Ubah"
                                data-id="{{ $b->id }}"
                                data-kode="{{ $b->kode_model }}"
                                data-jenis="{{ $b->jenis }}"
                                data-merk="{{ $b->merk }}"
                                data-harga="{{ (int) $b->harga_jual }}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <form action="{{ route('barang.destroy', $b) }}" method="POST"
                              onsubmit="return confirm('Hapus barang {{ $b->kode_model }}?')">
                            @csrf
                            @method('DELETE')
                            <button class="icon-btn" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-muted py-4">Belum ada data barang.</div>
    @endforelse
</div>

<div class="mt-4">
    {{ $barang->links() }}
</div>

{{-- ===== Popup: Tambah / Ubah Barang (satu popup untuk keduanya) ===== --}}
<div class="modal fade" id="modalBarang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="form-barang" action="{{ route('barang.store') }}"
                  data-url-simpan="{{ route('barang.store') }}"
                  data-url-ubah="{{ route('barang.update', '__ID__') }}">
                @csrf
                <input type="hidden" name="_method" value="PUT" id="field-method" disabled>
                <input type="hidden" name="_form" id="field-form" value="buat">
                <input type="hidden" name="_id" id="field-id" value="">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="judul-modal">Tambah Barang Baru</h5>
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

                    <div class="mb-3">
                        <label class="form-label">Kode Model</label>
                        <input type="text" name="kode_model" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="jenis" class="form-select" required>
                            <option value="">-- Pilih jenis --</option>
                            <option value="frame">Frame</option>
                            <option value="lensa">Lensa</option>
                            <option value="softlens">Softlens</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Merk</label>
                        <input type="text" name="merk" class="form-control" required>
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Harga Jual (Rp)</label>
                        <input type="number" name="harga_jual" min="0" step="any" class="form-control" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-abu" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="tombol-simpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Data untuk JavaScript dititipkan di atribut HTML (tanpa Blade di dalam tag script) --}}
<div id="data-barang" hidden
     data-buka="{{ $buka }}"
     data-lama="{{ json_encode($lama) }}"></div>
@endsection

@push('scripts')
<script>
    const modalEl  = document.getElementById('modalBarang');
    const modal    = bootstrap.Modal.getOrCreateInstance(modalEl);
    const form     = document.getElementById('form-barang');
    const judul    = document.getElementById('judul-modal');
    const elMethod = document.getElementById('field-method');
    const elForm   = document.getElementById('field-form');
    const elId     = document.getElementById('field-id');
    const tombolSimpan = document.getElementById('tombol-simpan');

    function bukaForm(mode, data = {}) {
        const ubah = mode === 'ubah';

        judul.textContent        = ubah ? 'Ubah Barang' : 'Tambah Barang Baru';
        tombolSimpan.textContent = ubah ? 'Perbarui' : 'Simpan';
        form.action              = ubah ? form.dataset.urlUbah.replace('__ID__', data.id) : form.dataset.urlSimpan;
        elMethod.disabled        = !ubah;      // _method=PUT hanya dikirim saat ubah
        elForm.value             = mode;
        elId.value               = data.id ?? '';

        form.elements['kode_model'].value = data.kode_model ?? '';
        form.elements['jenis'].value      = data.jenis ?? '';
        form.elements['merk'].value       = data.merk ?? '';
        form.elements['harga_jual'].value = data.harga_jual ?? '';

        modal.show();
    }

    function hapusAlert() {
        const alert = document.getElementById('alert-form');
        if (alert) alert.remove();
    }

    // Tombol "Tambah Barang Baru"
    document.getElementById('btn-tambah').addEventListener('click', () => {
        hapusAlert();
        bukaForm('buat');
    });

    // Tombol pensil di tiap kartu
    document.addEventListener('click', e => {
        const tombol = e.target.closest('.btn-ubah');
        if (!tombol) return;

        hapusAlert();
        bukaForm('ubah', {
            id: tombol.dataset.id,
            kode_model: tombol.dataset.kode,
            jenis: tombol.dataset.jenis,
            merk: tombol.dataset.merk,
            harga_jual: tombol.dataset.harga,
        });
    });

    // Kalau popup tadi ditolak validasi, buka lagi dengan isian yang tadi diketik
    const dataEl = document.getElementById('data-barang');
    if (dataEl.dataset.buka) {
        bukaForm(dataEl.dataset.buka, JSON.parse(dataEl.dataset.lama));
    }
</script>
@endpush