@extends('layouts.app')

@section('title', 'Modul Retur')

@section('content')
@php
    /** @var \App\Models\User $user */
    $user    = auth()->user();
    $isOwner = $user->isOwner();

    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $namaBarang = fn ($b) => $b ? trim(($b->merk ?? '') . ' ' . ($b->kode_model ?? '')) : '-';
@endphp

<div class="container-fluid">
    @include('retur.header', ['aktif' => 'kesalahan'])

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            @if ($isOwner)
                <div class="rt-kartu">Total kerugian kesalahan toko: <strong>{{ $rp($totalNilai) }}</strong></div>
            @endif
        </div>
        <button type="button" class="btn-abu" data-bs-toggle="modal" data-bs-target="#modalKesalahan">
            <i class="bi bi-plus-lg"></i> Catat Penggantian Lensa
        </button>
    </div>

    <div class="tabel-abu">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No. &amp; Tanggal</th>
                        <th>Pesanan &amp; Pelanggan</th>
                        <th>Jenis Kesalahan</th>
                        <th>Lensa Lama (Write Off)</th>
                        <th>Lensa Pengganti</th>
                        @if ($isOwner)
                            <th class="text-end">Kerugian (HPP)</th>
                        @endif
                        <th>Dicatat oleh</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($daftar as $k)
                    <tr>
                        <td>
                            <div class="mono fw-bold">{{ $k->nomor }}</div>
                            <div class="rt-sub">{{ $k->created_at->locale('id')->translatedFormat('d M Y, H.i') }}</div>
                        </td>
                        <td>
                            <div class="mono">{{ $k->pesanan?->nomor_pesanan ?? '-' }}</div>
                            <div class="fw-bold">{{ $k->pesanan?->customer?->nama ?? '-' }}</div>
                        </td>
                        <td style="max-width:220px">
                            <div class="fw-bold">{{ $k->jenis_label }}</div>
                            @if ($k->keterangan)
                                <div class="rt-sub">{{ $k->keterangan }}</div>
                            @endif
                        </td>
                        <td>{{ $namaBarang($k->lensaWriteoff) }} <span class="rt-sub">x {{ $k->jumlah }} biji</span></td>
                        <td>{{ $namaBarang($k->lensaPengganti) }} <span class="rt-sub">x {{ $k->jumlah }} biji</span></td>
                        @if ($isOwner)
                            <td class="text-end fw-bold">{{ $rp($k->nilai_kerugian) }}</td>
                        @endif
                        <td>{{ $k->pencatat?->nama ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isOwner ? 7 : 6 }}" class="text-muted py-4 text-center">Belum ada penggantian akibat kesalahan toko.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

{{-- ===== Modal: Catat Penggantian Lensa ===== --}}
<div class="modal fade" id="modalKesalahan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('retur.kesalahan.store') }}" id="formKesalahan">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Catat Penggantian Lensa (Kesalahan Toko)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($pesananOpsi->isEmpty())
                        <div class="alert alert-secondary mb-0">
                            Belum ada pesanan yang bisa dicatat. Pesanan harus memakai lensa dari toko dan berstatus
                            Sedang Dikerjakan, Siap Diambil, atau Selesai.
                        </div>
                    @else
                        <label class="form-label">Pesanan</label>
                        <select name="pesanan_id" id="ksPesanan" class="form-select mb-1" required>
                            <option value="">Pilih pesanan</option>
                            @foreach ($pesananOpsi as $p)
                                <option value="{{ $p->id }}"
                                        data-jumlah="{{ (int) $p->jumlah_lensa }}"
                                        data-lensa="{{ $namaBarang($p->lensa) }}"
                                        @selected((string) old('pesanan_id') === (string) $p->id)>
                                    {{ $p->nomor_pesanan }} — {{ $p->customer?->nama }} — {{ $namaBarang($p->lensa) }} x{{ $p->jumlah_lensa }} ({{ $p->status_label }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text mb-3" id="ksInfo">Lensa lama pada pesanan ini akan dicatat sebagai write off.</div>

                        <label class="form-label">Jenis kesalahan</label>
                        <select name="jenis_kesalahan" class="form-select mb-3" required>
                            <option value="">Pilih jenis kesalahan</option>
                            @foreach (\App\Models\PenggantianKesalahanToko::JENIS as $kode => $label)
                                <option value="{{ $kode }}" @selected(old('jenis_kesalahan') === $kode)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <label class="form-label">Lensa pengganti (dari stok)</label>
                        <select name="lensa_pengganti_id" class="form-select mb-3" required>
                            <option value="">Pilih lensa pengganti</option>
                            @foreach ($lensaStok as $l)
                                <option value="{{ $l->id }}" @selected((string) old('lensa_pengganti_id') === (string) $l->id)>
                                    {{ $l->kode_model }} - {{ $l->merk }}{{ $l->ukuran_label ? ' (' . $l->ukuran_label . ')' : '' }} — stok {{ $l->stok_tersedia }}
                                </option>
                            @endforeach
                        </select>

                        <label class="form-label">Jumlah biji yang diganti</label>
                        <input type="number" name="jumlah" id="ksJumlah" min="1" max="2" value="{{ old('jumlah', 1) }}" class="form-control mb-3" required>

                        <label class="form-label">Keterangan (opsional)</label>
                        <input type="text" name="keterangan" maxlength="255" value="{{ old('keterangan') }}" class="form-control">
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    @if ($pesananOpsi->isNotEmpty())
                        <button type="submit" class="btn btn-primary" id="btnKesalahan">Simpan &amp; Keluarkan dari Stok</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const sel = document.getElementById('ksPesanan');
        if (!sel) return;

        const inJumlah = document.getElementById('ksJumlah');
        const info     = document.getElementById('ksInfo');

        function atur() {
            const opt = sel.options[sel.selectedIndex];
            const max = parseInt(opt && opt.dataset.jumlah) || 2;
            inJumlah.max = max;
            if (parseInt(inJumlah.value) > max) inJumlah.value = max;
            info.textContent = sel.value
                ? 'Lensa lama (write off): ' + opt.dataset.lensa + '. Maksimal diganti ' + max + ' biji.'
                : 'Lensa lama pada pesanan ini akan dicatat sebagai write off.';
        }

        sel.addEventListener('change', atur);
        atur();

        document.getElementById('formKesalahan').addEventListener('submit', function () {
            document.getElementById('btnKesalahan').disabled = true;
        });
    })();
</script>
@endpush