@php
    $kolomUkuran = ['sphere' => 'SPH', 'cylinder' => 'CYL', 'axis' => 'AXIS', 'add' => 'ADD', 'mono_pd' => 'PD'];
    $sisiUkuran  = ['od' => 'OD (R)', 'os' => 'OS (L)'];

    // Kenaikan tiap kolom: SPH, CYL, dan ADD berkelipatan 0,25; AXIS dan PD bebas
    $langkah = ['sphere' => '0.25', 'cylinder' => '0.25', 'axis' => 'any', 'add' => '0.25', 'mono_pd' => 'any'];

    // Mode awal saat halaman dimuat ulang karena validasi gagal
    $modeAwal = (old('pesanan.unit_od') || old('pesanan.unit_os'))
        ? 'nota'
        : (old('pesanan.refraksi_id') ? 'refraksi' : 'manual');
@endphp

@push('styles')
<style>
    .psn-ukuran { width: 100%; font-size: .68rem; margin-bottom: 8px; }
    .psn-ukuran th { font-weight: 600; text-align: center; padding: 2px; white-space: nowrap; }
    .psn-ukuran td { padding: 2px; }
    .psn-ukuran input { padding: 2px 3px; font-size: .7rem; text-align: center; min-width: 0; }
    .psn-ukuran input[readonly] { background: #e9ecef; }
    .psn-lensa-mata { background: #eef5fb; border: 1px solid #b9d3ea; border-radius: 8px; padding: 8px 10px; }
</style>
@endpush

<div class="mb-2">
    <label class="form-label mb-1" style="font-size:.75rem">Sumber ukuran</label>
    <select id="psn_mode" class="form-select form-select-sm">
        <option value="manual">Isi ukuran manual</option>
        <option value="refraksi">Ambil dari riwayat refraksi</option>
        <option value="nota">Ambil dari nota pembelian</option>
    </select>
</div>

{{-- Mode refraksi --}}
<div class="mb-2 d-none" id="psn_blok_refraksi">
    <label class="form-label mb-1" style="font-size:.75rem">Riwayat refraksi pelanggan</label>
    <select name="pesanan[refraksi_id]" id="psn_refraksi" class="form-select form-select-sm" disabled>
        <option value="">Pilih riwayat refraksi</option>
    </select>
    <div class="form-text" style="font-size:.68rem">Ukuran boleh diubah, misalnya diturunkan atas permintaan pelanggan.</div>
</div>

{{-- Mode nota: lensa tiap mata dipilih dari unit lensa pada nota --}}
<div class="mb-2 d-none psn-lensa-mata" id="psn_blok_nota">
    <div class="row g-2">
        <div class="col-6">
            <label class="form-label mb-1" style="font-size:.72rem">Lensa mata kanan (OD)</label>
            <select name="pesanan[unit_od]" id="psn_unit_od" class="form-select form-select-sm" disabled></select>
        </div>
        <div class="col-6">
            <label class="form-label mb-1" style="font-size:.72rem">Lensa mata kiri (OS)</label>
            <select name="pesanan[unit_os]" id="psn_unit_os" class="form-select form-select-sm" disabled></select>
        </div>
    </div>
    <div class="form-text" style="font-size:.68rem">
        Lensa yang sudah dipilih di satu mata tidak muncul lagi di mata lainnya. SPH/CYL terisi otomatis dari lensa;
        untuk lensa custom, AXIS dan ADD juga terisi otomatis dari nota.
    </div>
</div>

<div class="d-flex justify-content-end mb-1">
    <button type="button" id="psn_tukar" class="btn btn-light btn-sm" style="font-size:.7rem">
        <i class="bi bi-arrow-left-right"></i> Tukar OD / OS
    </button>
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

{{-- Data untuk script di bawah --}}
<div id="psn_sumber" hidden
     data-refraksi="{{ json_encode($refraksiPerCustomer ?? []) }}"
     data-lama="{{ old('pesanan.refraksi_id') }}"
     data-mode="{{ $modeAwal }}"
     data-unit-od="{{ old('pesanan.unit_od') }}"
     data-unit-os="{{ old('pesanan.unit_os') }}"></div>

@push('scripts')
<script>
(function () {
    var SISI  = ['od', 'os'];
    var KOLOM = ['sphere', 'cylinder', 'axis', 'add', 'mono_pd'];

    function el(id) { return document.getElementById(id); }

    var info     = el('psn_sumber');
    var mode     = el('psn_mode');
    var blokRef  = el('psn_blok_refraksi');
    var blokNota = el('psn_blok_nota');
    var selRef   = el('psn_refraksi');
    var selOd    = el('psn_unit_od');
    var selOs    = el('psn_unit_os');

    var peta = {};
    try { peta = JSON.parse(info.dataset.refraksi); } catch (e) { peta = {}; }

    var customerSekarang = null;
    var units = [];                              // unit lensa pada nota yang sedang dipilih
    var tungguOd = info.dataset.unitOd || '';    // pilihan lama (setelah validasi gagal), dipakai sekali
    var tungguOs = info.dataset.unitOs || '';

    // ---------- Refraksi ----------
    function isiOpsiRef(teks, nilai) {
        var o = document.createElement('option');
        o.value = nilai;
        o.textContent = teks;
        selRef.appendChild(o);
    }

    // Dipanggil saat nota/pelanggan berubah: isi ulang daftar refraksi milik pelanggan itu
    window.psnGantiCustomer = function (customerId, pilihId) {
        var id = customerId ? String(customerId) : '';
        if (id === customerSekarang) { return; }
        customerSekarang = id;

        selRef.innerHTML = '';
        isiOpsiRef('Pilih riwayat refraksi', '');

        var daftar = id && peta[id] ? peta[id] : [];
        daftar.forEach(function (r) { isiOpsiRef(r.label, r.id); });
        if (pilihId) { selRef.value = pilihId; }
    };

    // Memilih refraksi = mengisi kolom ukuran dengan nilainya (tetap bisa diubah)
    selRef.addEventListener('change', function () {
        var daftar = customerSekarang && peta[customerSekarang] ? peta[customerSekarang] : [];
        var dipilih = daftar.find(function (r) { return String(r.id) === selRef.value; });
        if (!dipilih) { return; }

        Object.keys(dipilih.nilai).forEach(function (kunci) {
            var e = el('psn_' + kunci);
            if (e) { e.value = dipilih.nilai[kunci] === null ? '' : dipilih.nilai[kunci]; }
        });
    });

    // ---------- Lensa dari nota ----------
    function cariUnit(key) {
        return units.find(function (u) { return u.key === key; }) || null;
    }

    // Isi satu select dengan semua unit kecuali unit yang sudah dipakai mata lain
    function isiSelect(sel, kecualiKey, nilai) {
        sel.innerHTML = '';
        var kosong = document.createElement('option');
        kosong.value = '';
        kosong.textContent = 'Pilih lensa';
        sel.appendChild(kosong);

        units.forEach(function (u) {
            if (kecualiKey && u.key === kecualiKey) { return; }
            var o = document.createElement('option');
            o.value = u.key;
            o.textContent = u.label;
            sel.appendChild(o);
        });
        sel.value = nilai || '';
    }

    // AXIS/ADD yang tadinya terisi dari lensa custom dikosongkan lagi kalau mata itu tidak lagi memakai custom
    function lepasCustom(input) {
        if (input.dataset.dariCustom === '1') {
            input.value = '';
            delete input.dataset.dariCustom;
        }
    }

    // Lensa custom: SPH, CYL, AXIS, ADD terisi dan terkunci.
    // Lensa biasa: SPH/CYL terisi dan terkunci, AXIS/ADD diisi manual.
    function terapkanUkuran() {
        SISI.forEach(function (s) {
            var u    = cariUnit(el('psn_unit_' + s).value);
            var sph  = el('psn_' + s + '_sphere');
            var cyl  = el('psn_' + s + '_cylinder');
            var axis = el('psn_' + s + '_axis');
            var add  = el('psn_' + s + '_add');

            if (u && u.custom) {
                sph.value  = u.sph  === null ? '' : u.sph;
                cyl.value  = u.cyl  === null ? '' : u.cyl;
                axis.value = u.axis === null ? '' : u.axis;
                add.value  = u.add  === null ? '' : u.add;
                [sph, cyl, axis, add].forEach(function (i) { i.readOnly = true; });
                axis.dataset.dariCustom = '1';
                add.dataset.dariCustom = '1';
                return;
            }

            lepasCustom(axis);
            lepasCustom(add);
            axis.readOnly = false;
            add.readOnly = false;

            if (u && u.sph !== null) {
                sph.value = u.sph;
                cyl.value = u.cyl === null ? '' : u.cyl;
                sph.readOnly = true;
                cyl.readOnly = true;
            } else if (u) {
                // Ukuran lensa belum tercatat: petugas mengetik sendiri
                sph.readOnly = false;
                cyl.readOnly = false;
            } else {
                // Mata ini tidak memakai lensa
                sph.value = '';
                cyl.value = '';
                sph.readOnly = true;
                cyl.readOnly = true;
            }
        });
    }

    // Bangun ulang kedua select supaya unit yang sama tidak bisa dipilih dua kali
    function segarkanUnit() {
        var od = selOd.value, os = selOs.value;
        isiSelect(selOd, os, od);
        isiSelect(selOs, selOd.value, os);
        terapkanUkuran();
    }

    // Di luar mode nota semua kolom ukuran bebas diisi
    function bukaSemuaUkuran() {
        SISI.forEach(function (s) {
            ['sphere', 'cylinder', 'axis', 'add'].forEach(function (k) {
                var i = el('psn_' + s + '_' + k);
                i.readOnly = false;
                lepasCustom(i);
            });
        });
    }

    function aturMode() {
        var m = mode.value;

        blokRef.classList.toggle('d-none', m !== 'refraksi');
        selRef.disabled = (m !== 'refraksi');
        if (m !== 'refraksi') { selRef.value = ''; }

        blokNota.classList.toggle('d-none', m !== 'nota');
        selOd.disabled = (m !== 'nota');
        selOs.disabled = (m !== 'nota');

        if (m === 'nota') {
            segarkanUnit();
        } else {
            selOd.innerHTML = '';
            selOs.innerHTML = '';
            bukaSemuaUkuran();
        }
    }

    // Dipanggil dari halaman Pesanan setiap kali nota dipilih
    window.psnGantiNota = function (daftar) {
        units = daftar || [];

        var opsiNota = mode.querySelector('option[value="nota"]');
        opsiNota.disabled = (units.length === 0);
        if (units.length === 0 && mode.value === 'nota') { mode.value = 'manual'; }

        isiSelect(selOd, '', tungguOd);
        isiSelect(selOs, '', tungguOs);
        tungguOd = '';
        tungguOs = '';

        aturMode();
    };

    selOd.addEventListener('change', segarkanUnit);
    selOs.addEventListener('change', segarkanUnit);
    mode.addEventListener('change', aturMode);

    // Tukar OD / OS
    el('psn_tukar').addEventListener('click', function () {
        KOLOM.forEach(function (k) {
            var a = el('psn_od_' + k), b = el('psn_os_' + k);
            var t = a.value;
            a.value = b.value;
            b.value = t;
        });

        if (mode.value === 'nota') {
            // Tukar pilihan lensa; ukuran ikut menyesuaikan lensa yang baru
            var od = selOd.value, os = selOs.value;
            isiSelect(selOd, od, os);
            isiSelect(selOs, os, od);
            terapkanUkuran();
        }
    });

    // Mode awal (mengikuti isian lama kalau validasi gagal)
    mode.value = info.dataset.mode || 'manual';
    aturMode();
})();
</script>
@endpush