@extends('layouts.app')

@section('title', 'Laporan Laba Rugi')

@push('styles')
<style>
    /* ---------- Laporan Laba Rugi ---------- */
    .lr-crumb { font-size: .78rem; color: #777; margin-bottom: 14px; }
    .lr-crumb strong { color: #111; font-weight: 600; }

    .lr-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, .14);
        padding: 24px;
        margin-bottom: 22px;
    }
    .lr-title { font-size: 1.15rem; font-weight: 700; margin: 0; }
    .lr-title small { font-size: .8rem; font-weight: 400; color: #666; }
    .lr-sub { font-size: .85rem; font-weight: 600; color: #555; margin-top: 6px; }
    .lr-head { padding-bottom: 14px; border-bottom: 1px solid #e3e3e3; margin-bottom: 16px; }

    /* Pemilih periode */
    .lr-filter { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding-bottom: 18px; border-bottom: 1px solid #e3e3e3; margin-bottom: 20px; }
    .lr-range {
        display: inline-flex; align-items: center; gap: 8px;
        border: 1px solid #c9c9c9; border-radius: 6px;
        padding: 4px 12px; background: #fff;
    }
    .lr-range i { color: #555; }
    .lr-range input { border: 0; outline: none; font-size: .82rem; background: transparent; }

    /* Kartu ringkasan */
    .lr-sum {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 8px rgba(0, 0, 0, .16);
        padding: 16px 24px 14px;
        border-bottom: 5px solid #bdbdbd;
        height: 100%;
    }
    .lr-sum .lbl { font-size: .85rem; font-weight: 500; color: #444; }
    .lr-sum .val { font-size: 1.25rem; font-weight: 700; margin-top: 4px; }
    .lr-sum.kuning  { border-bottom-color: var(--kuning); }
    .lr-sum.emas    { border-bottom-color: #d9a400; }

    /* Tabel gaya laporan */
    .lr-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .lr-table thead th {
        background: var(--abu-kartu);
        font-size: .7rem; font-weight: 600; letter-spacing: .03em;
        text-transform: uppercase; color: #555;
        padding: 14px 8px;
    }
    .lr-table td { padding: 12px 8px; border-bottom: 1px solid #e6e6e6; }
    .lr-table .sec td { font-weight: 700; padding-left: 16px; }
    .lr-table .item td:first-child { padding-left: 32px; }
    .lr-table .tot td { font-weight: 700; padding-left: 16px; }
    .lr-table .sub td { background: var(--abu-kartu); font-weight: 700; padding: 14px 8px 14px 16px; border-bottom: 0; }
    .lr-table .fin td { background: var(--kuning); font-weight: 800; padding: 16px 8px 16px 16px; border-bottom: 0; }
    .lr-table .num { text-align: right; padding-right: 12px; white-space: nowrap; }
    .neg { color: #c62828; }

    /* Tabel rincian per hari */
    .lr-harian { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .lr-harian thead th {
        background: var(--abu-kartu);
        font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em;
        color: #555; padding: 12px 10px; white-space: nowrap;
    }
    .lr-harian td { padding: 10px; border-bottom: 1px solid #e6e6e6; }
    .lr-harian tfoot td { background: var(--abu-kartu); font-weight: 700; border-bottom: 0; }

    /* Kotak catatan */
    .lr-note {
        background: #fff8d6;
        border-left: 4px solid var(--kuning);
        border-radius: 6px;
        padding: 14px 18px;
        font-size: .8rem;
    }
    .lr-note ul { margin: 6px 0 0; padding-left: 18px; }
    .lr-note li { margin-bottom: 4px; }
</style>
@endpush

@section('content')
@php
    // Angka negatif ditulis dalam tanda kurung, seperti laporan akuntansi
    $rp = function ($n) {
        $n = round($n);
        $teks = number_format(abs($n), 0, ',', '.');
        return $n < 0 ? "(Rp {$teks})" : "Rp {$teks}";
    };
    $warna = fn ($n) => round($n) < 0 ? 'neg' : '';

    // Pintasan periode
    $pintasan = [
        'Bulan Ini'  => [now()->startOfMonth()->toDateString(), now()->toDateString()],
        'Bulan Lalu' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        'Tahun Ini'  => [now()->startOfYear()->toDateString(), now()->toDateString()],
    ];

    $dariStr   = $dari->toDateString();
    $sampaiStr = $sampai->toDateString();
@endphp

<div class="lr-crumb">Laporan / <strong>Laporan Laba Rugi</strong></div>

@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $pesan)
            <div>{{ $pesan }}</div>
        @endforeach
    </div>
@endif

{{-- ===== Kartu utama: judul, periode, ringkasan, tabel ===== --}}
<div class="lr-card">

    <div class="lr-head d-flex justify-content-between align-items-start">
        <div>
            <h1 class="lr-title">Laporan Laba Rugi <small>(dalam rupiah)</small></h1>
            <div class="lr-sub">Solo Optik</div>
        </div>
        <a href="{{ route('laporan.labarugi.pdf', ['dari' => $dariStr, 'sampai' => $sampaiStr]) }}"
           class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-pdf"></i> Ekspor PDF
        </a>
    </div>

    {{-- Filter periode --}}
    <form method="GET" action="{{ route('laporan.labarugi') }}" class="lr-filter">
        <div class="lr-range">
            <i class="bi bi-calendar3"></i>
            <input type="date" name="dari" value="{{ $dariStr }}">
            <span>-</span>
            <input type="date" name="sampai" value="{{ $sampaiStr }}">
        </div>
        <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Tampilkan</button>

        <div class="d-flex gap-2 ms-md-auto">
            @foreach ($pintasan as $label => [$a, $b])
                <a class="chip {{ $dariStr === $a && $sampaiStr === $b ? 'active' : '' }}"
                   style="border: 1px solid #d0d0d0;"
                   href="{{ route('laporan.labarugi', ['dari' => $a, 'sampai' => $b]) }}">{{ $label }}</a>
            @endforeach
        </div>
    </form>

    {{-- Tiga kartu ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="lr-sum">
                <div class="lbl">Total Pendapatan Bersih</div>
                <div class="val {{ $warna($r['pendapatan_bersih']) }}">{{ $rp($r['pendapatan_bersih']) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="lr-sum kuning">
                <div class="lbl">Total Laba Kotor</div>
                <div class="val {{ $warna($r['laba_kotor']) }}">{{ $rp($r['laba_kotor']) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="lr-sum emas">
                <div class="lbl">Total Laba Bersih</div>
                <div class="val {{ $warna($r['laba_bersih']) }}">{{ $rp($r['laba_bersih']) }}</div>
            </div>
        </div>
    </div>

    <div class="small text-muted mb-2">
        Periode {{ $dari->translatedFormat('d M Y') }} - {{ $sampai->translatedFormat('d M Y') }}
    </div>

    {{-- Tabel Deskripsi / Saldo --}}
    <table class="lr-table">
        <thead>
            <tr>
                <th style="text-align: left; padding-left: 16px;">Deskripsi</th>
                <th style="text-align: right; padding-right: 12px;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            {{-- Pendapatan --}}
            <tr class="sec"><td colspan="2">Pendapatan</td></tr>
            <tr class="item">
                <td>Penjualan (nota selesai: {{ $r['nota_selesai'] }})</td>
                <td class="num">{{ $rp($r['penjualan']) }}</td>
            </tr>
            <tr class="item">
                <td>DP Hangus (nota batal: {{ $r['nota_batal'] }})</td>
                <td class="num">{{ $rp($r['dp_hangus']) }}</td>
            </tr>
            <tr class="item">
                <td>Retur Penjualan (refund customer)</td>
                <td class="num neg">{{ $rp(-$r['refund']) }}</td>
            </tr>
            <tr class="tot">
                <td>Total Pendapatan Bersih</td>
                <td class="num {{ $warna($r['pendapatan_bersih']) }}">{{ $rp($r['pendapatan_bersih']) }}</td>
            </tr>

            {{-- HPP --}}
            <tr class="sec"><td colspan="2">Harga Pokok Penjualan</td></tr>
            <tr class="item">
                <td>Beban Pokok Penjualan (metode FIFO)</td>
                <td class="num">{{ $rp($r['hpp']) }}</td>
            </tr>
            <tr class="tot">
                <td>Total Harga Pokok Penjualan</td>
                <td class="num">{{ $rp($r['hpp']) }}</td>
            </tr>

            {{-- Laba kotor --}}
            <tr class="sub">
                <td>Laba (Rugi) Kotor</td>
                <td class="num {{ $warna($r['laba_kotor']) }}">{{ $rp($r['laba_kotor']) }}</td>
            </tr>

            {{-- Kerugian operasional --}}
            <tr class="sec"><td colspan="2">Kerugian Operasional</td></tr>
            @foreach (\App\Models\KerugianOperasional::JENIS as $kode => $label)
                <tr class="item">
                    <td>{{ $label }}</td>
                    <td class="num">{{ $rp($kerugianJenis[$kode] ?? 0) }}</td>
                </tr>
            @endforeach
            <tr class="tot">
                <td>Total Kerugian Operasional</td>
                <td class="num">{{ $rp($r['kerugian']) }}</td>
            </tr>

            {{-- Laba bersih --}}
            <tr class="fin">
                <td>Laba (Rugi) Bersih</td>
                <td class="num {{ $warna($r['laba_bersih']) }}">{{ $rp($r['laba_bersih']) }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ===== Rincian per hari ===== --}}
<div class="lr-card">
    <h2 class="lr-title mb-3" style="font-size: 1rem;">Rincian per Hari</h2>
    <div class="table-responsive">
        <table class="lr-harian">
            <thead>
                <tr>
                    <th style="text-align: left;">Tanggal</th>
                    <th style="text-align: center;">Nota</th>
                    <th style="text-align: right;">Pendapatan</th>
                    <th style="text-align: right;">Refund</th>
                    <th style="text-align: right;">HPP</th>
                    <th style="text-align: right;">Laba Kotor</th>
                    <th style="text-align: right;">Kerugian</th>
                    <th style="text-align: right;">Laba Bersih</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($harian as $tgl => $h)
                    @php
                        $kotor  = $h['pendapatan'] - $h['refund'] - $h['hpp'];
                        $bersih = $kotor - $h['kerugian'];
                    @endphp
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($tgl)->translatedFormat('d M Y') }}</td>
                        <td style="text-align: center;">{{ $h['nota'] }}</td>
                        <td style="text-align: right;">{{ $rp($h['pendapatan']) }}</td>
                        <td style="text-align: right;">{{ $rp($h['refund']) }}</td>
                        <td style="text-align: right;">{{ $rp($h['hpp']) }}</td>
                        <td style="text-align: right;" class="{{ $warna($kotor) }}">{{ $rp($kotor) }}</td>
                        <td style="text-align: right;">{{ $rp($h['kerugian']) }}</td>
                        <td style="text-align: right; font-weight: 600;" class="{{ $warna($bersih) }}">{{ $rp($bersih) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada transaksi pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($harian))
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td style="text-align: center;">{{ $r['nota_selesai'] + $r['nota_batal'] }}</td>
                        <td style="text-align: right;">{{ $rp($r['penjualan'] + $r['dp_hangus']) }}</td>
                        <td style="text-align: right;">{{ $rp($r['refund']) }}</td>
                        <td style="text-align: right;">{{ $rp($r['hpp']) }}</td>
                        <td style="text-align: right;" class="{{ $warna($r['laba_kotor']) }}">{{ $rp($r['laba_kotor']) }}</td>
                        <td style="text-align: right;">{{ $rp($r['kerugian']) }}</td>
                        <td style="text-align: right;" class="{{ $warna($r['laba_bersih']) }}">{{ $rp($r['laba_bersih']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ===== Catatan perhitungan ===== --}}
<div class="lr-note">
    <div class="fw-bold"><i class="bi bi-info-circle"></i> Catatan Perhitungan</div>
    <ul>
        <li>Pendapatan diakui saat nota berstatus <strong>Selesai</strong>, berdasarkan tanggal nota. Nota <strong>Dibatalkan</strong> hanya dihitung sebesar DP yang sudah diterima.</li>
        @if ($berjalan['jumlah'] > 0)
            <li>
                Ada <strong>{{ $berjalan['jumlah'] }} nota berjalan</strong> pada periode ini (total nilai {{ $rp($berjalan['total']) }},
                sudah dibayar {{ $rp($berjalan['dibayar']) }}). Belum dihitung sebagai pendapatan sampai pesanannya selesai.
            </li>
        @endif
        @if ($lensaCustom > 0)
            <li class="neg">
                Ada <strong>{{ $lensaCustom }} baris lensa custom</strong> pada periode ini dengan HPP Rp 0 (modal dari supplier belum dicatat),
                sehingga laba kotor tampak lebih besar dari kenyataan.
            </li>
        @endif
        <li>Pembelian dari supplier tidak dihitung sebagai biaya langsung. Modalnya masuk lewat HPP saat barang terjual (FIFO).</li>
        <li>Retur ke supplier tidak memengaruhi laporan ini.</li>
    </ul>
</div>
@endsection