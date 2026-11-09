<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Jalan {{ $retur->nomor_surat_jalan }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #111; margin: 0; padding: 24px; }
        .kertas { max-width: 800px; margin: 0 auto; }
        .no-print { margin-bottom: 16px; display: flex; gap: 8px; }
        .no-print button, .no-print a {
            border: 0; border-radius: 6px; padding: 8px 16px; font-size: 13px; font-weight: 600;
            cursor: pointer; text-decoration: none; background: #fdcb2f; color: #000;
        }
        .no-print a { background: #d9d9d9; }
        .kop { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 14px; }
        .kop .toko { font-size: 20px; font-weight: 700; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 14px; letter-spacing: .5px; }
        .info { width: 100%; margin-bottom: 14px; }
        .info td { padding: 2px 0; vertical-align: top; }
        .info td.label { width: 170px; }
        table.barang { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.barang th, table.barang td { border: 1px solid #111; padding: 6px 8px; text-align: left; }
        table.barang th { background: #eee; }
        table.barang td.angka, table.barang th.angka { text-align: center; }
        .ttd { width: 100%; margin-top: 30px; }
        .ttd td { width: 33%; text-align: center; vertical-align: top; }
        .ttd .ruang { height: 70px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
@php
    $supplier   = $retur->pembelian?->supplier;
    $namaKontak = $supplier ? $supplier->kontak->pluck('nama')->implode(', ') : '';
@endphp
<div class="kertas">
    <div class="no-print">
        <button type="button" onclick="window.print()">Cetak</button>
        <a href="{{ route('retur.supplier') }}">Kembali</a>
    </div>

    <div class="kop">
        <div class="toko">Solo Optik</div>
    </div>

    <h1>SURAT JALAN PENGEMBALIAN BARANG</h1>

    <table class="info">
        <tr><td class="label">No. Surat Jalan</td><td>: {{ $retur->nomor_surat_jalan }}</td></tr>
        <tr><td class="label">Tanggal</td><td>: {{ $retur->tanggal_format }}</td></tr>
        <tr><td class="label">Kepada (Supplier)</td><td>: {{ $supplier?->nama_perusahaan ?? '-' }}</td></tr>
        @if ($namaKontak !== '')
            <tr><td class="label">Contact Person</td><td>: {{ $namaKontak }}</td></tr>
        @endif
        <tr><td class="label">No. Referensi Invoice</td><td>: {{ $retur->pembelian?->nomor_invoice_supplier ?: '-' }}</td></tr>
        <tr><td class="label">Penyelesaian</td><td>: {{ $retur->jenis_label }}</td></tr>
    </table>

    <table class="barang">
        <thead>
            <tr>
                <th class="angka" style="width:40px">No</th>
                <th>Merek</th>
                <th>Tipe / Kode Model</th>
                <th class="angka" style="width:70px">Jumlah</th>
                <th>Alasan Retur</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($retur->detail as $i => $d)
                <tr>
                    <td class="angka">{{ $i + 1 }}</td>
                    <td>{{ $d->barang?->merk ?? '-' }}</td>
                    <td>{{ $d->barang?->kode_model ?? '-' }}</td>
                    <td class="angka">{{ $d->jumlah }}</td>
                    <td>{{ \App\Models\ReturSupplier::ALASAN[$d->alasan] ?? $d->alasan }}</td>
                    <td>{{ $d->keterangan ?: '-' }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" style="text-align:right"><strong>Total unit</strong></td>
                <td class="angka"><strong>{{ $retur->total_unit }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <table class="ttd">
        <tr>
            <td>Dibuat oleh,<div class="ruang"></div>( Solo Optik )</td>
            <td>Pengirim,<div class="ruang"></div>( ................. )</td>
            <td>Diterima supplier,<div class="ruang"></div>( ................. )</td>
        </tr>
    </table>
</div>
</body>
</html>