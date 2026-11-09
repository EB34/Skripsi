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
    .resep-kosong { color: #666; margin-bottom: 4px; }
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
    .st-batal        { background: #fddede; color: #a31515; border-color: #f0a0a0; }

    .btn-ambil, .btn-selesai {
        display: block; margin: 6px auto 0; border: 0; border-radius: 6px;
        font-size: .7rem; font-weight: 600; padding: 3px 12px;
    }
    .btn-ambil { background: var(--kuning); color: #000; }
    .btn-ambil:hover { background: #f0bb15; }
    .btn-selesai { background: #1b7a1b; color: #fff; }
    .btn-selesai:hover { background: #166516; }
    .btn-batal {
        display: block; margin: 6px auto 0; border: 0; background: none; padding: 0;
        color: #a31515; font-size: .68rem; text-decoration: underline;
    }
    .teks-hangus { color: #a31515; }
    .ringkas-nota { background: #efefef; border-radius: 8px; padding: 10px 12px; font-size: .78rem; }

    /* Popup yang isinya dibungkus <form>: form ikut menjadi kolom flex supaya
       header dan footer tetap terlihat dan hanya isi (modal-body) yang digulir */
    .modal-dialog-scrollable .modal-content > form {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        max-height: 100%;
        overflow: hidden;
    }
    .modal-dialog-scrollable .modal-content > form > .modal-body {
        min-height: 0;
        overflow-y: auto;
    }
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
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $namaBarang = fn ($b) => $b ? trim(($b->merk ?? '') . ' ' . ($b->kode_model ?? '')) : null;

    // Nama lensa dari baris nota (ditambah keterangan ukuran kalau ada, mis. lensa custom)
    $namaLensaMata = fn ($d) => $d
        ? trim(($d->barang->merk ?? '') . ' ' . ($d->barang->kode_model ?? '') . ($d->keterangan ? ' | ' . $d->keterangan : ''))
        : null;

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
        <input type="text" name="q" value="{{ $cari }}" placeholder="Cari no.order, nama pelanggan, no.Wa, no.nota">
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
                $nota    = $p->penjualan;
                $dibayar = $nota->total_dibayar;
                $sisa    = $nota->sisa_bayar;

                // Ukuran milik pesanan sendiri (boleh berbeda dari refraksi)
                $ada = [];
                foreach (['od', 'os'] as $s) {
                    $ada[$s] = $p->{$s . '_sphere'} !== null || $p->{$s . '_cylinder'} !== null || $p->{$s . '_axis'} !== null;
                }
                $punyaUkuran = $ada['od'] || $ada['os'];

                // Lensa per mata (hanya ada kalau pesanan dibuat dengan mode "dari nota")
                $lensaPerMata = [];
                if ($p->lensaOd) { $lensaPerMata[] = 'OD: ' . $namaLensaMata($p->lensaOd); }
                if ($p->lensaOs) { $lensaPerMata[] = 'OS: ' . $namaLensaMata($p->lensaOs); }

                if (! $p->lensa_id) {
                    $teksLensa = 'Lensa lama milik pelanggan';
                } else {
                    $teksLensa = $lensaPerMata ? implode(' · ', $lensaPerMata) : null;
                }

                $resep = [
                    'sumber' => $p->refraksi
                        ? 'acuan refraksi ' . $p->refraksi->tanggal_periksa->format('d/m/Y')
                        : 'diinput manual',
                    'lensa'  => $teksLensa,
                    'pd'     => $num($p->pd_total),
                    'od'     => ['sph' => $fmt($p->od_sphere), 'cyl' => $fmt($p->od_cylinder), 'axis' => $p->od_axis ?? '-', 'add' => $num($p->od_add), 'pd' => $num($p->od_mono_pd)],
                    'os'     => ['sph' => $fmt($p->os_sphere), 'cyl' => $fmt($p->os_cylinder), 'axis' => $p->os_axis ?? '-', 'add' => $num($p->os_add), 'pd' => $num($p->os_mono_pd)],
                ];

                $bolehKerja = (int) $p->petugas_id === (int) $userLogin->id || $userLogin->isOwner();
                $bisaBatal  = in_array($p->status, ['menunggu', 'dikerjakan', 'siap_diambil'], true) && $sisa > 0;
            @endphp
            <tr>
                {{-- No order, tanggal, estimasi --}}
                <td>
                    <div class="no-order mono">{{ $p->nomor_pesanan }}</div>
                    <div class="tgl-kecil">{{ $p->created_at->translatedFormat('d M Y') }}</div>
                    @if ($p->estimasi_selesai && ! in_array($p->status, ['selesai', 'batal'], true))
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
                    <div>
                        @if ($p->lensa && $lensaPerMata)
                            @foreach ([['OD', $p->lensaOd], ['OS', $p->lensaOs]] as [$mata, $det])
                                @if ($det)
                                    <div>{{ $mata }}: {{ $namaLensaMata($det) }}</div>
                                @endif
                            @endforeach
                        @elseif ($p->lensa)
                            {{ $namaBarang($p->lensa) }} <span class="tgl-kecil">x {{ $p->jumlah_lensa }} biji</span>
                        @else
                            Lensa lama pelanggan
                        @endif
                    </div>
                </td>

                {{-- Resep --}}
                <td>
                    <div class="resep-box">
                        @if ($punyaUkuran)
                            @foreach (['od' => ['R', '(OD):'], 'os' => ['L', '(OS):']] as $sisi => [$huruf, $kode])
                                @if ($ada[$sisi])
                                    <div class="resep-baris">
                                        <span class="sisi">{{ $huruf }}<br>{{ $kode }}</span>
                                        <span class="mono">SPH {{ $resep[$sisi]['sph'] }} | C {{ $resep[$sisi]['cyl'] }} | A {{ $resep[$sisi]['axis'] }}</span>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div class="resep-kosong">{{ $p->lensa_id ? 'Ukuran belum diisi' : 'Lensa lama, tanpa ukuran' }}</div>
                        @endif
                        <div class="resep-foot">
                            <span>PD : {{ $resep['pd'] }}</span>
                            <a data-resep="{{ json_encode($resep) }}" onclick="bukaDetailResep(this)">Detail</a>
                        </div>
                    </div>
                </td>

                {{-- Status dan aksi --}}
                <td class="text-center">
                    @if ($p->status === 'menunggu')
                        <span class="pill-status st-menunggu">Menunggu Pengerjaan</span>
                        <form action="{{ route('pesanan.ambil', $p) }}" method="POST"
                              onsubmit="return confirm('Ambil pesanan {{ $p->nomor_pesanan }} untuk Anda kerjakan?')">
                            @csrf
                            <button type="submit" class="btn-ambil">Ambil Pesanan</button>
                        </form>

                    @elseif ($p->status === 'dikerjakan' && $bolehKerja)
                        <form action="{{ route('pesanan.status', $p) }}" method="POST">
                            @csrf @method('PATCH')
                            <select name="status" class="select-status st-dikerjakan" data-awal="dikerjakan" onchange="ubahStatus(this)">
                                <option value="dikerjakan" selected disabled>Sedang Dikerjakan</option>
                                <option value="siap_diambil">Siap Diambil</option>
                            </select>
                        </form>

                    @elseif ($p->status === 'siap_diambil')
                        <span class="pill-status st-siap_diambil">Siap Diambil</span>
                        <button type="button" class="btn-selesai" data-bs-toggle="modal" data-bs-target="#modalSelesai"
                                data-action="{{ route('pesanan.selesai', $p) }}"
                                data-nomor="{{ $p->nomor_pesanan }}"
                                data-total="{{ (float) $nota->total }}"
                                data-dibayar="{{ $dibayar }}"
                                data-sisa="{{ $sisa }}">
                            Selesai (diambil)
                        </button>

                    @else
                        <span class="pill-status st-{{ $p->status }}">{{ $p->status_label }}</span>
                        @if ($p->status === 'batal' && $p->alasan_batal)
                            <div class="tgl-kecil mt-1">{{ $p->alasan_batal }}</div>
                        @endif
                    @endif

                    @if ($bisaBatal)
                        <button type="button" class="btn-batal" data-bs-toggle="modal" data-bs-target="#modalBatal"
                                data-action="{{ route('pesanan.batal', $p) }}"
                                data-nomor="{{ $p->nomor_pesanan }}"
                                data-dibayar="{{ $dibayar }}"
                                data-frame="{{ $p->frame_id ? $namaBarang($p->frame) : '' }}">
                            Batalkan
                        </button>
                    @endif
                </td>

                {{-- Keterangan dan pembayaran --}}
                <td class="text-center">
                    <div>{{ $p->keterangan ?: '-' }}</div>
                    <div class="tgl-kecil mt-1">
                        <a href="{{ route('penjualan.show', $nota) }}">{{ $nota->nomor_nota }}</a>
                        @if ($p->status === 'batal')
                            <div class="teks-hangus">DP hangus {{ $rp($dibayar) }}</div>
                            @if ($p->frame_dikembalikan)
                                <div>Frame dikembalikan ke stok</div>
                            @endif
                        @elseif ($sisa > 0)
                            <div class="teks-hangus">DP {{ $rp($dibayar) }}, sisa {{ $rp($sisa) }}</div>
                        @else
                            <div>Lunas</div>
                        @endif
                    </div>
                </td>

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
                    @if ($p->status === 'batal' && $p->pembatal)
                        <div class="tgl-kecil">Dibatalkan: {{ $p->pembatal->nama }}</div>
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

{{-- ===== Modal: Buat Pesanan Baru (dari nota yang belum selesai) ===== --}}
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

                    @if ($notaTersedia->isEmpty())
                        <div class="alert alert-secondary mb-0">
                            Tidak ada nota yang menunggu pesanan. Pesanan dibuat dari nota yang belum selesai dan berisi frame atau lensa.
                            Buat transaksinya dulu di <a href="{{ route('penjualan.create') }}">Transaksi Penjualan</a>.
                        </div>
                    @else
                        <label class="form-label">Pilih nota</label>
                        <select name="penjualan_id" id="pesanan_nota" class="form-select mb-3" required>
                            <option value="">Pilih nota yang belum selesai</option>
                            @foreach ($notaTersedia as $n)
                                @php
                                    $detailFrame = $n->detail->first(fn ($d) => strtolower((string) $d->barang->jenis) === 'frame');
                                    $detailLensa = $n->detail->filter(fn ($d) => strtolower((string) $d->barang->jenis) === 'lensa');
                                    // Ringkasan semua baris lensa, mis. "Lensa A LN-001 x 1, Lensa B LN-002 x 1"
                                    $ringkasLensa = $detailLensa
                                        ->map(fn ($d) => $namaBarang($d->barang) . ' x ' . (int) $d->jumlah)
                                        ->implode(', ');
                                @endphp
                                <option value="{{ $n->id }}"
                                        data-customer="{{ $n->customer_id }}"
                                        data-pelanggan="{{ $n->customer->nama }}"
                                        data-frame="{{ $detailFrame ? $namaBarang($detailFrame->barang) : '' }}"
                                        data-lensa="{{ $ringkasLensa }}"
                                        data-jumlah="{{ (int) $detailLensa->sum('jumlah') }}"
                                        data-unit="{{ json_encode($unitPerNota[$n->id] ?? []) }}"
                                        @selected((string) old('penjualan_id') === (string) $n->id)>
                                    {{ $n->nomor_nota }} | {{ $n->customer->nama }} ({{ $n->tanggal->format('d/m/Y') }})
                                </option>
                            @endforeach
                        </select>

                        <div id="ringkas_nota" class="ringkas-nota mb-3 d-none"></div>

                        <div id="wrap_form_pesanan" class="d-none">
                            @include('pesanan.form_pesanan', ['refraksiPerCustomer' => $refraksiPerCustomer])
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    @if ($notaTersedia->isNotEmpty())
                        <button type="submit" class="btn btn-primary">Simpan Pesanan</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modal: Selesai (pelunasan saat barang diambil) ===== --}}
<div class="modal fade" id="modalSelesai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="form_selesai">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Selesaikan <span id="selesai_nomor"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="ringkas-nota mb-3">
                        <div class="d-flex justify-content-between"><span>Total nota</span><span id="selesai_total"></span></div>
                        <div class="d-flex justify-content-between"><span>Sudah dibayar</span><span id="selesai_dibayar"></span></div>
                        <div class="d-flex justify-content-between fw-bold"><span>Sisa dilunasi sekarang</span><span id="selesai_sisa"></span></div>
                    </div>

                    <div id="selesai_lunas" class="alert alert-success py-2 d-none mb-0">
                        Nota sudah lunas. Pesanan akan langsung diselesaikan.
                    </div>

                    <div id="selesai_blok_bayar">
                        <label class="form-label">Metode pembayaran</label>
                        <select name="metode_pembayaran" id="selesai_metode" class="form-select mb-3">
                            <option value="tunai">Uang Tunai (Cash)</option>
                            <option value="qris">QRIS Bank</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="kartu">Kartu Debit/Kredit</option>
                        </select>

                        <div id="selesai_box_uang">
                            <label class="form-label">Cash diterima</label>
                            <input type="number" name="uang_diterima" id="selesai_uang" min="0" step="any" class="form-control mb-2" placeholder="Contoh: 500000">
                            <div class="d-flex justify-content-between">
                                <span>Kembalian</span><strong id="selesai_kembalian">Rp 0</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn_selesai">Terima Pelunasan &amp; Selesai</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modal: Batalkan pesanan (DP hangus) ===== --}}
<div class="modal fade" id="modalBatal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="form_batal">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Batalkan <span id="batal_nomor"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        DP <strong id="batal_dibayar"></strong> yang sudah dibayar <strong>tidak dikembalikan (hangus)</strong>.
                        Lensa tidak dikembalikan ke stok. Tindakan ini tidak bisa dibatalkan.
                    </div>

                    <label class="form-label">Alasan pembatalan <span class="text-danger">*</span></label>
                    <textarea name="alasan_batal" id="batal_alasan" rows="2" maxlength="255" class="form-control mb-3"
                              placeholder="Contoh: pelanggan tidak jadi mengambil" required></textarea>

                    <div id="batal_blok_frame" class="form-check d-none">
                        <input class="form-check-input" type="checkbox" name="kembalikan_frame" value="1" id="batal_frame">
                        <label class="form-check-label" for="batal_frame">
                            Kembalikan frame <strong id="batal_nama_frame"></strong> ke stok
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kembali</button>
                    <button type="submit" class="btn btn-danger" id="btn_batal">Batalkan Pesanan</button>
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
                <h5 class="modal-title">Detail Resep <small class="text-muted" id="resep_sumber"></small></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="resep_lensa" class="alert alert-secondary py-2 d-none"></div>
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
<div id="dataPesanan" class="d-none" data-buka-modal="{{ $errors->any() ? '1' : '0' }}"></div>
@endsection

@push('scripts')
<script>
    const dataPesanan = document.getElementById('dataPesanan');

    function rp(angka) {
        return 'Rp ' + Math.round(angka).toLocaleString('id-ID');
    }

    // ----- Buat Pesanan Baru: pilih nota -----
    const selNota = document.getElementById('pesanan_nota');

    function tampilNota() {
        if (!selNota) return;

        const ringkas = document.getElementById('ringkas_nota');
        const wrap    = document.getElementById('wrap_form_pesanan');

        if (!selNota.value) {
            ringkas.classList.add('d-none');
            wrap.classList.add('d-none');
            return;
        }

        const opt    = selNota.options[selNota.selectedIndex];
        const jumlah = parseInt(opt.dataset.jumlah) || 0;

        ringkas.innerHTML = '';
        const baris = [
            'Pelanggan: ' + opt.dataset.pelanggan,
            'Frame: ' + (opt.dataset.frame || 'bawaan pelanggan'),
            'Lensa: ' + (opt.dataset.lensa ? opt.dataset.lensa + ' (total ' + jumlah + ' biji)' : 'lensa lama pelanggan'),
            opt.dataset.lensa
                ? 'Isi ukuran sesuai jumlah lensa: 1 biji = satu sisi mata, 2 biji = OD dan OS. Pilih "Ambil dari nota pembelian" untuk menentukan lensa tiap mata.'
                : 'Lensa lama: ukuran boleh dikosongkan.'
        ];
        baris.forEach(function (t) {
            const d = document.createElement('div');
            d.textContent = t;
            ringkas.appendChild(d);
        });

        ringkas.classList.remove('d-none');
        wrap.classList.remove('d-none');

        // Daftar refraksi milik pelanggan di nota (dari partial form pesanan)
        const sumber = document.getElementById('psn_sumber');
        const lama = sumber ? (sumber.dataset.lama || null) : null;
        if (typeof window.psnGantiCustomer === 'function') {
            window.psnGantiCustomer(opt.dataset.customer, lama);
        }

        // Unit lensa (per biji) pada nota ini, untuk pilihan lensa mata kanan/kiri
        let unit = [];
        try { unit = JSON.parse(opt.dataset.unit || '[]'); } catch (e) { unit = []; }
        if (typeof window.psnGantiNota === 'function') {
            window.psnGantiNota(unit);
        }
    }

    if (selNota) {
        selNota.addEventListener('change', tampilNota);
        // Tunggu semua script selesai dimuat (script form pesanan dimuat setelah script ini)
        document.addEventListener('DOMContentLoaded', tampilNota);
    }

    // Buka lagi modal kalau validasi gagal
    if (dataPesanan.dataset.bukaModal === '1') {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPesanan')).show();
    }

    // ----- Modal Selesai (pelunasan) -----
    const modalSelesai = document.getElementById('modalSelesai');
    const formSelesai  = document.getElementById('form_selesai');
    const selMetode    = document.getElementById('selesai_metode');
    const inUang       = document.getElementById('selesai_uang');
    const btnSelesai   = document.getElementById('btn_selesai');
    let sisaSelesai = 0;

    function hitungSelesai() {
        const tunai = selMetode.value === 'tunai';
        const uang  = parseFloat(inUang.value) || 0;

        document.getElementById('selesai_box_uang').classList.toggle('d-none', !tunai);
        document.getElementById('selesai_kembalian').textContent = rp(Math.max(uang - sisaSelesai, 0));
        btnSelesai.disabled = sisaSelesai > 0 && tunai && uang < sisaSelesai;
    }

    modalSelesai.addEventListener('show.bs.modal', function (e) {
        const b = e.relatedTarget;
        sisaSelesai = parseFloat(b.dataset.sisa) || 0;

        formSelesai.action = b.dataset.action;
        document.getElementById('selesai_nomor').textContent   = b.dataset.nomor;
        document.getElementById('selesai_total').textContent   = rp(parseFloat(b.dataset.total) || 0);
        document.getElementById('selesai_dibayar').textContent = rp(parseFloat(b.dataset.dibayar) || 0);
        document.getElementById('selesai_sisa').textContent    = rp(sisaSelesai);

        const lunas = sisaSelesai <= 0;
        document.getElementById('selesai_lunas').classList.toggle('d-none', !lunas);
        document.getElementById('selesai_blok_bayar').classList.toggle('d-none', lunas);
        selMetode.disabled = lunas;
        inUang.disabled = lunas;

        selMetode.value = 'tunai';
        inUang.value = '';
        btnSelesai.textContent = lunas ? 'Selesaikan' : 'Terima Pelunasan & Selesai';
        hitungSelesai();
    });

    selMetode.addEventListener('change', hitungSelesai);
    inUang.addEventListener('input', hitungSelesai);
    formSelesai.addEventListener('submit', function () { btnSelesai.disabled = true; });

    // ----- Modal Batalkan -----
    const modalBatal = document.getElementById('modalBatal');
    const formBatal  = document.getElementById('form_batal');

    modalBatal.addEventListener('show.bs.modal', function (e) {
        const b = e.relatedTarget;

        formBatal.action = b.dataset.action;
        document.getElementById('batal_nomor').textContent   = b.dataset.nomor;
        document.getElementById('batal_dibayar').textContent = rp(parseFloat(b.dataset.dibayar) || 0);
        document.getElementById('batal_alasan').value = '';

        const frame = b.dataset.frame || '';
        document.getElementById('batal_blok_frame').classList.toggle('d-none', frame === '');
        document.getElementById('batal_nama_frame').textContent = frame;
        document.getElementById('batal_frame').checked = false;
    });

    formBatal.addEventListener('submit', function () {
        document.getElementById('btn_batal').disabled = true;
    });

    // ----- Detail resep -----
    function bukaDetailResep(el) {
        const r = JSON.parse(el.dataset.resep);
        const modal = document.getElementById('modalResep');

        document.getElementById('resep_sumber').textContent = '(' + r.sumber + ')';
        document.getElementById('resep_pd').textContent = r.pd;

        const info = document.getElementById('resep_lensa');
        info.textContent = r.lensa || '';
        info.classList.toggle('d-none', !r.lensa);

        ['od', 'os'].forEach(function (sisi) {
            ['sph', 'cyl', 'axis', 'add', 'pd'].forEach(function (k) {
                modal.querySelector('[data-' + sisi + '="' + k + '"]').textContent = r[sisi][k];
            });
        });

        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    // ----- Ubah status lewat dropdown (Dikerjakan -> Siap Diambil), dengan konfirmasi -----
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