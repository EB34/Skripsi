@extends('layouts.app')

@section('title', 'Modul Retur')

@push('styles')
<style>
    .rt-banner {
        background: #fff3b0; border: 1px solid #e0c34a; border-radius: 10px;
        padding: 10px 16px; font-size: .8rem; margin-bottom: 14px;
    }
    .rt-filter { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .rt-tabel { background: #fff; border: 1px solid #d9d9d9; border-radius: 10px; overflow: hidden; }
    .rt-tabel table { margin: 0; font-size: .8rem; color: #1e1e1e; }
    .rt-tabel thead th { background: #d9d9d9; font-size: .75rem; font-weight: 600; border-bottom: 0; white-space: nowrap; }
    .rt-status {
        display: inline-block; font-size: .7rem; font-weight: 600;
        padding: 3px 10px; border-radius: 999px; border: 1px solid;
    }
    .rt-status.diajukan { background: #fff1cc; color: #8a5a00; border-color: #f0c35a; }
    .rt-status.dikirim  { background: #d6ecff; color: #0b4f8a; border-color: #8fc4f0; }
    .rt-status.selesai  { background: #d6f5d6; color: #1b7a1b; border-color: #8fd48f; }
    .rt-sub { font-size: .68rem; color: #666; }
    .rt-jenis { font-size: .68rem; color: #1c4e80; margin-top: 4px; }
    .rt-badge-custom {
        display: inline-block; font-size: .62rem; font-weight: 600; padding: 0 7px;
        border-radius: 6px; background: #e8dcff; color: #4b2a8a; border: 1px solid #c4aef0;
    }
    .rt-btn {
        display: inline-flex; align-items: center; gap: 6px;
        border: 0; border-radius: 6px; padding: 6px 12px;
        font-size: .74rem; font-weight: 600; text-decoration: none; cursor: pointer;
    }
    .rt-btn.teal { background: #2a86a8; color: #fff; }
    .rt-btn.teal:hover { background: #226f8c; color: #fff; }
    .rt-btn.abu { background: #d9d9d9; color: #1e1e1e; }
    .rt-btn.abu:hover { background: #c9c9c9; color: #000; }
    .rt-btn.hijau { background: #1f9d4a; color: #fff; }
    .rt-btn.hijau:hover { background: #188040; color: #fff; }
    .rt-kotak { background: #efefef; border-radius: 10px; padding: 10px 14px; margin-bottom: 12px; }
</style>
@endpush

@section('content')
@php
    $isOwner  = auth()->user()->isOwner();
    $rp       = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $bukaBuat = old('_form') === 'buat';
@endphp

<div class="container-fluid">
    @include('retur.header', ['aktif' => 'supplier'])

    @if ($isOwner && ($hitung['diajukan'] ?? 0) > 0)
        <div class="rt-banner">
            <i class="bi bi-exclamation-triangle"></i>
            Ada {{ $hitung['diajukan'] }} retur menunggu validasi Owner.
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="rt-filter mb-0">
            <a href="{{ route('retur.supplier') }}" class="chip {{ ! $status ? 'active' : '' }}">Semua</a>
            @foreach (\App\Models\ReturSupplier::STATUS as $kode => $label)
                <a href="{{ route('retur.supplier', ['status' => $kode]) }}"
                   class="chip {{ $status === $kode ? 'active' : '' }}">
                    {{ $label }} ({{ $hitung[$kode] ?? 0 }})
                </a>
            @endforeach
        </div>
        <button type="button" class="btn-abu" data-bs-toggle="modal" data-bs-target="#modalBuat">
            <i class="bi bi-plus-lg"></i> Buat Retur Baru
        </button>
    </div>

    <div class="rt-tabel">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No. Retur &amp; Tanggal</th>
                        <th>PO Asal &amp; Supplier</th>
                        <th>Barang Diretur</th>
                        <th>Status &amp; Jenis</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($daftar as $r)
                    @php
                        $supplier   = $r->pembelian?->supplier;
                        $namaKontak = $supplier ? $supplier->kontak->pluck('nama')->implode(', ') : '';
                        $invoice    = $r->pembelian?->nomor_invoice_supplier;

                        // Saran nominal potongan = harga beli di PO toko x jumlah retur (hanya dihitung untuk Owner).
                        // Lensa custom memakai baris PO yang tepat, bukan sekadar barang yang sama.
                        $saran = 0;
                        if ($isOwner) {
                            foreach ($r->detail as $d) {
                                $barisPo = $d->detail_pembelian_id
                                    ? $r->pembelian?->detail->firstWhere('id', $d->detail_pembelian_id)
                                    : $r->pembelian?->detail->firstWhere('barang_id', $d->barang_id);
                                $saran += (int) $d->jumlah * (float) ($barisPo?->harga_beli ?? 0);
                            }
                        }

                        $itemsJs = $r->detail->map(function ($d) {
                            $bp = $d->detailPembelian;
                            $custom = (bool) ($bp?->detail_penjualan_id);
                            $label = trim(($d->barang?->merk ?? '') . ' ' . ($d->barang?->kode_model ?? ''));
                            if ($custom) {
                                $label .= ' | ' . $bp->label_nota . ' | ' . ($bp->keterangan_ukuran ?: '-');
                            }

                            return [
                                'id'     => $d->id,
                                'label'  => $label,
                                'jumlah' => (int) $d->jumlah,
                                'custom' => $custom,
                            ];
                        })->values();
                    @endphp
                    <tr>
                        <td>
                            <div class="mono fw-bold">{{ $r->nomor_retur }}</div>
                            <div class="rt-sub">{{ $r->tanggal_format }}</div>
                            @if ($r->nomor_surat_jalan)
                                <div class="mono rt-sub">{{ $r->nomor_surat_jalan }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="mono">{{ $r->pembelian?->nomor_po ?? '-' }}</div>
                            <div class="fw-bold">{{ $supplier?->nama_perusahaan ?? '-' }}</div>
                            @if ($namaKontak !== '')
                                <div class="rt-sub">{{ $namaKontak }}</div>
                            @endif
                            <div class="rt-sub">Invoice supplier: {{ $invoice ?: 'belum diisi' }}</div>
                        </td>
                        <td>
                            @foreach ($r->detail as $d)
                                <div>
                                    {{ $d->jumlah }} pcs {{ $d->barang?->merk ?? '-' }}
                                    <span class="mono rt-sub">({{ $d->barang?->kode_model }})</span>
                                    @if ($d->detailPembelian?->detail_penjualan_id)
                                        <span class="rt-badge-custom">Custom</span>
                                        <div class="rt-sub">{{ $d->detailPembelian->label_nota }}</div>
                                    @endif
                                    <span class="rt-sub">- {{ \App\Models\ReturSupplier::ALASAN[$d->alasan] ?? $d->alasan }}</span>
                                    @if ($d->keterangan)
                                        <div class="rt-sub">{{ $d->keterangan }}</div>
                                    @endif
                                    @if ($r->status === 'selesai' && $r->jenis_retur === 'tukar_barang')
                                        <div class="rt-sub">Pengganti diterima {{ $d->jumlah_pengganti }}, cacat {{ $d->jumlah_pengganti_cacat }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td>
                            <span class="rt-status {{ $r->status }}">{{ $r->status_label }}</span>
                            <div class="rt-jenis">{{ $r->jenis_label }}</div>
                            @if ($isOwner && $r->jenis_retur === 'potong_tagihan' && $r->status === 'selesai')
                                <div class="rt-sub">Potongan tagihan {{ $rp($r->nilai_potongan) }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1 justify-content-center">
                                {{-- Validasi: hanya Owner --}}
                                @if ($r->status === 'diajukan')
                                    @if ($isOwner)
                                        <button type="button" class="rt-btn teal" data-bs-toggle="modal" data-bs-target="#modalValidasi"
                                                data-action="{{ route('retur.supplier.kirim', $r->id) }}"
                                                data-nomor="{{ $r->nomor_retur }}"
                                                data-jenis="{{ $r->jenis_retur }}"
                                                data-invoice="{{ $invoice }}">
                                            <i class="bi bi-check2-square"></i> Validasi &amp; Terbitkan Surat Jalan
                                        </button>
                                    @else
                                        <span class="rt-sub">Menunggu validasi Owner</span>
                                    @endif
                                @endif

                                @if ($r->nomor_surat_jalan)
                                    <a href="{{ route('retur.supplier.suratjalan', $r->id) }}" target="_blank" class="rt-btn abu">
                                        <i class="bi bi-printer"></i> Surat Jalan
                                    </a>
                                @endif

                                {{-- Jenis ganti barang: Owner dan Petugas mengecek barang pengganti --}}
                                @if ($r->status === 'dikirim' && $r->jenis_retur === 'tukar_barang')
                                    <button type="button" class="rt-btn hijau" data-bs-toggle="modal" data-bs-target="#modalPengganti"
                                            data-action="{{ route('retur.supplier.pengganti', $r->id) }}"
                                            data-nomor="{{ $r->nomor_retur }}"
                                            data-items="{{ json_encode($itemsJs) }}">
                                        <i class="bi bi-clipboard-check"></i> Terima Pengganti
                                    </button>
                                @endif

                                {{-- Jenis potong tagihan: hanya Owner (ada nominal uang) --}}
                                @if ($r->status === 'dikirim' && $r->jenis_retur === 'potong_tagihan')
                                    @if ($isOwner)
                                        <button type="button" class="rt-btn hijau" data-bs-toggle="modal" data-bs-target="#modalPotong"
                                                data-action="{{ route('retur.supplier.selesai', $r->id) }}"
                                                data-nomor="{{ $r->nomor_retur }}"
                                                data-saran="{{ (int) round($saran) }}">
                                            <i class="bi bi-check-circle"></i> Potong Tagihan &amp; Selesai
                                        </button>
                                    @else
                                        <span class="rt-sub">Menunggu Owner</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted py-4 text-center">Belum ada retur ke supplier.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

{{-- ===== Modal: Buat Retur Baru (Owner dan Petugas) ===== --}}
<div class="modal fade" id="modalBuat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('retur.supplier.store') }}" id="formBuat">
                @csrf
                <input type="hidden" name="_form" value="buat">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Retur ke Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">PO asal (supplier)</label>
                    <select name="pembelian_id" id="bPo" class="form-select mb-3" required>
                        <option value="">Pilih PO asal</option>
                    </select>

                    <label class="form-label">Barang</label>
                    {{-- Pilihan ini tidak dikirim langsung: JavaScript mengisi dua kolom tersembunyi di bawahnya --}}
                    <select id="bBarang" class="form-select mb-1" required disabled>
                        <option value="">Pilih PO dulu</option>
                    </select>
                    <input type="hidden" name="barang_id" id="bBarangId" value="{{ old('barang_id') }}">
                    <input type="hidden" name="detail_pembelian_id" id="bDetailId" value="{{ old('detail_pembelian_id') }}">
                    <div class="form-text mb-3" id="bStok">Stok layak jual akan berkurang sebanyak jumlah yang diretur.</div>

                    <label class="form-label">Jumlah</label>
                    <input type="number" name="jumlah" id="bJumlah" min="1" value="{{ old('jumlah', 1) }}" class="form-control mb-3" required>

                    <label class="form-label">Alasan retur</label>
                    <select name="alasan" class="form-select mb-3" required>
                        <option value="">Pilih alasan</option>
                        @foreach (\App\Models\ReturSupplier::ALASAN as $kode => $label)
                            <option value="{{ $kode }}" @selected(old('alasan') === $kode)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <label class="form-label">Keterangan (tercetak di surat jalan) <span class="text-danger">*</span></label>
                    <input type="text" name="keterangan" maxlength="200" value="{{ old('keterangan') }}" class="form-control mb-3"
                           placeholder="Contoh: warna tidak sesuai pesanan" required>

                    <label class="form-label d-block">Jenis retur <span class="text-danger">*</span></label>
                    @foreach (\App\Models\ReturSupplier::JENIS_DETAIL as $kode => $teks)
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="jenis_retur" id="bJenis{{ $loop->index }}"
                                   value="{{ $kode }}" @checked(old('jenis_retur') === $kode) required>
                            <label class="form-check-label" for="bJenis{{ $loop->index }}">{{ $teks }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnBuat">Buat Retur</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modal: Terima Pengganti (checklist kondisi barang) ===== --}}
<div class="modal fade" id="modalPengganti" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" id="formPengganti"
                  onsubmit="return confirm('Setelah diverifikasi, hasil penerimaan pengganti tidak dapat diubah. Lanjutkan?')">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Checklist Barang Pengganti <span id="pgNomor"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="rt-banner">
                        Periksa fisik barang pengganti. Unit bagus masuk stok (lensa custom tidak masuk stok, HPP nota yang terisi).
                        Unit cacat otomatis diajukan sebagai retur baru.
                    </div>
                    <div id="pgItems"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Verifikasi &amp; Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($isOwner)
{{-- ===== Modal: Validasi retur + terbitkan surat jalan (Owner) ===== --}}
<div class="modal fade" id="modalValidasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formValidasi">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Validasi <span id="vNomor"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Jenis retur <span class="text-danger">*</span></label>
                    <select name="jenis_retur" id="vJenis" class="form-select mb-3" required>
                        <option value="">Pilih jenis retur</option>
                        @foreach (\App\Models\ReturSupplier::JENIS_DETAIL as $kode => $teks)
                            <option value="{{ $kode }}">{{ $teks }}</option>
                        @endforeach
                    </select>

                    <label class="form-label">No. invoice dari supplier <span class="text-danger">*</span></label>
                    <input type="text" name="nomor_invoice_supplier" id="vInvoice" maxlength="100" class="form-control" required>
                    <div class="form-text">Dicetak di surat jalan sebagai No. Referensi Invoice. Terisi otomatis kalau sudah diisi saat penerimaan barang.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Validasi &amp; Terbitkan Surat Jalan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modal: Potong tagihan (Owner) ===== --}}
<div class="modal fade" id="modalPotong" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formPotong">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Potong Tagihan <span id="ptNomor"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Nominal potongan / uang kembali (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="nilai_potongan" id="ptNilai" min="1" step="any" class="form-control" required>
                    <div class="form-text">
                        Terisi otomatis dari harga beli di PO toko x jumlah retur, boleh diubah sesuai nota kredit supplier.
                        Total tagihan PO akan berkurang sebesar nominal ini dan tidak dicatat sebagai kerugian.
                        Kalau yang diretur lensa custom, lensanya bisa dipesan ulang lewat PO baru.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan &amp; Selesai</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Data untuk JavaScript dititipkan di atribut HTML --}}
<div id="dataRetur" hidden
     data-po="{{ json_encode($poOpsi) }}"
     data-buka="{{ $bukaBuat ? '1' : '0' }}"
     data-lama="{{ json_encode(['pembelian_id' => old('pembelian_id'), 'barang_id' => old('barang_id'), 'detail_pembelian_id' => old('detail_pembelian_id')]) }}"></div>
@endsection

@push('scripts')
<script>
(function () {
    const dataEl = document.getElementById('dataRetur');
    const poOpsi = JSON.parse(dataEl.dataset.po || '[]');

    // ----- Buat Retur Baru -----
    const selPo     = document.getElementById('bPo');
    const selBarang = document.getElementById('bBarang');
    const hBarang   = document.getElementById('bBarangId');
    const hDetail   = document.getElementById('bDetailId');
    const infoStok  = document.getElementById('bStok');
    const inJumlah  = document.getElementById('bJumlah');
    const formBuat  = document.getElementById('formBuat');

    poOpsi.forEach(function (p) {
        const o = document.createElement('option');
        o.value = p.id;
        o.textContent = p.label;
        selPo.appendChild(o);
    });

    // Kunci pilihan: "d<id baris PO>" untuk lensa custom, "b<id barang>" untuk barang stok
    function kunciDari(barangId, detailId) {
        if (detailId) { return 'd' + detailId; }
        return barangId ? 'b' + barangId : '';
    }

    function isiBarang(kunciTerpilih) {
        const po = poOpsi.find(function (p) { return String(p.id) === selPo.value; });
        selBarang.innerHTML = '<option value="">' + (po ? 'Pilih barang' : 'Pilih PO dulu') + '</option>';
        selBarang.disabled = !po;
        if (po) {
            po.items.forEach(function (it) {
                const o = document.createElement('option');
                o.value = it.key;
                o.textContent = it.custom ? it.label : it.label + ' (stok ' + it.stok + ')';
                o.dataset.barang = it.barang_id;
                o.dataset.detail = it.detail_id || '';
                o.dataset.custom = it.custom ? '1' : '0';
                o.dataset.stok = it.stok;
                selBarang.appendChild(o);
            });
        }
        selBarang.value = kunciTerpilih || '';
        aturStok();
    }

    function aturStok() {
        const opt = selBarang.options[selBarang.selectedIndex];
        const ada = opt && opt.value !== '';

        // Dua kolom tersembunyi inilah yang dikirim ke server
        hBarang.value = ada ? opt.dataset.barang : '';
        hDetail.value = ada ? opt.dataset.detail : '';

        if (ada && opt.dataset.custom === '1') {
            // Lensa custom: satu biji per retur, tidak ada stok yang berubah
            infoStok.textContent = 'Lensa custom tidak punya stok, jadi stok tidak berubah. HPP nota kembali ke 0 sampai lensa pengganti atau PO baru diterima.';
            inJumlah.value = 1;
            inJumlah.readOnly = true;
            inJumlah.removeAttribute('max');
            return;
        }

        inJumlah.readOnly = false;
        const stok = ada && opt.dataset.stok ? parseInt(opt.dataset.stok) : null;
        if (stok === null) {
            infoStok.textContent = 'Stok layak jual akan berkurang sebanyak jumlah yang diretur.';
            inJumlah.removeAttribute('max');
        } else {
            infoStok.textContent = 'Stok layak jual saat ini ' + stok + '. Stok berkurang sebanyak jumlah yang diretur.';
            inJumlah.max = stok;
        }
    }

    selPo.addEventListener('change', function () { isiBarang(''); });
    selBarang.addEventListener('change', aturStok);
    formBuat.addEventListener('submit', function () { document.getElementById('btnBuat').disabled = true; });

    // Popup ditolak server: buka lagi dengan isian tadi
    if (dataEl.dataset.buka === '1') {
        const lama = JSON.parse(dataEl.dataset.lama || '{}');
        selPo.value = lama.pembelian_id || '';
        isiBarang(kunciDari(lama.barang_id, lama.detail_pembelian_id));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBuat')).show();
    }

    // ----- Terima Pengganti -----
    const modalPg = document.getElementById('modalPengganti');
    modalPg.addEventListener('show.bs.modal', function (e) {
        const b = e.relatedTarget;
        document.getElementById('formPengganti').action = b.dataset.action;
        document.getElementById('pgNomor').textContent = b.dataset.nomor;

        const wadah = document.getElementById('pgItems');
        wadah.innerHTML = '';

        JSON.parse(b.dataset.items || '[]').forEach(function (it) {
            const kotak = document.createElement('div');
            kotak.className = 'rt-kotak';

            const judul = document.createElement('div');
            judul.className = 'fw-semibold';
            judul.textContent = it.label + ' (dikembalikan ' + it.jumlah + ' pcs)';
            kotak.appendChild(judul);

            if (it.custom) {
                const catatan = document.createElement('div');
                catatan.className = 'rt-sub';
                catatan.textContent = 'Lensa custom: tidak masuk stok. Kalau pengganti bagus, HPP nota terisi dari harga beli PO.';
                kotak.appendChild(catatan);
            }

            const grid = document.createElement('div');
            grid.className = 'row g-2 mt-1';
            grid.innerHTML =
                '<div class="col-md-6"><label class="form-label" style="font-size:.8rem">Jumlah pengganti diterima:</label>' +
                '<input type="number" name="item[' + it.id + '][diterima]" min="0" max="' + it.jumlah + '" value="0" required class="form-control"></div>' +
                '<div class="col-md-6"><label class="form-label" style="font-size:.8rem">Jumlah cacat:</label>' +
                '<input type="number" name="item[' + it.id + '][cacat]" min="0" value="0" required class="form-control"></div>' +
                '<div class="col-12"><label class="form-label" style="font-size:.8rem">Keterangan cacat (wajib kalau ada yang cacat):</label>' +
                '<input type="text" name="item[' + it.id + '][keterangan]" maxlength="200" class="form-control"></div>';
            kotak.appendChild(grid);

            wadah.appendChild(kotak);
        });
    });

    // ----- Validasi (Owner) -----
    const modalVal = document.getElementById('modalValidasi');
    if (modalVal) {
        modalVal.addEventListener('show.bs.modal', function (e) {
            const b = e.relatedTarget;
            document.getElementById('formValidasi').action = b.dataset.action;
            document.getElementById('vNomor').textContent  = b.dataset.nomor;
            document.getElementById('vJenis').value        = b.dataset.jenis || '';
            document.getElementById('vInvoice').value      = b.dataset.invoice || '';
        });
    }

    // ----- Potong tagihan (Owner) -----
    const modalPt = document.getElementById('modalPotong');
    if (modalPt) {
        modalPt.addEventListener('show.bs.modal', function (e) {
            const b = e.relatedTarget;
            document.getElementById('formPotong').action = b.dataset.action;
            document.getElementById('ptNomor').textContent = b.dataset.nomor;
            document.getElementById('ptNilai').value = b.dataset.saran || '';
        });
    }
})();
</script>
@endpush