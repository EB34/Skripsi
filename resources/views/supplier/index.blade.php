@extends('layouts.app')

@section('title', 'Data Supplier')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" action="{{ route('supplier.index') }}" class="d-flex gap-2">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control"
               placeholder="Cari nama perusahaan...">
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
                            {{ $s->nama_kontak ?? '-' }}
                            @if ($s->wa_kontak)
                                <div class="small text-muted">
                                    <i class="bi bi-whatsapp text-success"></i> {{ $s->wa_kontak }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $s->wa_perusahaan ?? '-' }}</td>
                        <td>{{ $s->email ?? '-' }}</td>
                        <td>{{ $s->alamat ?? '-' }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning btn-ubah"
                                    data-action="{{ route('supplier.update', $s) }}"
                                    data-id="{{ $s->id }}"
                                    data-nama-perusahaan="{{ $s->nama_perusahaan }}"
                                    data-nama-kontak="{{ $s->nama_kontak }}"
                                    data-wa-kontak="{{ $s->wa_kontak }}"
                                    data-wa-perusahaan="{{ $s->wa_perusahaan }}"
                                    data-email="{{ $s->email }}"
                                    data-alamat="{{ $s->alamat }}">Ubah</button>
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
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_perusahaan" id="inputNamaPerusahaan"
                                   value="{{ old('nama_perusahaan') }}"
                                   class="form-control @error('nama_perusahaan') is-invalid @enderror" required>
                            @error('nama_perusahaan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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

                        <div class="col-md-6">
                            <label class="form-label">Nama Contact Person</label>
                            <input type="text" name="nama_kontak" id="inputNamaKontak"
                                   value="{{ old('nama_kontak') }}"
                                   class="form-control @error('nama_kontak') is-invalid @enderror">
                            @error('nama_kontak')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">No. WhatsApp Contact Person</label>
                            <input type="text" name="wa_kontak" id="inputWaKontak"
                                   value="{{ old('wa_kontak') }}" placeholder="Contoh: 08123456789"
                                   class="form-control @error('wa_kontak') is-invalid @enderror">
                            @error('wa_kontak')
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('modalSupplier');
    var modal = new bootstrap.Modal(modalEl);

    var form = document.getElementById('formSupplier');
    var judul = document.getElementById('judulModal');
    var tombolSimpan = document.getElementById('tombolSimpan');
    var inputMethod = document.getElementById('inputMethod');
    var inputMode = document.getElementById('inputMode');
    var inputId = document.getElementById('inputId');

    // Kolom isian: id elemen -> nama atribut data-* di tombol Ubah
    var kolom = {
        inputNamaPerusahaan: 'namaPerusahaan',
        inputNamaKontak: 'namaKontak',
        inputWaKontak: 'waKontak',
        inputWaPerusahaan: 'waPerusahaan',
        inputEmail: 'email',
        inputAlamat: 'alamat'
    };

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
        judul.textContent = 'Tambah Supplier';
        tombolSimpan.textContent = 'Simpan';
        if (kosongkan) {
            Object.keys(kolom).forEach(function (idEl) {
                document.getElementById(idEl).value = '';
            });
        }
    }

    // data = dataset dari tombol Ubah; null berarti isian dibiarkan (dipakai saat buka ulang karena error)
    function modeUbah(action, id, data) {
        form.action = action;
        inputMethod.disabled = false;
        inputMode.value = 'edit';
        inputId.value = id;
        judul.textContent = 'Ubah Supplier';
        tombolSimpan.textContent = 'Perbarui';
        if (data !== null) {
            Object.keys(kolom).forEach(function (idEl) {
                document.getElementById(idEl).value = data[kolom[idEl]] || '';
            });
        }
    }

    // Tombol "Tambah Supplier"
    document.getElementById('btnTambah').addEventListener('click', function () {
        bersihkanError();
        modeTambah(true);
        modal.show();
    });

    // Tombol "Ubah" di tiap baris
    document.querySelectorAll('.btn-ubah').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            bersihkanError();
            modeUbah(tombol.dataset.action, tombol.dataset.id, tombol.dataset);
            modal.show();
        });
    });

    // Validasi gagal: buka lagi modalnya dengan isian lama dan pesan error
    var bukaLagi = modalEl.dataset.buka;
    if (bukaLagi === 'edit') {
        modeUbah(modalEl.dataset.actionLama, inputId.value, null);
        modal.show();
    } else if (bukaLagi === 'tambah') {
        modeTambah(false);
        modal.show();
    }
});
</script>
@endsection