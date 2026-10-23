@extends('layouts.app')

@section('title', 'Buat Pesanan Pembelian')

@section('content')
<h1 class="page-title mb-3">Buat Pesanan Pembelian</h1>

<form method="POST" action="{{ route('pembelian.store') }}" class="card">
    <div class="card-body">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select" required>
                    <option value="">-- Pilih supplier --</option>
                    @foreach ($supplier as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->nama }}</option>
                    @endforeach
                </select>
                <div class="form-text">
                    Supplier belum ada? <a href="{{ route('supplier.create') }}">Tambah supplier</a>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Pesanan</label>
                <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}"
                       class="form-control" required>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Barang</th>
                        <th style="width: 120px">Jumlah</th>
                        <th style="width: 190px">Harga Beli (Rp)</th>
                        <th style="width: 170px" class="text-end">Subtotal</th>
                        <th style="width: 50px"></th>
                    </tr>
                </thead>
                <tbody id="baris-barang"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Total</td>
                        <td class="text-end fw-bold" id="total-po">Rp 0</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="button" id="tambah-baris" class="btn-abu mb-4">
            <i class="bi bi-plus-lg"></i> Tambah Barang
        </button>

        <div>
            <a href="{{ route('pembelian.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Pesanan</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
@php
    $barangJs = $barang->map(fn ($b) => ['id' => $b->id, 'teks' => $b->kode_model . ' - ' . $b->merk])->values();
@endphp

{{-- Data untuk JavaScript dititipkan di sini (Blade di luar tag script) --}}
<div id="data-pembelian" hidden
     data-barang="{{ json_encode($barangJs) }}"
     data-lama="{{ json_encode(array_values(old('items', []))) }}"></div>

<script>
    const dataEl       = document.getElementById('data-pembelian');
    const daftarBarang = JSON.parse(dataEl.dataset.barang);
    const isianLama    = JSON.parse(dataEl.dataset.lama);

    const body = document.getElementById('baris-barang');
    let indeks = 0;

    function rupiah(angka) {
        return 'Rp ' + Math.round(angka).toLocaleString('id-ID');
    }

    function hitung() {
        let total = 0;
        body.querySelectorAll('tr').forEach(tr => {
            const jumlah = parseFloat(tr.querySelector('.jumlah').value) || 0;
            const harga  = parseFloat(tr.querySelector('.harga').value) || 0;
            const sub    = jumlah * harga;
            tr.querySelector('.subtotal').textContent = rupiah(sub);
            total += sub;
        });
        document.getElementById('total-po').textContent = rupiah(total);
    }

    function tambahBaris(data = {}) {
        const i  = indeks++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="items[${i}][barang_id]" class="form-select" required>
                    <option value="">-- Pilih barang --</option>
                </select>
            </td>
            <td><input type="number" name="items[${i}][jumlah]" class="form-control jumlah" min="1" required></td>
            <td><input type="number" name="items[${i}][harga_beli]" class="form-control harga" min="0" step="any" required></td>
            <td class="subtotal text-end">Rp 0</td>
            <td><button type="button" class="icon-btn hapus" title="Hapus baris"><i class="bi bi-x-lg"></i></button></td>
        `;

        const pilihan = tr.querySelector('select');
        daftarBarang.forEach(b => {
            const opsi = document.createElement('option');
            opsi.value = b.id;
            opsi.textContent = b.teks;
            pilihan.appendChild(opsi);
        });

        pilihan.value = data.barang_id ?? '';
        tr.querySelector('.jumlah').value = data.jumlah ?? '';
        tr.querySelector('.harga').value  = data.harga_beli ?? '';

        body.appendChild(tr);
        hitung();
    }

    body.addEventListener('input', hitung);

    body.addEventListener('click', e => {
        const tombol = e.target.closest('.hapus');
        if (tombol && body.querySelectorAll('tr').length > 1) {
            tombol.closest('tr').remove();
            hitung();
        }
    });

    document.getElementById('tambah-baris').addEventListener('click', () => tambahBaris());

    // Isi awal: pulihkan isian lama kalau validasi gagal, atau satu baris kosong
    if (isianLama.length) {
        isianLama.forEach(tambahBaris);
    } else {
        tambahBaris();
    }
</script>
@endpush