<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi</title>
    <style>
        @page { margin: 36px 40px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #111;
        }
        .judul { font-size: 17px; font-weight: bold; margin: 0; }
        .toko  { font-size: 11px; color: #555; margin: 3px 0 0; }
        .periode { font-size: 10px; color: #777; margin: 2px 0 14px; }

        table.lap { width: 100%; border-collapse: collapse; }
        table.lap td, table.lap th { padding: 7px 10px; }

        /* Kepala tabel */
        table.lap th {
            background: #fdcb2f;
            color: #111;
            font-size: 10px;
            text-align: left;
        }
        table.lap th.kanan { text-align: right; }

        /* Judul bagian */
        tr.sec td {
            background: #fff3b0;
            color: #8a6d00;
            font-weight: bold;
            font-size: 9.5px;
            text-transform: uppercase;
            padding-top: 9px;
            padding-bottom: 9px;
        }

        /* Baris rincian (selang-seling) */
        tr.item td { border-bottom: 1px solid #f0e6b8; }
        tr.alt  td { background: #fffdf3; }

        /* Total bagian */
        tr.tot td {
            font-weight: bold;
            border-top: 1.5px solid #fdcb2f;
            border-bottom: 1.5px solid #fdcb2f;
        }

        /* Laba kotor */
        tr.laba td {
            background: #fff3b0;
            font-weight: bold;
            font-size: 10.5px;
            border-top: 1.5px solid #fdcb2f;
            border-bottom: 1.5px solid #fdcb2f;
        }

        /* Laba bersih */
        tr.fin td {
            background: #fdcb2f;
            font-weight: bold;
            font-size: 12px;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .kanan { text-align: right; }
        .neg   { color: #c62828; }

        .spasi td { padding: 4px; }

        .catatan { margin-top: 16px; font-size: 8.5px; color: #555; }
        .catatan .jdl { font-weight: bold; color: #111; margin-bottom: 3px; }
        .catatan ul { margin: 0; padding-left: 14px; }
        .catatan li { margin-bottom: 2px; }

        .footer {
            margin-top: 18px;
            padding-top: 6px;
            border-top: 1px solid #fdcb2f;
            font-size: 8.5px;
            color: #777;
        }
    </style>
</head>
<body>
@php
    // Angka negatif ditulis dalam tanda kurung, seperti laporan akuntansi
    $rp = function ($n) {
        $n = round($n);
        $teks = number_format(abs($n), 0, ',', '.');
        return $n < 0 ? "(Rp {$teks})" : "Rp {$teks}";
    };
    $warna = fn ($n) => round($n) < 0 ? 'neg' : '';

    $dariTeks   = $dari->copy()->locale('id')->translatedFormat('d F Y');
    $sampaiTeks = $sampai->copy()->locale('id')->translatedFormat('d F Y');

    // Susunan baris laporan: tipe sec / item / tot / laba / fin / spasi
    $baris = [];

    $baris[] = ['sec', 'Pendapatan'];
    $baris[] = ['item', "Penjualan (nota selesai: {$r['nota_selesai']})", $r['penjualan']];
    $baris[] = ['item', "DP Hangus (nota batal: {$r['nota_batal']})", $r['dp_hangus']];
    $baris[] = ['item', 'Retur Penjualan (refund customer)', -$r['refund']];
    $baris[] = ['tot', 'Total Pendapatan Bersih', $r['pendapatan_bersih']];
    $baris[] = ['spasi'];

    $baris[] = ['sec', 'Harga Pokok Penjualan'];
    $baris[] = ['item', 'Beban Pokok Penjualan (metode FIFO)', $r['hpp']];
    $baris[] = ['tot', 'Total Harga Pokok Penjualan', $r['hpp']];
    $baris[] = ['spasi'];

    $baris[] = ['laba', 'LABA (RUGI) KOTOR', $r['laba_kotor']];
    $baris[] = ['spasi'];

    $baris[] = ['sec', 'Kerugian Operasional'];
    foreach (\App\Models\KerugianOperasional::JENIS as $kode => $label) {
        $baris[] = ['item', $label, $kerugianJenis[$kode] ?? 0];
    }
    $baris[] = ['tot', 'Total Kerugian Operasional', $r['kerugian']];
    $baris[] = ['spasi'];

    $baris[] = ['fin', 'LABA (RUGI) BERSIH', $r['laba_bersih']];

    $urutItem = 0;
@endphp

<h1 class="judul">LAPORAN LABA RUGI</h1>
<div class="toko">Solo Optik</div>
<div class="periode">Periode: {{ $dariTeks }} &ndash; {{ $sampaiTeks }}</div>

<table class="lap">
    <thead>
        <tr>
            <th>Keterangan</th>
            <th class="kanan">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($baris as $b)
            @php($tipe = $b[0])

            @if ($tipe === 'spasi')
                <tr class="spasi"><td colspan="2"></td></tr>

            @elseif ($tipe === 'sec')
                @php($urutItem = 0)
                <tr class="sec"><td colspan="2">{{ $b[1] }}</td></tr>

            @elseif ($tipe === 'item')
                @php($urutItem++)
                <tr class="item {{ $urutItem % 2 === 0 ? 'alt' : '' }}">
                    <td>{{ $b[1] }}</td>
                    <td class="kanan {{ $warna($b[2]) }}">{{ $rp($b[2]) }}</td>
                </tr>

            @else
                <tr class="{{ $tipe }}">
                    <td>{{ $b[1] }}</td>
                    <td class="kanan {{ $tipe === 'fin' ? '' : $warna($b[2]) }}">{{ $rp($b[2]) }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>

{{-- Catatan perhitungan --}}
<div class="catatan">
    <div class="jdl">Catatan Perhitungan</div>
    <ul>
        <li>Pendapatan diakui saat nota berstatus Selesai, berdasarkan tanggal nota. Nota Dibatalkan hanya dihitung sebesar DP yang sudah diterima.</li>
        @if ($berjalan['jumlah'] > 0)
            <li>
                Terdapat {{ $berjalan['jumlah'] }} nota berjalan pada periode ini (total nilai {{ $rp($berjalan['total']) }},
                sudah dibayar {{ $rp($berjalan['dibayar']) }}) yang belum dihitung sebagai pendapatan.
            </li>
        @endif
        @if ($lensaCustom > 0)
            <li>
                Terdapat {{ $lensaCustom }} baris lensa custom dengan HPP Rp 0 (modal dari supplier belum dicatat),
                sehingga laba kotor dapat tampak lebih besar dari kenyataan.
            </li>
        @endif
        <li>Pembelian dari supplier tidak dihitung sebagai biaya langsung. Modalnya masuk lewat HPP saat barang terjual (FIFO).</li>
        <li>Retur ke supplier tidak memengaruhi laporan ini.</li>
    </ul>
</div>

<div class="footer">
    Dicetak oleh {{ $dicetakOleh }} pada {{ $dicetakPada->copy()->locale('id')->translatedFormat('d F Y, H:i') }}
</div>

</body>
</html>