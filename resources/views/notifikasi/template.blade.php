@extends('layouts.app')

@section('title', 'Edit Template Chat')

@push('styles')
<style>
    .kartu-template { background: var(--abu-kartu); border-radius: 12px; padding: 20px 22px; margin-bottom: 20px; }
    .kartu-template textarea { border: 0; border-radius: 8px; font-size: .85rem; }
    .judul-template { font-weight: 700; font-size: 1rem; margin: 0; }
    .ket-template { font-size: .75rem; color: #555; margin: 2px 0 12px; }
    .btn-penanda {
        border: 0; background: #fff; border-radius: 999px;
        padding: 3px 12px; font-size: .72rem; color: #111;
    }
    .btn-penanda:hover { background: #fff3b0; }
    .label-kecil { font-size: .72rem; font-weight: 600; color: #444; margin-bottom: 4px; }
    .pratinjau {
        background: #dcf8c6; border-radius: 10px; padding: 12px 14px;
        font-size: .82rem; white-space: pre-line; word-break: break-word; min-height: 60px;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Edit Template Chat</h1>
    <a href="{{ route('notifikasi.index') }}" class="btn-abu"><i class="bi bi-arrow-left"></i> Kembali ke Log</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $e)
            <div>{{ $e }}</div>
        @endforeach
    </div>
@endif

<div id="data-contoh"
     @foreach($contoh as $kunci => $nilai) data-{{ str_replace('_', '-', $kunci) }}="{{ $nilai }}" @endforeach
     hidden></div>

@foreach($templates as $kode => $template)
    @php
        $def = $daftar[$kode];
        $isiTampil = old('_kode') === $kode ? old('isi') : $template->isi;
    @endphp
    <div class="kartu-template" data-template="{{ $kode }}">
        <h2 class="judul-template">{{ $def['nama'] }}</h2>
        <p class="ket-template">{{ $def['keterangan'] }}</p>

        <form method="POST" action="{{ route('notifikasi.template.update', $kode) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="_kode" value="{{ $kode }}">

            <div class="label-kecil">Isi pesan</div>
            <textarea name="isi" rows="5" class="form-control isi-template" maxlength="1000" required>{{ $isiTampil }}</textarea>

            <div class="label-kecil mt-3">Penanda (klik untuk menyisipkan di posisi kursor)</div>
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach($penanda as $kunci => $arti)
                    <button type="button" class="btn-penanda" data-penanda="{{ '{' . $kunci . '}' }}" title="{{ $arti }}">{{ '{' . $kunci . '}' }}</button>
                @endforeach
            </div>

            <div class="label-kecil">Pratinjau (data contoh)</div>
            <div class="pratinjau mb-3"></div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Simpan Template</button>
            </div>
        </form>

        <form method="POST" action="{{ route('notifikasi.template.reset', $kode) }}" class="mt-2"
              onsubmit="return confirm('Kembalikan template ini ke teks bawaan?');">
            @csrf
            <button type="submit" class="btn-abu" style="padding: 5px 14px; font-size: .75rem;">
                <i class="bi bi-arrow-counterclockwise"></i> Kembalikan bawaan
            </button>
        </form>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
    (function () {
        var sumber = document.getElementById('data-contoh');
        var contoh = {
            nama: sumber.dataset.nama,
            nomor_pesanan: sumber.dataset.nomorPesanan,
            nomor_nota: sumber.dataset.nomorNota,
            jumlah_kacamata: sumber.dataset.jumlahKacamata,
            sisa_bayar: sumber.dataset.sisaBayar,
            nama_toko: sumber.dataset.namaToko
        };

        function perbarui(kartu) {
            var teks = kartu.querySelector('.isi-template').value;
            Object.keys(contoh).forEach(function (k) {
                teks = teks.split('{' + k + '}').join(contoh[k]);
            });
            kartu.querySelector('.pratinjau').textContent = teks;
        }

        document.querySelectorAll('.kartu-template').forEach(function (kartu) {
            var area = kartu.querySelector('.isi-template');
            area.addEventListener('input', function () { perbarui(kartu); });

            kartu.querySelectorAll('.btn-penanda').forEach(function (tombol) {
                tombol.addEventListener('click', function () {
                    var p = tombol.dataset.penanda;
                    var awal = area.selectionStart;
                    var akhir = area.selectionEnd;
                    area.value = area.value.slice(0, awal) + p + area.value.slice(akhir);
                    area.focus();
                    area.selectionStart = area.selectionEnd = awal + p.length;
                    perbarui(kartu);
                });
            });

            perbarui(kartu);
        });
    })();
</script>
@endpush