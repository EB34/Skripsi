@extends('layouts.app')

@section('title', 'Pesanan Kacamata')

@push('styles')
<style>
    .btn-pesanan-baru {
        background: #3d6f8a; color: #fff; border: 0;
        border-radius: 8px; padding: 9px 18px;
        font-size: .82rem; font-weight: 600;
    }
    .btn-pesanan-baru:hover { background: #335d75; color: #fff; }

    .stat-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin: 18px 0; }
    @media (max-width: 992px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
    .stat-card { background: #fff; border: 1px solid #cfcfcf; border-radius: 10px; padding: 8px 14px; }
    .stat-card .lbl { font-size: .72rem; font-weight: 500; }
    .stat-card .num { font-size: 1.1rem; font-weight: 700; color: #1e1e1e; }
    .stat-card.menunggu .lbl { color: #9a6b00; }
    .stat-card.dikerjakan .lbl { color: #1f5f87; }
    .stat-card.siap_diambil .lbl { color: #1b7a1b; }
    .stat-card.selesai .lbl { color: #555; }

    .filter-status {
        height: 38px; border: 0; border-radius: 8px; background: #fff;
        padding: 0 14px; font-size: .8rem; min-width: 210px; text-align: center;
    }

    .tabel-pesanan-wrap { border: 1px solid #cfcfcf; border-radius: 14px; overflow: hidden; margin-top: 18px; background: #fff; }
    .tabel-pesanan { width: 100%; border-collapse: collapse; color: #1e1e1e; }
    .tabel-pesanan th {
        background: #efefef; text-align: center; font-size: .82rem; font-weight: 700;
        padding: 14px 8px; border-left: 1px solid #cfcfcf;
    }
    .tabel-pesanan td {
        padding: 14px 10px; font-size: .78rem; vertical-align: middle;
        border-left: 1px solid #cfcfcf; border-top: 1px solid #cfcfcf;
    }
    .tabel-pesanan th:first-child, .tabel-pesanan td:first-child { border-left: 0; }

    .no-order { color: #1b6b2f; font-weight: 700; }
    .tgl-kecil { font-size: .68rem; color: #666; }
    .estimasi { color: #c0392b; font-size: .7rem; }

    .tag-frame, .tag-lensa {
        display: inline-block; font-size: .62rem; border-radius: 4px; padding: 0 6px; margin-bottom: 2px;
    }
    .tag-frame { background: #d6f5d6; border: 1px solid #8fd48f; color: #1b7a1b; }
    .tag-lensa { background: #d6e9fb; border: 1px solid #9cc3e6; color: #1f5f87; }

    .resep-box { border: 1px solid #1e1e1e; border-radius: 6px; padding: 6px 8px; font-size: .7rem; min-width: 190px; }
    .resep-baris { display: flex; gap: 8px; margin-bottom: 4px; }
    .resep-baris .sisi { font-weight: 700; line-height: 1.15; }
    .resep-foot { border-top: 1px solid #1e1e1e; margin-top: 4px; padding-top: 3px; display: flex; justify-content: space-between; }
    .resep-foot a { color: #1b7a1b; font-weight: 600; text-decoration: underline; cursor: pointer; }

    .pill-status, .select-status {
        display: inline-block; border-radius: 4px; font-size: .72rem; font-weight: 500;
        padding: 3px 10px; border: 1px solid; min-width: 140px; text-align: center;
    }
    .select-status { height: 26px; padding: 0 6px; }
    .st-menunggu     { background: #fff1cc; color: #8a5a00; border-color: #f0c35a; }
    .st-dikerjakan   { background: #dbeafe; color: #1f5f87; border-color: #9cc3e6; }
    .st-siap_diambil { background: #d6f5d6; color: #1b7a1b; border-color: #8fd48f; }
    .st-selesai      { background: #e5e5e5; color: #444;    border-color: #bbb; }

    .btn-ambil {
        display: block; margin: 6px auto 0; border: 0; border-radius: 6px;
        background: var(--kuning); color: #000; font-size: .7rem; font-weight: 600; padding: 3px 12px;
    }
    .btn-ambil:hover { background: #f0bb15; }
</style>
@endpush

@section('content')
@php
    // Angka dengan tanda (+/-) tanpa nol berlebih: -2.50 menjadi -2.5
    $fmt = function ($v) {
        if ($v === null || $v === '') return '-';
        $teks = rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        return ((float) $v > 0 ? '+' : '') . $teks;
    };
    // Angka tanpa tanda (untuk ADD dan PD)
    $num = function ($v) {
        if ($v === null || $v === '') return '-';
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    };
    $namaBarang = fn ($b) => $b ? trim(($b->merk ?? '') . ' ' . ($b->kode_model ?? '')) : null;

    // Daftar resep per pelanggan untuk dropdown di modal "Buat Pesanan Baru"
    $refraksiPerCustomer = $customer->mapWithKeys(fn ($c) => [
        $c->id => $c->refraksi->map(fn ($r) => [
            'id'    => $r->id,
            'label' => $r->tanggal_periksa->format('d/m/Y')
                . ' — OD ' . $fmt($r->od_sphere) . ' / ' . $fmt($r->od_cylinder) . ' / ' . ($r->od_axis ?? '-')
                . ' · OS ' . $fmt($r->os_sphere) . ' / ' . $fmt($r->os_cylinder) . ' / ' . ($r->os_axis ?? '-'),
        ])->values(),
    ])->all();

    $userLogin = auth()->user();
@endphp

<div class="d-flex justify-content-between align-items-center">
    <h1 class="page-title">Pesanan Kacamata</h1>
    <button type="button" class="btn-pesanan-baru" data-bs-toggle="modal" data-bs-target="#modalPesanan">
        <i class="bi bi-plus-lg"></i> Buat Pesanan Baru
    </button>
</div>

{{-- ===== Kartu statistik ===== --}}
<div class="stat-grid">
    <div class="stat-card"><div class="lbl">Total Pesanan</div><div class="num">{{ $statistik['total'] }}</div></div>
    <div class="stat-card menunggu"><div class="lbl">Menunggu</div><div class="num">{{ $statistik['menunggu'] }}</div></div>
    <div class="stat-card dikerjakan"><div class="lbl">Dikerjakan</div><div class="num">{{ $statistik['dikerjakan'] }}</div></div>
    <div class="stat-card siap_diambil"><div class="lbl">Siap Diambil</div><div class="num">{{ $statistik['siap_diambil'] }}</div></div>
    <div class="stat-card selesai"><div class="lbl">Selesai</div><div class="num">{{ $statistik['selesai'] }}</div></div>
</div>

{{-- ===== Pencarian dan filter ===== --}}
<form method="GET" action="{{ route('pesanan.index') }}" class="panel-abu d-flex align-items-center gap-3">
    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" name="q" value="{{ $cari }}" placeholder="Cari no.order, nama pelanggan, no.Wa">
    </div>
    <div class="d-flex align-items-center gap-2">
        <span style="font-size:.78rem;">Status:</span>
        <select name="status" class="filter-status" onchange="this.form.submit()">
            <option value="">Semua Status Pesanan</option>
            @foreach (\App\Models\PesananKacamata::STATUS as $kode => $label)
                <option value="{{ $kode }}" @selected($status === $kode)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</form>

{{-- ===== Tabel pesanan ===== --}}
<div class="tabel-pesanan-wrap">
    <table class="tabel-pesanan">
        <thead>
            <tr>
                <th>No.Order<br>&amp; Tgl</th>
                <th>Pelanggan<br>&amp; Kontak</th>
                <th>Frame &amp;<br>Lensa</th>
                <th>Resep Ukuran</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($pesanan as $p)
            @php
                $r = $p->refraksi;
                $resep = [
                    'tanggal' => $r->tanggal_periksa->format('d/m/Y'),
                    'pd'      => $num($r->pd_total),
                    'od'      => ['sph' => $fmt($r->od_sphere), 'cyl' => $fmt($r->od_cylinder), 'axis' => $r->od_axis ?? '-', 'add' => $num($r->od_add), 'pd' => $num($r->od_mono_pd)],
                    'os'      => ['sph' => $fmt($r->os_sphere), 'cyl' => $fmt($r->os_cylinder), 'axis' => $r->os_axis ?? '-', 'add' => $num($r->os_add), 'pd' => $num($r->os_mono_pd)],
                ];
                $bolehSelesaikanPengerjaan = (int) $p->petugas_id === (int) $userLogin->id || $userLogin->isOwner();
            @endphp
            <tr>
                {{-- No order, tanggal, estimasi --}}
                <td>
                    <div class="no-order mono">{{ $p->nomor_pesanan }}</div>
                    <div class="tgl-kecil">{{ $p->created_at->translatedFormat('d M Y') }}</div>
                    @if ($p->estimasi_selesai)
                        <div class="estimasi"><i class="bi bi-clock"></i> Est: {{ $p->estimasi_selesai->translatedFormat('d M Y') }}</div>
                    @endif
                </td>

                {{-- Pelanggan --}}
                <td class="text-center">
                    <div class="fw-bold">{{ $p->customer->nama }}</div>
                    <div class="mono">{{ $p->customer->nomor_wa }}</div>
                </td>

                {{-- Frame dan lensa --}}
                <td>
                    <span class="tag-frame">Frame :</span>
                    <div class="mb-1">{{ $namaBarang($p->frame) ?? 'Bawaan pelanggan' }}</div>
                    <span class="tag-lensa">Lensa :</span>
                    <div>{{ $namaBarang($p->lensa) }}</div>
                </td>

                {{-- Resep --}}
                <td>
                    <div class="resep-box">
                        <div class="resep-baris">
                            <span class="sisi">R<br>(OD):</span>
                            <span class="mono">SPH {{ $resep['od']['sph'] }} | C {{ $resep['od']['cyl'] }} | A {{ $resep['od']['axis'] }}</span>
                        </div>
                        <div class="resep-baris">
                            <span class="sisi">L<br>(OS):</span>
                            <span class="mono">SPH {{ $resep['os']['sph'] }} | C {{ $resep['os']['cyl'] }} | A {{ $resep['os']['axis'] }}</span>
                        </div>
                        <div class="resep-foot">
                            <span>PD : {{ $resep['pd'] }}</span>
                            <a data-resep="{{ json_encode($resep) }}" onclick="bukaDetailResep(this)">Detail</a>
                        </div>
                    </div>
                </td>

                {{-- Status --}}
                <td class="text-center">
                    @if ($p->status === 'menunggu')
                        <span class="pill-status st-menunggu">Menunggu Pengerjaan</span>
                        <form action="{{ route('pesanan.ambil', $p) }}" method="POST"
                              onsubmit="return confirm('Ambil pesanan {{ $p->nomor_pesanan }} untuk Anda kerjakan?')">
                            @csrf
                            <button type="submit" class="btn-ambil">Ambil Pesanan</button>
                        </form>

                    @elseif ($p->status === 'dikerjakan' && $bolehSelesaikanPengerjaan)
                        <form action="{{ route('pesanan.status', $p) }}" method="POST">
                            @csrf @method('PATCH')
                            <select name="status" class="select-status st-dikerjakan" data-awal="dikerjakan" onchange="ubahStatus(this)">
                                <option value="dikerjakan" selected disabled>Sedang Dikerjakan</option>
                                <option value="siap_diambil">Siap Diambil</option>
                            </select>
                        </form>

                    @elseif ($p->status === 'siap_diambil')
                        <form action="{{ route('pesanan.status', $p) }}" method="POST">
                            @csrf @method('PATCH')
                            <select name="status" class="select-status st-siap_diambil" data-awal="siap_diambil" onchange="ubahStatus(this)">
                                <option value="siap_diambil" selected disabled>Siap Diambil</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </form>

                    @else
                        <span class="pill-status st-{{ $p->status }}">{{ $p->status_label }}</span>
                    @endif
                </td>

                {{-- Keterangan --}}
                <td class="text-center">{{ $p->keterangan ?: '-' }}</td>

                {{-- User --}}
                <td class="text-center">
                    @if ($p->petugas)
                        <div>{{ $p->petugas->nama }}</div>
                    @else
                        <div class="text-muted">Belum diambil</div>
                    @endif
                    @if ($p->pencatat)
                        <div class="tgl-kecil">Dicatat: {{ $p->pencatat->nama }}</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-5">
                    {{ ($cari !== '' || $status !== '') ? 'Tidak ada pesanan yang cocok dengan pencarian.' : 'Belum ada pesanan kacamata.' }}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $pesanan->onEachSide(1)->links('pagination::bootstrap-5') }}
</div>

{{-- ===== Modal: Buat Pesanan Baru ===== --}}
<div class="modal fade" id="modalPesanan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('pesanan.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Buat Pesanan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    @if ($errors->any())
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $pesan)
                                    <li>{{ $pesan }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pelanggan</label>
                            <select name="customer_id" id="pesanan_customer" class="form-select" required>
                                <option value="">Pilih pelanggan</option>
                                @foreach ($customer as $c)
                                    <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>
                                        {{ $c->nama }}{{ $c->nomor_wa ? ' — ' . $c->nomor_wa : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Resep (data refraksi)</label>
                            <select name="refraksi_id" id="pesanan_refraksi" class="form-select" required>
                                <option value="">Pilih pelanggan dulu</option>
                            </select>
                            <div id="info_refraksi" class="form-text text-danger d-none">
                                Pelanggan ini belum punya data refraksi. Tambahkan dulu lewat
                                <a href="{{ route('customer.index') }}">Data Pelanggan</a>.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Frame</label>
                            <select name="frame_id" class="form-select">
                                <option value="">Frame bawaan pelanggan (bukan stok toko)</option>
                                @foreach ($frame as $b)
                                    <option value="{{ $b->id }}" @selected(old('frame_id') == $b->id)>{{ $b->merk }} {{ $b->kode_model }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lensa</label>
                            <select name="lensa_id" class="form-select" required>
                                <option value="">Pilih lensa</option>
                                @foreach ($lensa as $b)
                                    <option value="{{ $b->id }}" @selected(old('lensa_id') == $b->id)>{{ $b->merk }} {{ $b->kode_model }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Estimasi selesai</label>
                            <input type="date" name="estimasi_selesai" class="form-control"
                                   min="{{ now()->toDateString() }}" value="{{ old('estimasi_selesai') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" rows="2" maxlength="255" class="form-control"
                                      placeholder="Catatan untuk pesanan ini (opsional)">{{ old('keterangan') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modal: Detail Resep ===== --}}
<div class="modal fade" id="modalResep" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Resep <small class="text-muted" id="resep_tanggal"></small></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm text-center mb-2">
                    <thead>
                        <tr><th></th><th>Sphere</th><th>Cylinder</th><th>Axis</th><th>Add</th><th>Mono PD</th></tr>
                    </thead>
                    <tbody>
                        <tr><th>OD (kanan)</th><td data-od="sph"></td><td data-od="cyl"></td><td data-od="axis"></td><td data-od="add"></td><td data-od="pd"></td></tr>
                        <tr><th>OS (kiri)</th><td data-os="sph"></td><td data-os="cyl"></td><td data-os="axis"></td><td data-os="add"></td><td data-os="pd"></td></tr>
                    </tbody>
                </table>
                <div>PD total: <strong id="resep_pd"></strong></div>
            </div>
        </div>
    </div>
</div>

{{-- Data dari Blade untuk JavaScript di bawah, dibaca lewat atribut data-* --}}
<div id="dataPesanan" class="d-none"
     data-refraksi='@json($refraksiPerCustomer)'
     data-refraksi-lama="{{ old('refraksi_id') }}"
     data-buka-modal="{{ $errors->any() ? '1' : '0' }}"></div>
@endsection

@push('scripts')
<script>
    const dataPesanan = document.getElementById('dataPesanan');
    const refraksiPerCustomer = JSON.parse(dataPesanan.dataset.refraksi);

    // ----- Dropdown resep mengikuti pelanggan yang dipilih -----
    const selCustomer = document.getElementById('pesanan_customer');
    const selRefraksi = document.getElementById('pesanan_refraksi');
    const infoRefraksi = document.getElementById('info_refraksi');

    function tambahOpsi(select, nilai, teks) {
        const o = document.createElement('option');
        o.value = nilai;
        o.textContent = teks;
        select.appendChild(o);
    }

    function isiRefraksi(pilihId) {
        selRefraksi.innerHTML = '';
        infoRefraksi.classList.add('d-none');

        if (!selCustomer.value) {
            tambahOpsi(selRefraksi, '', 'Pilih pelanggan dulu');
            return;
        }

        const daftar = refraksiPerCustomer[selCustomer.value] || [];
        if (daftar.length === 0) {
            tambahOpsi(selRefraksi, '', 'Belum ada data refraksi');
            infoRefraksi.classList.remove('d-none');
            return;
        }

        tambahOpsi(selRefraksi, '', 'Pilih resep');
        daftar.forEach(function (r) { tambahOpsi(selRefraksi, r.id, r.label); });
        if (pilihId) selRefraksi.value = pilihId;
    }

    selCustomer.addEventListener('change', function () { isiRefraksi(null); });
    isiRefraksi(dataPesanan.dataset.refraksiLama || null);

    // Buka lagi modal kalau validasi gagal
    if (dataPesanan.dataset.bukaModal === '1') {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPesanan')).show();
    }

    // ----- Detail resep -----
    function bukaDetailResep(el) {
        const r = JSON.parse(el.dataset.resep);
        const modal = document.getElementById('modalResep');

        document.getElementById('resep_tanggal').textContent = '(diperiksa ' + r.tanggal + ')';
        document.getElementById('resep_pd').textContent = r.pd;

        ['od', 'os'].forEach(function (sisi) {
            ['sph', 'cyl', 'axis', 'add', 'pd'].forEach(function (k) {
                modal.querySelector('[data-' + sisi + '="' + k + '"]').textContent = r[sisi][k];
            });
        });

        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    // ----- Ubah status lewat dropdown, dengan konfirmasi -----
    function ubahStatus(el) {
        const teks = el.options[el.selectedIndex].text;
        if (confirm('Ubah status pesanan menjadi "' + teks + '"?\nStatus tidak bisa dikembalikan.')) {
            el.form.submit();
        } else {
            el.value = el.dataset.awal;
        }
    }
</script>
@endpush