@extends('layouts.app')

@section('title', 'Data Supplier')

@push('styles')
<style>
    .baris-kontak { background: #efefef; border-radius: 8px; padding: 8px; margin-bottom: 8px; }
</style>
@endpush

@section('content')
@php
    // Pesan error khusus contact person (nama kosong, nama kembar)
    $errKontak = collect($errors->getMessages())
        ->filter(fn ($pesan, $kunci) => str_starts_with($kunci, 'kontak'))
        ->flatten();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" action="{{ route('supplier.index') }}" class="d-flex gap-2">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control"
               placeholder="Cari nama perusahaan atau contact person...">
        <button class="btn btn-outline-primary">Cari</button>
    </form>

    <button type="button" id="btnTambah" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Supplier
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nama Perusahaan</th>
                    <th>Contact Person</th>
                    <th>WA Perusahaan</th>
                    <th>Email</th>
                    <th>Alamat</th>
                    <th style="width: 140px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($supplier as $s)
                    <tr>
                        <td class="fw-semibold">{{ $s->nama_perusahaan }}</td>
                        <td>
                            @forelse ($s->kontak as $k)
                                <div class="mb-1">
                                    {{ $k->nama }}
                                    @if ($k->wa)
                                        <div class="small text-muted">
                                            <i class="bi bi-whatsapp text-success"></i> {{ $k->wa }}
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <span class="text-muted">-</span>
                            @endforelse
                            <button type="button" class="btn btn-link btn-sm p-0 btn-kontak" data-id="{{ $s->id }}">
                                <i class="bi bi-plus-lg"></i> Tambah contact person
                            </button>
                        </td>
                        <td>{{ $s->wa_perusahaan ?? '-' }}</td>
                        <td>{{ $s->email ?? '-' }}</td>
                        <td>{{ $s->alamat ?? '-' }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning btn-ubah"
                                    data-id="{{ $s->id }}">Ubah</button>
                            <form action="{{ route('supplier.destroy', $s) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus supplier {{ $s->nama_perusahaan }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada data supplier.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $supplier->links() }}
</div>

{{-- ===== Modal tambah / ubah supplier ===== --}}
<div class="modal fade" id="modalSupplier" tabindex="-1" aria-hidden="true"
     data-action-store="{{ route('supplier.store') }}"
     data-buka="{{ $errors->any() ? old('_mode') : '' }}"
     data-action-lama="{{ (old('_mode') === 'edit' && old('_id')) ? route('supplier.update', old('_id')) : '' }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formSupplier" method="POST" action="{{ route('supplier.store') }}">
                @csrf
                <input type="hidden" name="_method" value="PUT" id="inputMethod" disabled>
                <input type="hidden" name="_mode" id="inputMode" value="{{ old('_mode', 'tambah') }}">
                <input type="hidden" name="_id" id="inputId" value="{{ old('_id') }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="judulModal">Tambah Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    @if ($errKontak->isNotEmpty())
                        <div class="alert alert-danger py-2 small" id="alertKontak">
                            <ul class="mb-0 ps-3">
                                @foreach ($errKontak as $pesan)
                                    <li>{{ $pesan }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_perusahaan" id="inputNamaPerusahaan"
                                   value="{{ old('nama_perusahaan') }}" autocomplete="off"
                                   class="form-control @error('nama_perusahaan') is-invalid @enderror" required>
                            @error('nama_perusahaan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            {{-- Hasil pengecekan nama (diisi JavaScript) --}}
                            <div id="cekNama" class="mt-2"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">No. WhatsApp Perusahaan</label>
                            <input type="text" name="wa_perusahaan" id="inputWaPerusahaan"
                                   value="{{ old('wa_perusahaan') }}" placeholder="Contoh: 0271xxxxxxx"
                                   class="form-control @error('wa_perusahaan') is-invalid @enderror">
                            @error('wa_perusahaan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="inputEmail"
                                   value="{{ old('email') }}" placeholder="Contoh: info@supplier.com"
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" id="inputAlamat" rows="3"
                                      class="form-control @error('alamat') is-invalid @enderror">{{ old('alamat') }}</textarea>
                            @error('alamat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Contact Person</label>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnTambahKontak">
                                    <i class="bi bi-plus-lg"></i> Tambah Contact Person
                                </button>
                            </div>
                            <div id="daftarKontak"></div>
                            <div class="form-text">Boleh lebih dari satu. Baris yang dikosongkan tidak disimpan.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="tombolSimpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Data untuk JavaScript dititipkan di atribut HTML --}}
<div id="data-supplier" hidden
     data-supplier="{{ json_encode($daftarSupplier) }}"
     data-kontak-lama="{{ json_encode(array_values((array) old('kontak', []))) }}"></div>
@endsection

@push('scripts')
<script>
(function () {
    var modalEl = document.getElementById('modalSupplier');
    var modal = new bootstrap.Modal(modalEl);

    var dataEl = document.getElementById('data-supplier');
    var daftar = JSON.parse(dataEl.dataset.supplier);          // semua supplier
    var kontakLama = JSON.parse(dataEl.dataset.kontakLama);    // isian kontak sebelum validasi gagal

    var peta = {};
    daftar.forEach(function (s) { peta[s.id] = s; });

    var form = document.getElementById('formSupplier');
    var judul = document.getElementById('judulModal');
    var tombolSimpan = document.getElementById('tombolSimpan');
    var inputMethod = document.getElementById('inputMethod');
    var inputMode = document.getElementById('inputMode');
    var inputId = document.getElementById('inputId');
    var inputNama = document.getElementById('inputNamaPerusahaan');
    var cekNama = document.getElementById('cekNama');
    var daftarKontak = document.getElementById('daftarKontak');

    // Kolom isian: id elemen -> nama field di data supplier
    var kolom = {
        inputNamaPerusahaan: 'nama_perusahaan',
        inputWaPerusahaan: 'wa_perusahaan',
        inputEmail: 'email',
        inputAlamat: 'alamat'
    };

    var nomorBaris = 0;
    var fokusKontakBaru = false;

    function norm(teks) {
        return (teks || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function buatElemen(tag, kelas, teks) {
        var el = document.createElement(tag);
        if (kelas) { el.className = kelas; }
        if (teks) { el.textContent = teks; }
        return el;
    }

    function bersihkanError() {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        var alertKontak = document.getElementById('alertKontak');
        if (alertKontak) { alertKontak.remove(); }
    }

    // ---------- Baris contact person ----------
    function tambahBaris(data) {
        data = data || {};
        var i = nomorBaris++;

        var baris = buatElemen('div', 'baris-kontak');
        var row = buatElemen('div', 'row g-2 align-items-center');

        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'kontak[' + i + '][id]';
        idInput.value = data.id || '';

        var colNama = buatElemen('div', 'col-md-5');
        var nama = document.createElement('input');
        nama.type = 'text';
        nama.name = 'kontak[' + i + '][nama]';
        nama.className = 'form-control form-control-sm';
        nama.placeholder = 'Nama contact person';
        nama.maxLength = 255;
        nama.value = data.nama || '';
        colNama.appendChild(nama);

        var colWa = buatElemen('div', 'col-md-5');
        var wa = document.createElement('input');
        wa.type = 'text';
        wa.name = 'kontak[' + i + '][wa]';
        wa.className = 'form-control form-control-sm';
        wa.placeholder = 'No. WhatsApp';
        wa.maxLength = 20;
        wa.value = data.wa || '';
        colWa.appendChild(wa);

        var colHapus = buatElemen('div', 'col-md-2 text-end');
        var hapus = buatElemen('button', 'btn btn-sm btn-outline-danger');
        hapus.type = 'button';
        hapus.title = 'Hapus baris';
        hapus.appendChild(buatElemen('i', 'bi bi-x-lg'));
        hapus.addEventListener('click', function () {
            baris.remove();
            if (!daftarKontak.children.length) { tambahBaris(); }
        });
        colHapus.appendChild(hapus);

        row.appendChild(colNama);
        row.appendChild(colWa);
        row.appendChild(colHapus);
        baris.appendChild(idInput);
        baris.appendChild(row);
        daftarKontak.appendChild(baris);
    }

    function isiKontak(daftarK) {
        daftarKontak.textContent = '';
        nomorBaris = 0;
        (daftarK || []).forEach(tambahBaris);
        if (!daftarKontak.children.length) { tambahBaris(); }
    }

    function fokusBarisAkhir() {
        var baris = daftarKontak.lastElementChild;
        if (baris) { baris.querySelector('input[name$="[nama]"]').focus(); }
    }

    // Contact person yang sudah sempat diketik tapi belum tersimpan (baris tanpa id)
    function ambilKontakBaru() {
        var hasil = [];
        daftarKontak.querySelectorAll('.baris-kontak').forEach(function (baris) {
            var id = baris.querySelector('input[name$="[id]"]').value;
            var nama = baris.querySelector('input[name$="[nama]"]').value.trim();
            var wa = baris.querySelector('input[name$="[wa]"]').value.trim();
            if (!id && (nama || wa)) { hasil.push({ id: '', nama: nama, wa: wa }); }
        });
        return hasil;
    }

    // ---------- Pengecekan nama perusahaan ----------
    function periksaNama() {
        cekNama.textContent = '';
        tombolSimpan.disabled = false;

        var q = norm(inputNama.value);
        if (!q) { return; }

        var idSendiri = (inputMode.value === 'edit') ? parseInt(inputId.value, 10) : 0;
        var sama = null;
        var mirip = [];

        daftar.forEach(function (s) {
            if (s.id === idSendiri) { return; }
            var n = norm(s.nama_perusahaan);
            if (n === q) {
                sama = s;
            } else if (q.length >= 3 && (n.indexOf(q) !== -1 || q.indexOf(n) !== -1)) {
                mirip.push(s);
            }
        });

        if (sama) {
            var kotak = buatElemen('div', 'alert alert-danger py-2 small mb-2');
            kotak.appendChild(buatElemen('strong', '', 'Supplier ini sudah ada. '));
            kotak.appendChild(document.createTextNode(
                'Nama perusahaan tidak boleh sama dengan "' + sama.nama_perusahaan + '".'
            ));

            if (sama.kontak.length) {
                kotak.appendChild(buatElemen('div', 'mt-1',
                    'Contact person saat ini: ' + sama.kontak.map(function (k) { return k.nama; }).join(', ')));
            }

            if (inputMode.value === 'tambah') {
                var tombol = buatElemen('button', 'btn btn-sm btn-dark mt-2', 'Tambah contact person ke supplier ini');
                tombol.type = 'button';
                tombol.addEventListener('click', function () { pindahKeUbah(sama); });
                kotak.appendChild(tombol);
            }

            cekNama.appendChild(kotak);
            tombolSimpan.disabled = true;
        }

        if (mirip.length) {
            cekNama.appendChild(buatElemen('div', 'small text-muted',
                'Supplier yang mirip: ' + mirip.slice(0, 5).map(function (s) { return s.nama_perusahaan; }).join(', ')));
        }
    }

    // ---------- Mode form ----------
    function modeTambah(kosongkan, kontak) {
        form.action = modalEl.dataset.actionStore;
        inputMethod.disabled = true;
        inputMode.value = 'tambah';
        inputId.value = '';
        judul.textContent = 'Tambah Supplier';
        tombolSimpan.textContent = 'Simpan';
        if (kosongkan) {
            Object.keys(kolom).forEach(function (idEl) {
                document.getElementById(idEl).value = '';
            });
        }
        isiKontak(kosongkan ? [] : kontak);
        periksaNama();
    }

    // data = data supplier; null berarti isian dibiarkan (dipakai saat buka ulang karena error)
    function modeUbah(action, id, data, kontak) {
        form.action = action;
        inputMethod.disabled = false;
        inputMode.value = 'edit';
        inputId.value = id;
        judul.textContent = 'Ubah Supplier';
        tombolSimpan.textContent = 'Perbarui';
        if (data) {
            Object.keys(kolom).forEach(function (idEl) {
                document.getElementById(idEl).value = data[kolom[idEl]] || '';
            });
        }
        isiKontak(kontak);
        periksaNama();
    }

    // Dari peringatan "supplier sudah ada": buka form ubah supplier itu, bawa kontak yang sudah diketik
    function pindahKeUbah(s) {
        var baru = ambilKontakBaru();
        modeUbah(s.update, s.id, s, s.kontak.concat(baru));
        if (!baru.length) { tambahBaris(); }
        fokusBarisAkhir();
    }

    // ---------- Tombol-tombol ----------
    document.getElementById('btnTambah').addEventListener('click', function () {
        bersihkanError();
        modeTambah(true, []);
        modal.show();
    });

    document.querySelectorAll('.btn-ubah').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var s = peta[tombol.dataset.id];
            bersihkanError();
            modeUbah(s.update, s.id, s, s.kontak);
            modal.show();
        });
    });

    // "Tambah contact person" di tabel: buka form ubah dengan satu baris kosong siap diisi
    document.querySelectorAll('.btn-kontak').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var s = peta[tombol.dataset.id];
            bersihkanError();
            modeUbah(s.update, s.id, s, s.kontak);
            tambahBaris();
            fokusKontakBaru = true;
            modal.show();
        });
    });

    document.getElementById('btnTambahKontak').addEventListener('click', function () {
        tambahBaris();
        fokusBarisAkhir();
    });

    inputNama.addEventListener('input', function () {
        inputNama.classList.remove('is-invalid');
        periksaNama();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        if (fokusKontakBaru) {
            fokusBarisAkhir();
            fokusKontakBaru = false;
        } else if (inputMode.value === 'tambah') {
            inputNama.focus();
        }
    });

    // Validasi gagal: buka lagi modalnya dengan isian lama dan pesan error
    var bukaLagi = modalEl.dataset.buka;
    if (bukaLagi === 'edit') {
        modeUbah(modalEl.dataset.actionLama, inputId.value, null, kontakLama);
        modal.show();
    } else if (bukaLagi === 'tambah') {
        modeTambah(false, kontakLama);
        modal.show();
    }
})();
</script>
@endpush