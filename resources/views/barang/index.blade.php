@extends('layouts.app')

@section('title', 'Data Barang')

@push('styles')
<style>
    button.btn-abu { cursor: pointer; }
    .ukuran-lensa { font-size: .72rem; font-weight: 600; color: #1f5f87; }
    .select-kategori {
        height: 38px; border: 0; border-radius: 8px;
        padding: 0 10px; font-size: .8rem; background: #fff; max-width: 200px;
    }
</style>
@endpush

@section('content')
@php
    // Dipakai kalau popup ditolak validasi: popup dibuka lagi dengan isian tadi
    $buka = in_array(old('_form'), ['buat', 'ubah'], true) ? old('_form') : '';
    $lama = [
        'id'          => old('_id'),
        'kode_model'  => old('kode_model'),
        'jenis'       => old('jenis'),
        'merk'        => old('merk'),
        'kategori_id' => old('kategori_id'),
        'harga_jual'  => old('harga_jual'),
        'sph'         => old('sph'),
        'cyl'         => old('cyl'),
        'tanpa_stok'  => (bool) old('tanpa_stok'),
    ];

    // Pilihan kategori untuk JavaScript popup
    $kategoriJs = $daftarKategori->map(fn ($k) => ['id' => $k->id, 'jenis' => $k->jenis, 'nama' => $k->nama])->values();

    // Pilihan filter: kalau satu jenis dipilih, hanya kategori jenis itu yang tampil
    $kategoriFilter = request('jenis') ? $daftarKategori->where('jenis', request('jenis')) : $daftarKategori;
    $namaJenis = \App\Models\KategoriBarang::JENIS;
    $labelKategori = \App\Models\KategoriBarang::LABEL;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Manajemen Data Barang</h1>
    <button type="button" class="btn-abu" id="btn-tambah">
        <i class="bi bi-plus-lg"></i> Tambah Barang Baru
    </button>
</div>

{{-- Panel pencarian + filter jenis + filter kategori --}}
<form method="GET" action="{{ route('barang.index') }}" class="panel-abu d-flex align-items-center gap-3">
    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode model, merk, kategori, atau ukuran SPH...">
    </div>

    @if (request('jenis'))
        <input type="hidden" name="jenis" value="{{ request('jenis') }}">
    @endif

    @if ($kategoriFilter->isNotEmpty())
        <select name="kategori" class="select-kategori" onchange="this.form.submit()">
            <option value="">Semua kategori</option>
            @foreach ($kategoriFilter->groupBy('jenis') as $jenisGrup => $grup)
                <optgroup label="{{ $labelKategori[$jenisGrup] ?? ucfirst($jenisGrup) }}">
                    @foreach ($grup as $k)
                        <option value="{{ $k->id }}" @selected((string) request('kategori') === (string) $k->id)>{{ $k->nama }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    @endif

    @foreach ([['frame', 'Frame', 'bi-eyeglasses'], ['lensa', 'Lensa', 'bi-record-circle'], ['softlens', 'Softlens', 'bi-eye']] as [$nilai, $label, $ikon])
        <a href="{{ route('barang.index', array_filter(['q' => request('q'), 'jenis' => request('jenis') === $nilai ? null : $nilai])) }}"
           class="chip {{ request('jenis') === $nilai ? 'active' : '' }}">
            <i class="bi {{ $ikon }}"></i> {{ $label }}
        </a>
    @endforeach

    @if (request('q') || request('jenis') || request('kategori'))
        <a href="{{ route('barang.index') }}" class="text-muted small text-decoration-none">Reset</a>
    @endif
</form>

{{-- Kartu barang --}}
<div class="barang-grid">
    @forelse ($barang as $b)
        @php
            // Definisi "menipis" per barang (stok_minimum), sama dengan halaman Pengelolaan Stok
            $kelasStok = $b->status_stok === 'aman' ? '' : $b->status_stok;
            $ikonJenis = ['frame' => 'bi-eyeglasses', 'lensa' => 'bi-record-circle', 'softlens' => 'bi-eye'][$b->jenis] ?? 'bi-box';
        @endphp

        <div class="barang-card">
            <div class="barang-foto"><i class="bi {{ $ikonJenis }}"></i></div>

            <div class="barang-body">
                <div class="barang-top">
                    <span class="kode-chip mono">{{ $b->kode_model }}</span>
                    @if ($b->tanpa_stok)
                        <span class="badge-stok">Custom</span>
                    @else
                        <span class="badge-stok {{ $kelasStok }}">Stok: {{ $b->stok_tersedia }}</span>
                    @endif
                </div>

                <div class="barang-nama">{{ $b->merk }}</div>
                <div class="barang-info">
                    Jenis: {{ ucfirst($b->jenis) }}<br>
                    @if ($b->kategori)
                        {{ $labelKategori[$b->jenis] ?? 'Kategori' }}: {{ $b->kategori->nama }}<br>
                    @endif
                    @if ($b->tanpa_stok)
                        <span class="ukuran-lensa">Ukuran diisi saat transaksi</span><br>
                        Tanpa stok (dipesan ke supplier)
                    @else
                        @if (in_array($b->jenis, ['lensa', 'softlens'], true))
                            <span class="ukuran-lensa">{{ $b->ukuran_label ?? 'Ukuran belum diisi' }}</span><br>
                        @endif
                        Stok rusak: {{ $b->stok_rusak }}
                    @endif
                </div>

                <div class="barang-foot">
                    <div>
                        <div class="harga-label">{{ $b->tanpa_stok ? 'Harga Awal' : 'Harga Jual' }}</div>
                        <div class="harga">Rp {{ number_format($b->harga_jual, 0, ',', '.') }}</div>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="icon-btn btn-ubah" title="Ubah"
                                data-id="{{ $b->id }}"
                                data-kode="{{ $b->kode_model }}"
                                data-jenis="{{ $b->jenis }}"
                                data-merk="{{ $b->merk }}"
                                data-kategori="{{ $b->kategori_id }}"
                                data-harga="{{ (int) $b->harga_jual }}"
                                data-sph="{{ $b->sph }}"
                                data-cyl="{{ $b->cyl }}"
                                data-custom="{{ $b->tanpa_stok ? 1 : 0 }}">
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

                    {{-- Kategori: Material (frame), Tipe Lensa, atau Tipe Softlens. Pilihannya mengikuti jenis. --}}
                    <div class="mb-3 d-none" id="blok-kategori">
                        <label class="form-label" id="label-kategori">Kategori</label>
                        <select name="kategori_id" class="form-select">
                            <option value="">-- Tidak diisi --</option>
                        </select>
                        <div class="form-text d-none" id="info-kategori-kosong">
                            Belum ada pilihan untuk jenis ini. Tambahkan lewat menu Kategori Barang.
                        </div>
                    </div>

                    {{-- Lensa custom: hanya tampil kalau jenis = Lensa --}}
                    <div class="mb-3 d-none" id="blok-custom">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tanpa_stok" value="1" id="cek-custom">
                            <label class="form-check-label fw-semibold" for="cek-custom">Lensa custom (tanpa stok)</label>
                        </div>
                        <div class="form-text">
                            Untuk lensa yang dipesan manual ke supplier. Stok tidak dihitung, dan ukuran serta harga diisi saat transaksi.
                        </div>
                    </div>

                    {{-- Ukuran: hanya tampil kalau jenis = Lensa atau Softlens (dan bukan lensa custom) --}}
                    <div class="row g-3 mb-3 d-none" id="blok-ukuran">
                        <div class="col-6">
                            <label class="form-label">SPH <span class="text-danger">*</span></label>
                            <input type="number" name="sph" step="0.25" min="-20" max="20" class="form-control"
                                   placeholder="Contoh: -2.25">
                        </div>
                        <div class="col-6">
                            <label class="form-label">CYL</label>
                            <input type="number" name="cyl" step="0.25" min="-10" max="10" class="form-control"
                                   placeholder="Kosong = 0">
                        </div>
                        <div class="col-12 form-text mt-1">Satu kode model lensa atau softlens mewakili satu ukuran. Kelipatan 0,25.</div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label" id="label-harga">Harga Jual (Rp)</label>
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
     data-lama="{{ json_encode($lama) }}"
     data-kategori="{{ json_encode($kategoriJs) }}"></div>
@endsection

@push('scripts')
<script>
    const dataEl   = document.getElementById('data-barang');
    const modalEl  = document.getElementById('modalBarang');
    const modal    = bootstrap.Modal.getOrCreateInstance(modalEl);
    const form     = document.getElementById('form-barang');
    const judul    = document.getElementById('judul-modal');
    const elMethod = document.getElementById('field-method');
    const elForm   = document.getElementById('field-form');
    const elId     = document.getElementById('field-id');
    const tombolSimpan = document.getElementById('tombol-simpan');
    const blokUkuran   = document.getElementById('blok-ukuran');
    const blokCustom   = document.getElementById('blok-custom');
    const cekCustom    = document.getElementById('cek-custom');
    const labelHarga   = document.getElementById('label-harga');
    const blokKategori = document.getElementById('blok-kategori');
    const labelKategori = document.getElementById('label-kategori');
    const infoKosong   = document.getElementById('info-kategori-kosong');

    const semuaKategori = JSON.parse(dataEl.dataset.kategori);
    const namaKategori  = { frame: 'Material', lensa: 'Tipe Lensa', softlens: 'Tipe Softlens' };

    // Isi dropdown kategori dengan pilihan yang jenisnya sama dengan jenis barang
    function isiKategori(jenis, terpilih = '') {
        const sel = form.elements['kategori_id'];
        sel.innerHTML = '<option value="">-- Tidak diisi --</option>';

        const pilihan = semuaKategori.filter(k => k.jenis === jenis);
        pilihan.forEach(k => {
            const opt = document.createElement('option');
            opt.value = k.id;
            opt.textContent = k.nama;
            sel.appendChild(opt);
        });

        sel.value = String(terpilih ?? '');
        blokKategori.classList.toggle('d-none', !jenis);
        labelKategori.textContent = namaKategori[jenis] ?? 'Kategori';
        infoKosong.classList.toggle('d-none', !jenis || pilihan.length > 0);
    }

    // - Checkbox custom hanya muncul untuk jenis Lensa
    // - SPH/CYL muncul untuk lensa dan softlens, kecuali lensa custom (ukurannya diisi saat transaksi)
    function aturUkuran() {
        const jenis = form.elements['jenis'].value;
        const lensa = jenis === 'lensa';

        blokCustom.classList.toggle('d-none', !lensa);
        if (!lensa) cekCustom.checked = false;

        const berukuran = (lensa || jenis === 'softlens') && !cekCustom.checked;
        blokUkuran.classList.toggle('d-none', !berukuran);
        form.elements['sph'].required = berukuran;

        labelHarga.textContent = cekCustom.checked ? 'Harga Awal (Rp), boleh diubah saat transaksi' : 'Harga Jual (Rp)';
    }

    form.elements['jenis'].addEventListener('change', () => {
        isiKategori(form.elements['jenis'].value);   // ganti jenis = pilihan kategori ikut berganti
        aturUkuran();
    });
    cekCustom.addEventListener('change', aturUkuran);

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
        form.elements['sph'].value        = data.sph ?? '';
        form.elements['cyl'].value        = data.cyl ?? '';
        cekCustom.checked                 = !!data.tanpa_stok;

        isiKategori(data.jenis ?? '', data.kategori_id ?? '');
        aturUkuran();
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
            kategori_id: tombol.dataset.kategori,
            harga_jual: tombol.dataset.harga,
            sph: tombol.dataset.sph,
            cyl: tombol.dataset.cyl,
            tanpa_stok: tombol.dataset.custom === '1',
        });
    });

    // Kalau popup tadi ditolak validasi, buka lagi dengan isian yang tadi diketik
    if (dataEl.dataset.buka) {
        bukaForm(dataEl.dataset.buka, JSON.parse(dataEl.dataset.lama));
    }
</script>
@endpush