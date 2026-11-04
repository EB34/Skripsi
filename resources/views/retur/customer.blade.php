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
    @include('retur.header', ['aktif' => 'customer'])

    @if ($isOwner && ($hitung['dicatat'] ?? 0) > 0)
        <div class="rt-banner">
            <i class="bi bi-exclamation-triangle"></i>
            Ada {{ $hitung['dicatat'] }} retur customer menunggu konfirmasi dan tindak lanjut Owner.
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="rt-filter mb-0">
            <a href="{{ route('retur.customer') }}" class="chip {{ ! $status ? 'active' : '' }}">Semua</a>
            @foreach (\App\Models\ReturCustomer::STATUS as $kode => $label)
                <a href="{{ route('retur.customer', ['status' => $kode]) }}"
                   class="chip {{ $status === $kode ? 'active' : '' }}">
                    {{ $label }} ({{ $hitung[$kode] ?? 0 }})
                </a>
            @endforeach
        </div>
        <button type="button" class="btn-abu" data-bs-toggle="modal" data-bs-target="#modalCatatRetur">
            <i class="bi bi-plus-lg"></i> Catat Retur Customer
        </button>
    </div>

    <div class="rt-tabel">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No. Retur &amp; Tanggal</th>
                        <th>Nota &amp; Pelanggan</th>
                        <th>Frame &amp; Keluhan</th>
                        <th>Status</th>
                        <th>Hasil Penanganan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($daftar as $r)
                    @php
                        $nota       = $r->penjualan;
                        $barisFrame = $nota->detail->firstWhere('barang_id', $r->barang_id);
                        $hargaFrame = (float) ($barisFrame->harga_jual ?? 0);
                    @endphp
                    <tr>
                        <td>
                            <div class="mono fw-bold">{{ $r->nomor_retur }}</div>
                            <div class="rt-sub">{{ $r->tanggal->locale('id')->translatedFormat('d M Y') }}</div>
                            <div class="rt-sub">Dicatat: {{ $r->pencatat?->nama ?? '-' }}</div>
                        </td>
                        <td>
                            <a href="{{ route('penjualan.show', $nota) }}" class="mono">{{ $nota->nomor_nota }}</a>
                            <div class="fw-bold">{{ $nota->customer?->nama ?? '-' }}</div>
                            <div class="rt-sub mono">{{ $nota->customer?->nomor_wa }}</div>
                        </td>
                        <td style="max-width:260px">
                            <div class="fw-bold">{{ $namaBarang($r->barang) }}</div>
                            <div>{{ $r->keluhan }}</div>
                        </td>
                        <td><span class="rt-status {{ $r->status }}">{{ $r->status_label }}</span></td>
                        <td>
                            @if ($r->status === 'selesai')
                                <div class="fw-bold">{{ $r->tindak_lanjut_label }}</div>
                                <div class="rt-sub">{{ $r->konfirmasi_label }}</div>
                                @if ($r->tindak_lanjut === 'tukar_stok_toko')
                                    <div>Pengganti: {{ $namaBarang($r->pengganti) }}</div>
                                    @if ($isOwner)
                                        <div class="rt-sub">Kerugian (HPP): {{ $rp($r->nilai_kerugian) }}</div>
                                    @endif
                                @elseif ($r->tindak_lanjut === 'refund')
                                    <div>Refund {{ $rp($r->jumlah_refund) }}</div>
                                @endif
                                @if ($r->catatan_owner)
                                    <div class="rt-sub">{{ $r->catatan_owner }}</div>
                                @endif
                                <div class="rt-sub">Diproses: {{ $r->pemroses?->nama ?? '-' }}</div>
                            @else
                                <span class="rt-sub">Belum diproses</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($r->status === 'dicatat')
                                @if ($isOwner)
                                    <button type="button" class="rt-btn teal" data-bs-toggle="modal" data-bs-target="#modalProses"
                                            data-action="{{ route('retur.customer.proses', $r->id) }}"
                                            data-nomor="{{ $r->nomor_retur }}"
                                            data-frame="{{ $namaBarang($r->barang) }}"
                                            data-keluhan="{{ $r->keluhan }}"
                                            data-pelanggan="{{ $nota->customer?->nama }}"
                                            data-refund="{{ $hargaFrame }}"
                                            data-maks="{{ $nota->total_dibayar }}">
                                        <i class="bi bi-check2-square"></i> Proses
                                    </button>
                                @else
                                    <span class="rt-sub">Menunggu Owner</span>
                                @endif
                            @else
                                <span class="text-success"><i class="bi bi-check-circle"></i></span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted py-4 text-center">Belum ada retur dari customer.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

{{-- ===== Modal: Catat Retur Customer ===== --}}
<div class="modal fade" id="modalCatatRetur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('retur.customer.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Catat Retur Customer (Frame Cacat)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($notaTersedia->isEmpty())
                        <div class="alert alert-secondary mb-0">
                            Tidak ada nota yang bisa diretur. Retur hanya untuk nota berstatus <strong>Selesai</strong>
                            yang berisi frame dan belum pernah diretur.
                        </div>
                    @else
                        <label class="form-label">Nota penjualan</label>
                        <select name="penjualan_id" class="form-select mb-3" required>
                            <option value="">Pilih nota</option>
                            @foreach ($notaTersedia as $n)
                                @php
                                    $fr = $n->detail->first(fn ($d) => strtolower((string) $d->barang->jenis) === 'frame');
                                @endphp
                                <option value="{{ $n->id }}" @selected((string) old('penjualan_id') === (string) $n->id)>
                                    {{ $n->nomor_nota }} — {{ $n->customer?->nama }} — {{ $namaBarang($fr?->barang) }}
                                </option>
                            @endforeach
                        </select>

                        <label class="form-label">Keluhan pelanggan <span class="text-danger">*</span></label>
                        <textarea name="keluhan" rows="3" maxlength="500" class="form-control" required
                                  placeholder="Contoh: engsel patah setelah dua minggu">{{ old('keluhan') }}</textarea>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    @if ($notaTersedia->isNotEmpty())
                        <button type="submit" class="btn btn-primary">Simpan Retur</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modal: Proses Retur (Owner) ===== --}}
@if ($isOwner)
<div class="modal fade" id="modalProses" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formProses">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Proses <span id="prosesNomor"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="p-2 mb-3" style="background:#efefef;border-radius:8px;font-size:.8rem">
                        <div>Pelanggan: <strong id="prosesPelanggan"></strong></div>
                        <div>Frame: <strong id="prosesFrame"></strong></div>
                        <div>Keluhan: <span id="prosesKeluhan"></span></div>
                    </div>

                    <label class="form-label">Hasil konfirmasi ke supplier <span class="text-danger">*</span></label>
                    <select name="status_konfirmasi" id="prosesKonf" class="form-select mb-3" required>
                        <option value="">Pilih hasil konfirmasi</option>
                        @foreach (\App\Models\ReturCustomer::KONFIRMASI as $kode => $label)
                            <option value="{{ $kode }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <label class="form-label">Tindak lanjut <span class="text-danger">*</span></label>
                    <select name="tindak_lanjut" id="prosesTindak" class="form-select mb-3" required>
                        <option value="">Pilih tindak lanjut</option>
                        @foreach (\App\Models\ReturCustomer::TINDAK_LANJUT as $kode => $label)
                            <option value="{{ $kode }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <div id="boxPengganti" class="d-none mb-3">
                        <label class="form-label">Frame pengganti dari stok toko <span class="text-danger">*</span></label>
                        <select name="barang_pengganti_id" id="prosesPengganti" class="form-select" disabled>
                            <option value="">Pilih frame</option>
                            @foreach ($frameStok as $f)
                                <option value="{{ $f->id }}">{{ $f->kode_model }} - {{ $f->merk }} (stok {{ $f->stok_tersedia }})</option>
                            @endforeach
                        </select>
                        <div class="form-text">Stok frame ini berkurang 1 dan HPP-nya dicatat sebagai kerugian operasional.</div>
                    </div>

                    <div id="boxRefund" class="d-none mb-3">
                        <label class="form-label">Nominal refund (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah_refund" id="prosesRefund" min="1" step="any" class="form-control" disabled>
                        <div class="form-text">Terisi otomatis dengan harga frame pada nota, boleh diubah.</div>
                    </div>

                    <label class="form-label">Catatan Owner (opsional)</label>
                    <input type="text" name="catatan_owner" maxlength="255" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnProses">Simpan Keputusan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('modalProses');
        if (!modal) return;

        const form        = document.getElementById('formProses');
        const selKonf     = document.getElementById('prosesKonf');
        const selTindak   = document.getElementById('prosesTindak');
        const boxPengganti = document.getElementById('boxPengganti');
        const boxRefund   = document.getElementById('boxRefund');
        const inPengganti = document.getElementById('prosesPengganti');
        const inRefund    = document.getElementById('prosesRefund');

        function atur() {
            // Tukar dari supplier hanya boleh kalau supplier masih punya stok
            const optSup = selTindak.querySelector('option[value="tukar_supplier"]');
            optSup.disabled = selKonf.value === 'supplier_habis';
            if (optSup.disabled && selTindak.value === 'tukar_supplier') selTindak.value = '';

            const t = selTindak.value;
            boxPengganti.classList.toggle('d-none', t !== 'tukar_stok_toko');
            boxRefund.classList.toggle('d-none', t !== 'refund');
            inPengganti.disabled = t !== 'tukar_stok_toko';
            inPengganti.required = t === 'tukar_stok_toko';
            inRefund.disabled = t !== 'refund';
            inRefund.required = t === 'refund';
        }

        modal.addEventListener('show.bs.modal', function (e) {
            const b = e.relatedTarget;
            form.action = b.dataset.action;
            document.getElementById('prosesNomor').textContent     = b.dataset.nomor;
            document.getElementById('prosesPelanggan').textContent = b.dataset.pelanggan;
            document.getElementById('prosesFrame').textContent     = b.dataset.frame;
            document.getElementById('prosesKeluhan').textContent   = b.dataset.keluhan;

            selKonf.value = '';
            selTindak.value = '';
            inPengganti.value = '';
            inRefund.value = b.dataset.refund || '';
            inRefund.max = b.dataset.maks || '';
            atur();
        });

        selKonf.addEventListener('change', atur);
        selTindak.addEventListener('change', atur);
        form.addEventListener('submit', function () {
            document.getElementById('btnProses').disabled = true;
        });
    })();
</script>
@endpush