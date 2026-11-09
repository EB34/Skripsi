@extends('layouts.app')

@push('styles')
<style>
    .kartu-rusak {
        display: flex; justify-content: space-between; align-items: center; gap: 16px;
        background: #fff;
        border: 1px solid #d9d9d9;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 16px;
    }
    .kartu-rusak .judul { font-size: .85rem; font-weight: 700; }
    .kartu-rusak .sub { font-size: .7rem; color: #777; }

    .aksi-btn {
        border-radius: 4px;
        padding: 4px 8px;
        font-size: .68rem; line-height: 1.15;
        border: 1px solid transparent;
    }
    .aksi-retur { background: #cfe5f7; border-color: #8fbbe0; color: #1c4e80; }
    .aksi-retur:hover { background: #bcdaf2; }
    .aksi-write { background: #ea8a8a; border-color: #d65f5f; color: #5a0f0f; }
    .aksi-write:hover { background: #e27373; }
    .selesai { color: #1f9d4a; font-weight: 600; }

    /* Panel pencarian (sama dengan tab Riwayat Mutasi Stok) */
    .panel-filter {
        display: flex; align-items: center; gap: 16px;
        background: #efefef;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
    .btn-reset {
        font-size: .78rem; color: #555; white-space: nowrap;
        text-decoration: none;
    }
    .btn-reset:hover { color: #000; text-decoration: underline; }
</style>
@endpush

@section('content')
@php
    /** @var \App\Models\User $user */
    $isOwner = auth()->user()->isOwner();
@endphp

<div class="container-fluid">
    @include('stok.header', ['aktif' => 'rusak'])

    <div class="kartu-rusak">
        <div>
            <div class="judul">Daftar Barang Rusak / Cacat</div>
            <div class="sub">
                Pencatatan barang yang dikeluarkan dari stok layak jual dan membutuhkan
                tindak lanjut retur supplier atau write off.
            </div>
        </div>
        <button type="button" class="btn-lapor" data-bs-toggle="modal" data-bs-target="#modalLaporRusak">
            <i class="bi bi-plus-lg"></i> Lapor Barang Rusak
        </button>
    </div>

    <!-- {{-- Pencarian: SKU, nama barang, merk, alasan kerusakan, atau nama pelapor --}}
    <form method="GET" action="{{ route('stok.rusak') }}" class="panel-filter">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" name="cari" value="{{ request('cari') }}"
                   placeholder="Cari SKU, nama barang, alasan kerusakan, atau pelapor">
        </div>
        @if(request('cari'))
            <a href="{{ route('stok.rusak') }}" class="btn-reset">Reset</a>
        @endif
    </form> -->

    <div class="tabel-mockup">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Tanggal &amp; Waktu</th>
                        <th>Nama &amp; SKU Barang</th>
                        <th>Qty</th>
                        <th>Alasan Kerusakan</th>
                        <th>Status Tindak Lanjut</th>
                        <th>Pelapor</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($daftarRusak as $r)
                    <tr>
                        <td class="mono">
                            {{ $r->created_at->locale('id')->translatedFormat('d M Y, H.i') }}
                        </td>
                        <td>
                            <div class="fw-bold">{{ ucfirst($r->barang?->jenis ?? '') }} {{ $r->barang?->merk ?? '-' }}</div>
                            <div class="mono sku-kecil">{{ $r->barang?->kode_model }}</div>
                        </td>
                        <td>{{ $r->jumlah }} pcs</td>
                        <td class="fw-bold">{{ $r->alasan }}</td>
                        <td>{{ $r->status_label }}</td>
                        <td>{{ $r->pelapor?->nama ?? '-' }}</td>
                        <td>
                            @if($r->status === 'menunggu_keputusan')
                                @if($isOwner)
                                    <div class="d-flex gap-1 justify-content-center">
                                        {{-- Retur: membuka modal untuk memilih PO asal dan alasan --}}
                                        <button type="button" class="aksi-btn aksi-retur btn-retur"
                                                data-bs-toggle="modal" data-bs-target="#modalRetur"
                                                data-action="{{ route('stok.rusak.putuskan', $r->id) }}"
                                                data-barang-id="{{ $r->barang_id }}"
                                                data-kode="{{ $r->barang?->kode_model }}">
                                            Retur<br>Supplier
                                        </button>

                                        <form method="POST" action="{{ route('stok.rusak.putuskan', $r->id) }}"
                                              onsubmit="return confirm('Write off barang ini? Barang dianggap hilang dari persediaan.')">
                                            @csrf
                                            <input type="hidden" name="keputusan" value="write_off">
                                            <button class="aksi-btn aksi-write">Write<br>Off</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted" style="font-size:.7rem">Menunggu Owner</span>
                                @endif
                            @else
                                <span class="selesai"><i class="bi bi-check-circle"></i> Selesai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-muted py-4">
                            @if(request('cari'))
                                Tidak ada laporan yang cocok dengan "{{ request('cari') }}".
                            @else
                                Belum ada laporan barang rusak.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftarRusak->links() }}</div>
</div>

@if($isOwner)
{{-- ===== Modal: Keputusan Retur ke Supplier ===== --}}
<div class="modal fade" id="modalRetur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formRetur" data-po="{{ json_encode($poPerBarang) }}">
                @csrf
                <input type="hidden" name="keputusan" value="retur_supplier">
                <div class="modal-header">
                    <h5 class="modal-title">Retur ke Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Barang: <strong id="returKode">-</strong></p>

                    <label class="form-label">PO asal (supplier)</label>
                    <select name="pembelian_id" id="returPembelian" class="form-select mb-1" required>
                        <option value="">Pilih PO asal</option>
                    </select>
                    <div id="returKosong" class="form-text text-danger d-none">
                        Tidak ada PO yang memuat barang ini, jadi retur tidak bisa dibuat.
                    </div>

                    <label class="form-label mt-3">Alasan retur</label>
                    <select name="alasan_retur" id="returAlasan" class="form-select mb-3" required>
                        <option value="">Pilih alasan</option>
                        @foreach($alasanRetur as $kode => $label)
                            <option value="{{ $kode }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <div class="form-text">
                        Retur dibuat dengan status diajukan. Owner menerbitkan surat jalan di tab Retur ke Supplier.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnRetur">Buat Retur</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@include('stok.modal_lapor')
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('formRetur');
        if (!form) return;

        const poPerBarang = JSON.parse(form.dataset.po || '{}');

        const kode      = document.getElementById('returKode');
        const selectPo  = document.getElementById('returPembelian');
        const alasan    = document.getElementById('returAlasan');
        const kosong    = document.getElementById('returKosong');
        const btnSubmit = document.getElementById('btnRetur');

        document.querySelectorAll('.btn-retur').forEach(function (btn) {
            btn.addEventListener('click', function () {
                form.action = btn.dataset.action;
                kode.textContent = btn.dataset.kode || '-';

                // Isi dropdown PO sesuai barang yang dipilih
                const daftar = poPerBarang[btn.dataset.barangId] || [];
                selectPo.innerHTML = '<option value="">Pilih PO asal</option>';
                daftar.forEach(function (p) {
                    const opsi = document.createElement('option');
                    opsi.value = p.id;
                    opsi.textContent = p.label;
                    selectPo.appendChild(opsi);
                });

                const adaPo = daftar.length > 0;
                selectPo.disabled = !adaPo;
                btnSubmit.disabled = !adaPo;
                kosong.classList.toggle('d-none', adaPo);
                alasan.value = '';
            });
        });
    })();
</script>
@endpush