@extends('layouts.app')

@section('title', 'Data Pelanggan')

@push('styles')
<style>
    /* ===== Pembungkus dua kolom ===== */
    .area-pelanggan { display: block; }
    .kolom-detail { display: none; }

    .pelanggan-card { cursor: pointer; }
    .pelanggan-card.aktif { outline: 2px solid #fdcb2f; background: #fff7d1; }

    /* ===== MODE DETAIL: daftar di kiri, detail di kanan ===== */
    .area-pelanggan.mode-detail {
        display: grid;
        grid-template-columns: 340px 1fr;
        gap: 1.25rem;
        align-items: start;
    }
    .area-pelanggan.mode-detail .kolom-detail { display: block; }
    .area-pelanggan.mode-detail .pelanggan-grid {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }
    .area-pelanggan.mode-detail .pelanggan-card .aksi-kartu { display: none; }

    /* ===== Panel detail ===== */
    .panel-detail {
        background: #fff;
        border: 1px solid #222;
        border-radius: 14px;
        padding: 1.5rem;
        min-height: 420px;
    }
    .det-nama { font-size: 1.6rem; font-weight: 700; }
    .det-label { display: inline-block; width: 100px; color: #555; }
    .tab-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        background: #d9d9d9; border: 0; border-radius: 8px;
        padding: .25rem .7rem; font-size: .8rem; margin-right: .4rem;
    }
    .tab-chip:hover { background: #c9c9c9; }
    .tab-chip.aktif { background: #fdcb2f; font-weight: 600; }

    /* ===== Kartu hasil refraksi ===== */
    .refraksi-card {
        border: 1px solid #222;
        border-radius: 14px;
        padding: 1rem;
    }
    .chip-tgl {
        background: #d9d9d9; border-radius: 6px;
        padding: .05rem .5rem; font-size: .75rem; font-weight: 400;
    }
    .chip-ro {
        background: #d9d9d9; border-radius: 8px;
        padding: .3rem .9rem; font-size: .8rem;
    }
    .panel-mata {
        background: #d9d9d9;
        border-radius: 10px;
        padding: .75rem;
    }
    .badge-mata {
        background: #111; color: #fff; border-radius: 8px;
        padding: .15rem .4rem; font-size: .75rem; font-weight: 700;
    }
    .kotak-mono {
        background: #efefef; border: 1px solid #333; border-radius: 8px;
        padding: .1rem .6rem; font-size: .75rem;
    }
    .kotak-nilai {
        background: #efefef; border: 1px solid #333; border-radius: 10px;
        text-align: center; padding: .3rem .2rem; flex: 1; min-width: 56px;
        font-family: monospace; font-weight: 700; font-size: .85rem;
    }
    .kotak-nilai small {
        display: block; color: #888; font-weight: 400; font-size: .7rem;
    }
    .kotak-pd {
        background: #d9d9d9; border-radius: 10px;
        padding: .6rem .8rem; display: inline-block;
    }

    /* ===== Baris history ===== */
    .chip-hist {
        background: #d9d9d9; border: 0; border-radius: 8px;
        padding: .15rem 1.2rem; font-size: .75rem;
    }
    .chip-hist.aktif { background: #fdcb2f; font-weight: 600; }
    .hist-nav { background: transparent; border: 0; font-size: 1rem; padding: 0 .3rem; }

    /* ===== Modal resep ===== */
    .panel-mata-form { background: #d9d9d9; border-radius: 10px; padding: .75rem; }
</style>
@endpush

@section('content')
@php
    // Format angka dioptri: tanda + untuk nilai positif, 2 desimal
    $fmt = function ($v) {
        if ($v === null) { return '-'; }
        $s = number_format((float) $v, 2, '.', '');
        return ((float) $v > 0) ? '+' . $s : $s;
    };

    // Label metode pembayaran untuk tabel histori pembelian
    $metode = ['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'kartu' => 'Kartu'];

    // Pelanggan yang panel detailnya perlu dibuka lagi setelah simpan resep / validasi gagal
    $bukaDetailId = session('buka_customer') ?? old('_customer_id');
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Manajemen Data Pelanggan</h1>
    <button type="button" id="btnTambah" class="btn-abu border-0">
        <i class="bi bi-plus-lg"></i> Tambah Pelanggan Baru
    </button>
</div>

<div id="areaPelanggan" class="area-pelanggan" data-buka-detail="{{ $bukaDetailId }}">

    {{-- ===== Kolom kiri: pencarian + daftar kartu ===== --}}
    <div class="kolom-daftar">
        <form method="GET" action="{{ route('customer.index') }}" class="panel-abu">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau nomor pelanggan...">
            </div>
        </form>

        <div class="pelanggan-grid">
            @forelse ($customer as $c)
                <div class="pelanggan-card">
                    <div>
                        <div class="pelanggan-nama">{{ $c->nama }}</div>
                        <div class="pelanggan-wa">
                            <i class="bi bi-telephone"></i> {{ $c->no_whatsapp ?? 'Belum ada nomor' }}
                        </div>
                    </div>
                    <div class="d-flex gap-1 aksi-kartu">
                        <button type="button" class="icon-btn btn-ubah border-0" title="Ubah"
                                data-action="{{ route('customer.update', $c) }}"
                                data-hapus="{{ route('customer.destroy', $c) }}"
                                data-resep="{{ route('refraksi.store', $c) }}"
                                data-id="{{ $c->id }}"
                                data-nama="{{ $c->nama }}"
                                data-wa="{{ $c->no_whatsapp }}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <form action="{{ route('customer.destroy', $c) }}" method="POST"
                              onsubmit="return confirm('Hapus pelanggan {{ $c->nama }}?')">
                            @csrf
                            @method('DELETE')
                            <button class="icon-btn border-0" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-muted py-4">Belum ada data pelanggan.</div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $customer->links() }}
        </div>
    </div>

    {{-- ===== Kolom kanan: detail pelanggan (muncul saat kartu diklik) ===== --}}
    <div class="kolom-detail" id="kolomDetail">
        <div class="panel-detail">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="det-nama" id="detNama"></div>
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <a href="#" target="_blank" rel="noopener" class="btn-abu border-0 text-decoration-none" id="detWaLink">
                        <i class="bi bi-whatsapp"></i> Hubungi WA
                    </a>
                    <button type="button" class="btn-abu border-0" id="detEdit">
                        <i class="bi bi-pencil-square"></i> Edit Profil
                    </button>
                    <form method="POST" id="formHapusDetail" onsubmit="return confirm('Hapus pelanggan ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn-abu border-0"><i class="bi bi-trash"></i> Hapus</button>
                    </form>
                    <button type="button" class="btn-close ms-1" id="detTutup" aria-label="Tutup detail"></button>
                </div>
            </div>

            <div class="mb-3">
                <div><span class="det-label">No. WhatsApp</span> <span id="detWa"></span></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <button type="button" class="tab-chip aktif" id="tabRefraksi"><i class="bi bi-eye"></i> Histori Refraksi</button>
                    <button type="button" class="tab-chip" id="tabPembelian"><i class="bi bi-eye"></i> Histori Pembelian</button>
                </div>
                <button type="button" class="btn-abu border-0" id="btnTambahResep">
                    <i class="bi bi-plus-lg"></i> Tambah Resep Baru
                </button>
            </div>

            {{-- Satu blok per pelanggan; JavaScript menampilkan blok milik pelanggan yang diklik --}}
            @foreach ($customer as $c)
                <div class="blok-customer d-none" data-customer="{{ $c->id }}">

                    {{-- ----- Tab Histori Refraksi ----- --}}
                    <div class="blok-refraksi">
                        @if ($c->refraksi->isEmpty())
                            <div class="text-muted small border rounded p-3">
                                Belum ada riwayat refraksi untuk pelanggan ini.
                            </div>
                        @else
                            @foreach ($c->refraksi as $i => $r)
                                <div class="refraksi-card kartu-refraksi {{ $i > 0 ? 'd-none' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="fw-bold">
                                            Hasil Pemeriksaan Refraksi
                                            <span class="chip-tgl ms-1">{{ $r->tanggal_periksa->locale('id')->translatedFormat('j M Y') }}</span>
                                        </div>
                                        <div class="chip-ro">
                                            <i class="bi bi-person"></i> RO: <strong>{{ $r->user->nama ?? '-' }}</strong>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-3 flex-wrap mb-3">
                                        @foreach (['od' => ['OD', 'Mata Kanan', '(Oculus Dexter)'], 'os' => ['OS', 'Mata Kiri', '(Oculus Sinister)']] as $sisi => $m)
                                            <div class="panel-mata flex-fill">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge-mata">{{ $m[0] }}</span>
                                                        <div class="lh-sm small fw-semibold">{{ $m[1] }}<br>{{ $m[2] }}</div>
                                                    </div>
                                                    <span class="kotak-mono mono">Mono PD: {{ $r->{$sisi . '_mono_pd'} ?? '-' }}</span>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <div class="kotak-nilai"><small>Sphere</small>{{ $fmt($r->{$sisi . '_sphere'}) }}</div>
                                                    <div class="kotak-nilai"><small>Cylinder</small>{{ $fmt($r->{$sisi . '_cylinder'}) }}</div>
                                                    <div class="kotak-nilai"><small>Axis</small>{{ $r->{$sisi . '_axis'} !== null ? $r->{$sisi . '_axis'} . '°' : '-' }}</div>
                                                    <div class="kotak-nilai"><small>Add</small>{{ $fmt($r->{$sisi . '_add'}) }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="kotak-pd">
                                        <div class="small fw-semibold">Pupillary Distance (PD)</div>
                                        <div class="mono fw-bold">{{ $r->pd_total !== null ? $r->pd_total . ' mm' : '-' }}</div>
                                        <div style="font-size: .7rem;">Jarak titik tengah kedua pupil</div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Baris history: pilih tanggal pemeriksaan --}}
                            <div class="d-flex align-items-center gap-2 mt-3 small">
                                <span><i class="bi bi-clock-history"></i> History:</span>
                                <button type="button" class="hist-nav hist-prev" title="Lebih baru"><i class="bi bi-chevron-left"></i></button>
                                @foreach ($c->refraksi as $i => $r)
                                    <button type="button" class="chip-hist {{ $i === 0 ? 'aktif' : '' }}">
                                        {{ $r->tanggal_periksa->locale('id')->translatedFormat('j M Y') }}
                                    </button>
                                @endforeach
                                <button type="button" class="hist-nav hist-next" title="Lebih lama"><i class="bi bi-chevron-right"></i></button>
                            </div>
                        @endif
                    </div>

                    {{-- ----- Tab Histori Pembelian ----- --}}
                    <div class="blok-pembelian d-none">
                        @if ($c->penjualan->isEmpty())
                            <div class="text-muted small border rounded p-3">
                                Belum ada riwayat pembelian untuk pelanggan ini.
                            </div>
                        @else
                            <div class="small text-muted mb-2">
                                {{ $c->penjualan->count() }} transaksi &middot;
                                total belanja Rp {{ number_format($c->penjualan->sum('total'), 0, ',', '.') }}
                            </div>

                            <div class="border rounded" style="max-height: 380px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size: .8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No. Nota</th>
                                            <th>Tanggal</th>
                                            <th>Barang</th>
                                            <th>Metode</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($c->penjualan as $p)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('penjualan.show', $p) }}" class="mono text-decoration-none">{{ $p->nomor_nota }}</a>
                                                </td>
                                                <td>{{ $p->tanggal->locale('id')->translatedFormat('j M Y') }}</td>
                                                <td>{{ $p->detail->map(fn ($d) => ($d->barang->merk ?? '-') . ' x' . $d->jumlah)->implode(', ') }}</td>
                                                <td>{{ $metode[$p->metode_pembayaran] ?? $p->metode_pembayaran }}</td>
                                                <td class="text-end">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ===== Modal tambah / ubah pelanggan ===== --}}
<div class="modal fade" id="modalCustomer" tabindex="-1" aria-hidden="true"
     data-action-store="{{ route('customer.store') }}"
     data-buka="{{ $errors->any() ? old('_mode') : '' }}"
     data-action-lama="{{ (old('_mode') === 'edit' && old('_id')) ? route('customer.update', old('_id')) : '' }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #efefef;">
            <form id="formCustomer" method="POST" action="{{ route('customer.store') }}">
                @csrf
                <input type="hidden" name="_method" value="PUT" id="inputMethod" disabled>
                <input type="hidden" name="_mode" id="inputMode" value="{{ old('_mode', 'tambah') }}">
                <input type="hidden" name="_id" id="inputId" value="{{ old('_id') }}">

                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="judulModal">Tambah Pelanggan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Customer</label>
                        <input type="text" name="nama" id="inputNama" value="{{ old('nama') }}"
                               class="form-control @error('nama') is-invalid @enderror" required>
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No. WhatsApp</label>
                        <input type="text" name="no_whatsapp" id="inputWa" value="{{ old('no_whatsapp') }}"
                               class="form-control @error('no_whatsapp') is-invalid @enderror"
                               placeholder="081234567890">
                        <div class="form-text">Boleh diketik dengan 0 atau +62, otomatis dirapikan ke format 62...</div>
                        @error('no_whatsapp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn-abu border-0" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-abu border-0" id="tombolSimpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modal input pemeriksaan & resep baru ===== --}}
<div class="modal fade" id="modalResep" tabindex="-1" aria-hidden="true"
     data-buka="{{ (old('_form') === 'resep' && $errors->any()) ? '1' : '' }}">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: #efefef;">
            <form id="formResep" method="POST" action="">
                @csrf
                <input type="hidden" name="_form" value="resep">
                <input type="hidden" name="_customer_id" id="resepCustomerId" value="{{ old('_customer_id') }}">

                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        Input Pemeriksaan &amp; Resep Baru
                        <span class="fw-normal text-muted" id="resepNama"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    @if (old('_form') === 'resep' && $errors->any())
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $pesan)
                                    <li>{{ $pesan }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Periksa</label>
                            <input type="date" name="tanggal_periksa" class="form-control"
                                   value="{{ old('tanggal_periksa', now()->toDateString()) }}"
                                   max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Refraksionis (RO)</label>
                            <input type="text" class="form-control" value="{{ auth()->user()->nama ?? '-' }}" disabled>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        @foreach (['od' => 'Mata Kanan (Oculus Dexter)', 'os' => 'Mata Kiri (Oculus Sinister)'] as $sisi => $labelMata)
                            <div class="col-md-6">
                                <div class="panel-mata-form">
                                    <div class="fw-semibold mb-2">{{ $labelMata }}</div>
                                    <div class="row g-2">
                                        @foreach (['sphere' => ['Sphere', '0.25'], 'cylinder' => ['Cylinder', '0.25'], 'axis' => ['Axis (0-180)', '1'], 'add' => ['Add', '0.25'], 'mono_pd' => ['Mono PD (mm)', '0.5']] as $kolom => [$label, $step])
                                            <div class="col-6">
                                                <label class="form-label small mb-0">{{ $label }}</label>
                                                <input type="number" step="{{ $step }}"
                                                       name="{{ $sisi }}_{{ $kolom }}"
                                                       value="{{ old($sisi . '_' . $kolom) }}"
                                                       class="form-control form-control-sm input-nilai-resep">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pupillary Distance / PD total (mm)</label>
                            <input type="number" step="0.5" name="pd_total" value="{{ old('pd_total') }}"
                                   class="form-control input-nilai-resep">
                            <div class="form-text">Jarak titik tengah kedua pupil.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn-abu border-0" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-abu border-0">Simpan Resep</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ---------- Modal tambah / ubah pelanggan ----------
    var modalEl = document.getElementById('modalCustomer');
    var modal = new bootstrap.Modal(modalEl);

    var form = document.getElementById('formCustomer');
    var judul = document.getElementById('judulModal');
    var tombolSimpan = document.getElementById('tombolSimpan');
    var inputMethod = document.getElementById('inputMethod');
    var inputMode = document.getElementById('inputMode');
    var inputId = document.getElementById('inputId');
    var inputNama = document.getElementById('inputNama');
    var inputWa = document.getElementById('inputWa');

    function bersihkanError() {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
    }

    function modeTambah(kosongkan) {
        form.action = modalEl.dataset.actionStore;
        inputMethod.disabled = true;
        inputMode.value = 'tambah';
        inputId.value = '';
        judul.textContent = 'Tambah Pelanggan Baru';
        tombolSimpan.textContent = 'Simpan';
        if (kosongkan) {
            inputNama.value = '';
            inputWa.value = '';
        }
    }

    function modeUbah(action, id, nama, wa) {
        form.action = action;
        inputMethod.disabled = false;
        inputMode.value = 'edit';
        inputId.value = id;
        judul.textContent = 'Ubah Data Pelanggan';
        tombolSimpan.textContent = 'Perbarui';
        if (nama !== null) {
            inputNama.value = nama;
            inputWa.value = wa || '';
        }
    }

    document.getElementById('btnTambah').addEventListener('click', function () {
        bersihkanError();
        modeTambah(true);
        modal.show();
    });

    var bukaLagi = modalEl.dataset.buka;
    if (bukaLagi === 'edit') {
        modeUbah(modalEl.dataset.actionLama, inputId.value, null, null);
        modal.show();
    } else if (bukaLagi === 'tambah') {
        modeTambah(false);
        modal.show();
    }

    // ---------- Mode kotak <-> mode detail ----------
    var area = document.getElementById('areaPelanggan');
    var kartuList = document.querySelectorAll('.pelanggan-card');
    var detNama = document.getElementById('detNama');
    var detWa = document.getElementById('detWa');
    var detWaLink = document.getElementById('detWaLink');
    var detEdit = document.getElementById('detEdit');
    var detTutup = document.getElementById('detTutup');
    var formHapusDetail = document.getElementById('formHapusDetail');
    var blokCustomerList = document.querySelectorAll('.blok-customer');

    var tabRefraksi = document.getElementById('tabRefraksi');
    var tabPembelian = document.getElementById('tabPembelian');
    var btnTambahResep = document.getElementById('btnTambahResep');

    var dataAktif = null; // data pelanggan yang sedang dibuka

    // Ganti tab: 'refraksi' atau 'pembelian'
    function pilihTab(nama) {
        tabRefraksi.classList.toggle('aktif', nama === 'refraksi');
        tabPembelian.classList.toggle('aktif', nama === 'pembelian');

        document.querySelectorAll('.blok-refraksi').forEach(function (b) {
            b.classList.toggle('d-none', nama !== 'refraksi');
        });
        document.querySelectorAll('.blok-pembelian').forEach(function (b) {
            b.classList.toggle('d-none', nama !== 'pembelian');
        });

        // Tombol Tambah Resep hanya relevan di tab Refraksi
        btnTambahResep.style.display = (nama === 'refraksi') ? '' : 'none';
    }

    tabRefraksi.addEventListener('click', function () { pilihTab('refraksi'); });
    tabPembelian.addEventListener('click', function () { pilihTab('pembelian'); });

    function tutupDetail() {
        area.classList.remove('mode-detail');
        kartuList.forEach(function (k) { k.classList.remove('aktif'); });
        dataAktif = null;
    }

    function bukaDetail(kartu) {
        var tombol = kartu.querySelector('.btn-ubah');
        if (!tombol) { return; }
        dataAktif = tombol.dataset;

        kartuList.forEach(function (k) { k.classList.remove('aktif'); });
        kartu.classList.add('aktif');

        detNama.textContent = dataAktif.nama;
        detWa.textContent = dataAktif.wa ? dataAktif.wa : 'Belum ada nomor';

        if (dataAktif.wa) {
            detWaLink.href = 'https://wa.me/' + dataAktif.wa.replace(/\D/g, '');
            detWaLink.style.display = '';
        } else {
            detWaLink.style.display = 'none';
        }

        formHapusDetail.action = dataAktif.hapus;

        // Tampilkan blok milik pelanggan ini saja, selalu mulai dari tab Refraksi
        blokCustomerList.forEach(function (blok) {
            blok.classList.toggle('d-none', blok.dataset.customer !== dataAktif.id);
        });
        pilihTab('refraksi');

        area.classList.add('mode-detail');
    }

    kartuList.forEach(function (kartu) {
        kartu.addEventListener('click', function (e) {
            if (e.target.closest('.aksi-kartu')) { return; }
            bukaDetail(kartu);
        });
    });

    document.querySelectorAll('.btn-ubah').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            bersihkanError();
            modeUbah(tombol.dataset.action, tombol.dataset.id, tombol.dataset.nama, tombol.dataset.wa);
            modal.show();
        });
    });

    detEdit.addEventListener('click', function () {
        if (!dataAktif) { return; }
        bersihkanError();
        modeUbah(dataAktif.action, dataAktif.id, dataAktif.nama, dataAktif.wa);
        modal.show();
    });

    detTutup.addEventListener('click', tutupDetail);

    // ---------- Riwayat refraksi: pilih tanggal pemeriksaan ----------
    document.querySelectorAll('.blok-refraksi').forEach(function (blok) {
        var kartuRefraksi = blok.querySelectorAll('.kartu-refraksi');
        var chips = blok.querySelectorAll('.chip-hist');
        var prev = blok.querySelector('.hist-prev');
        var next = blok.querySelector('.hist-next');
        var aktif = 0;

        function pilih(i) {
            if (i < 0 || i >= kartuRefraksi.length) { return; }
            aktif = i;
            kartuRefraksi.forEach(function (k, n) { k.classList.toggle('d-none', n !== i); });
            chips.forEach(function (c, n) { c.classList.toggle('aktif', n === i); });
        }

        chips.forEach(function (c, n) {
            c.addEventListener('click', function () { pilih(n); });
        });
        if (prev) { prev.addEventListener('click', function () { pilih(aktif - 1); }); }
        if (next) { next.addEventListener('click', function () { pilih(aktif + 1); }); }
    });

    // ---------- Modal Tambah Resep Baru ----------
    var modalResepEl = document.getElementById('modalResep');
    var modalResep = new bootstrap.Modal(modalResepEl);
    var formResep = document.getElementById('formResep');
    var resepCustomerId = document.getElementById('resepCustomerId');
    var resepNama = document.getElementById('resepNama');

    function bukaModalResep(kosongkan) {
        if (!dataAktif) { return; }
        formResep.action = dataAktif.resep;
        resepCustomerId.value = dataAktif.id;
        resepNama.textContent = '- ' + dataAktif.nama;
        if (kosongkan) {
            formResep.querySelectorAll('.input-nilai-resep').forEach(function (el) {
                el.value = '';
            });
        }
        modalResep.show();
    }

    btnTambahResep.addEventListener('click', function () {
        bukaModalResep(true);
    });

    // ---------- Buka lagi panel detail setelah simpan resep / validasi gagal ----------
    var idBuka = area.dataset.bukaDetail;
    if (idBuka) {
        var tombolBuka = document.querySelector('.btn-ubah[data-id="' + idBuka + '"]');
        if (tombolBuka) {
            bukaDetail(tombolBuka.closest('.pelanggan-card'));
            if (modalResepEl.dataset.buka === '1') {
                bukaModalResep(false); // pertahankan isian lama dan tampilkan pesan error
            }
        }
    }
});
</script>
@endsection