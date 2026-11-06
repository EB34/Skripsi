{{-- Isian pesanan pengerjaan. Nama field: pesanan[...]. Butuh variabel $refraksiPerCustomer. --}}
@php
    $kolomUkuran = ['sphere' => 'SPH', 'cylinder' => 'CYL', 'axis' => 'AXIS', 'add' => 'ADD', 'mono_pd' => 'PD'];
    $sisiUkuran  = ['od' => 'OD (R)', 'os' => 'OS (L)'];

    // Kenaikan tiap kolom: SPH, CYL, dan ADD berkelipatan 0,25; AXIS dan PD bebas
    $langkah = ['sphere' => '0.25', 'cylinder' => '0.25', 'axis' => 'any', 'add' => '0.25', 'mono_pd' => 'any'];
@endphp

@push('styles')
<style>
    .psn-ukuran { width: 100%; font-size: .68rem; margin-bottom: 8px; }
    .psn-ukuran th { font-weight: 600; text-align: center; padding: 2px; white-space: nowrap; }
    .psn-ukuran td { padding: 2px; }
    .psn-ukuran input { padding: 2px 3px; font-size: .7rem; text-align: center; min-width: 0; }
</style>
@endpush

<div class="mb-2">
    <label class="form-label mb-1" style="font-size:.75rem">Ambil ukuran dari riwayat refraksi (opsional)</label>
    <select name="pesanan[refraksi_id]" id="psn_refraksi" class="form-select form-select-sm">
        <option value="">Isi ukuran manual</option>
    </select>
    <div class="form-text" style="font-size:.68rem">Ukuran boleh diubah, misalnya diturunkan atas permintaan pelanggan.</div>
</div>

<table class="psn-ukuran">
    <thead>
        <tr>
            <th></th>
            @foreach ($kolomUkuran as $label)
                <th>{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($sisiUkuran as $sisi => $labelSisi)
            <tr>
                <th>{{ $labelSisi }}</th>
                @foreach ($kolomUkuran as $kolom => $label)
                    <td>
                        <input type="number" step="{{ $langkah[$kolom] }}" class="form-control form-control-sm"
                               name="pesanan[{{ $sisi }}_{{ $kolom }}]" id="psn_{{ $sisi }}_{{ $kolom }}"
                               value="{{ old('pesanan.' . $sisi . '_' . $kolom) }}">
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
<div class="form-text mb-2" style="font-size:.68rem">SPH, CYL, dan ADD berkelipatan 0,25 (contoh: -2.25).</div>

<div class="row g-2">
    <div class="col-4">
        <label class="form-label mb-1" style="font-size:.72rem">PD total</label>
        <input type="number" step="any" name="pesanan[pd_total]" id="psn_pd_total"
               value="{{ old('pesanan.pd_total') }}" class="form-control form-control-sm">
    </div>
    <div class="col-8">
        <label class="form-label mb-1" style="font-size:.72rem">Estimasi selesai</label>
        <input type="date" name="pesanan[estimasi_selesai]" min="{{ now()->toDateString() }}"
               value="{{ old('pesanan.estimasi_selesai') }}" class="form-control form-control-sm">
    </div>
    <div class="col-12">
        <label class="form-label mb-1" style="font-size:.72rem">Keterangan</label>
        <input type="text" name="pesanan[keterangan]" maxlength="255" value="{{ old('pesanan.keterangan') }}"
               class="form-control form-control-sm" placeholder="Catatan pengerjaan (opsional)">
    </div>
</div>

{{-- Data refraksi per pelanggan, dibaca script di bawah --}}
<div id="psn_sumber" hidden
     data-refraksi="{{ json_encode($refraksiPerCustomer ?? []) }}"
     data-lama="{{ old('pesanan.refraksi_id') }}"></div>

@push('scripts')
<script>
(function () {
    var sumber = document.getElementById('psn_sumber');
    var sel = document.getElementById('psn_refraksi');
    var peta = {};
    try { peta = JSON.parse(sumber.dataset.refraksi); } catch (e) { peta = {}; }

    var customerSekarang = null;

    function isiOpsi(teks, nilai) {
        var o = document.createElement('option');
        o.value = nilai;
        o.textContent = teks;
        sel.appendChild(o);
    }

    // Dipanggil saat pelanggan berubah: isi ulang daftar refraksi milik pelanggan itu
    window.psnGantiCustomer = function (customerId, pilihId) {
        var id = customerId ? String(customerId) : '';
        if (id === customerSekarang) { return; }
        customerSekarang = id;

        sel.innerHTML = '';
        isiOpsi('Isi ukuran manual', '');

        var daftar = id && peta[id] ? peta[id] : [];
        daftar.forEach(function (r) { isiOpsi(r.label, r.id); });
        if (pilihId) { sel.value = pilihId; }
    };

    // Memilih refraksi = mengisi kolom ukuran dengan nilainya (tetap bisa diubah)
    sel.addEventListener('change', function () {
        var daftar = customerSekarang && peta[customerSekarang] ? peta[customerSekarang] : [];
        var dipilih = daftar.find(function (r) { return String(r.id) === sel.value; });
        if (!dipilih) { return; }

        Object.keys(dipilih.nilai).forEach(function (kunci) {
            var el = document.getElementById('psn_' + kunci);
            if (el) { el.value = dipilih.nilai[kunci] === null ? '' : dipilih.nilai[kunci]; }
        });
    });

    // Awal halaman: pakai pelanggan yang sudah terpilih di POS (kalau ada)
    document.addEventListener('DOMContentLoaded', function () {
        var cust = document.getElementById('cust-id');
        if (cust) { window.psnGantiCustomer(cust.value, sumber.dataset.lama || null); }
    });
})();
</script>
@endpush