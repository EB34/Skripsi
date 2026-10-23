@extends('layouts.app')

@section('title', 'Pembelian Supplier')

@push('styles')
<style>
    button.btn-abu { cursor: pointer; }
    .po-tabel th { background: #efefef; font-size: .78rem; text-align: center; vertical-align: middle; }
    .po-tabel td { font-size: .8rem; vertical-align: middle; }
    .status-po {
        display: inline-block; font-size: .7rem; font-weight: 600;
        padding: 3px 10px; border-radius: 6px; border: 1px solid;
    }
    .status-po.menunggu { background: #d6ecff; color: #0b4f8a; border-color: #8fc4f0; }
    .status-po.sebagian { background: #ffeea0; color: #7a5a00; border-color: #e6c84a; }
    .status-po.lengkap  { background: #d6f5d6; color: #1b7a1b; border-color: #8fd48f; }
    .btn-teal {
        background: #2a86a8; color: #fff; border: 0; border-radius: 6px;
        padding: 6px 12px; font-size: .78rem; font-weight: 600; text-decoration: none; cursor: pointer;
    }
    .btn-teal:hover { background: #226f8c; color: #fff; }
    .kotak-peringatan {
        background: #fff3b0; border: 1px solid #e0c34a; border-radius: 8px;
        padding: 10px 14px; font-size: .78rem;
    }
    .kotak-item { background: #efefef; border-radius: 10px; padding: 14px 18px; margin-bottom: 14px; }
    .sup-combo { position: relative; }
    .sup-list {
        position: absolute; left: 0; right: 0; top: 100%;
        z-index: 1060;
        margin-top: 4px;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 8px;
        max-height: 230px;
        overflow-y: auto;
        box-shadow: 0 6px 16px rgba(0, 0, 0, .15);
    }
    .sup-opt { padding: 8px 12px; font-size: .85rem; cursor: pointer; }
    .sup-opt small { display: block; color: #2a6fb5; font-size: .72rem; }
    .sup-opt:hover, .sup-opt.sorot { background: #fff3b0; }
    #sup-kosong { padding: 10px 12px; font-size: .8rem; color: #888; }
    .sup-tambah {
    position: sticky; bottom: 0;
    display: block;
    background: #fff;
    border-top: 1px solid #ddd;
    padding: 9px 12px;
    font-size: .85rem; font-weight: 600;
    color: #000; text-decoration: none;
}
.sup-tambah:hover { background: #ffe11a; color: #000; }
</style>
@endpush

@section('content')
@php
    $statusLabel = [
        'menunggu_pengiriman' => ['Menunggu Pengiriman', 'menunggu'],
        'diterima_sebagian'   => ['Diterima Sebagian / Ada Cacat', 'sebagian'],
        'diterima_lengkap'    => ['Diterima Lengkap', 'lengkap'],
    ];

    // True kalau form "Buat PO" tadi ditolak validasi, supaya popup-nya dibuka lagi
    $bukaModalBuat = old('_form') === 'buat';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Pembelian dari Supplier &amp; Penerimaan Fisik</h1>
    <button type="button" class="btn-abu" data-bs-toggle="modal" data-bs-target="#modalBuatPo">
        <i class="bi bi-plus-lg"></i> Buat Pesanan Pembelian
    </button>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold" style="font-size:.85rem">
        <i class="bi bi-journal-text"></i> Riwayat Surat Pesanan (PO) &amp; Status Penerimaan Barang
    </div>
    <div class="table-responsive">
        <table class="table po-tabel mb-0">
            <thead>
                <tr>
                    <th>No. PO &amp; Tanggal</th>
                    <th>Supplier</th>
                    <th>Daftar Item Dipesan</th>
                    <th>Total Tagihan</th>
                    <th>Status Fisik Barang</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pembelian as $p)
                    @php [$teks, $kelas] = $statusLabel[$p->status]; @endphp
                    <tr>
                        <td>
                            <div class="mono fw-semibold">{{ $p->nomor_po }}</div>
                            <div class="text-muted">{{ $p->tanggal->format('d M Y') }}</div>
                        </td>
                        <td>{{ $p->supplier->nama }}</td>
                        <td>
                            @foreach ($p->detail as $d)
                                <div>
                                    {{ $d->jumlah_dipesan }} pcs {{ $d->barang->merk }}
                                    <span class="mono text-muted">({{ $d->barang->kode_model }})</span>
                                    @if ($p->status !== 'menunggu_pengiriman')
                                        <div style="font-size:.7rem" class="text-muted">
                                            Diterima {{ $d->jumlah_diterima }} pcs,
                                            <span class="{{ $d->jumlah_cacat > 0 ? 'text-danger fw-semibold' : '' }}">
                                                cacat {{ $d->jumlah_cacat }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td class="text-end">Rp {{ number_format($p->total_tagihan, 0, ',', '.') }}</td>
                        <td class="text-center"><span class="status-po {{ $kelas }}">{{ $teks }}</span></td>
                        <td class="text-center">
                            @if ($p->status === 'menunggu_pengiriman')
                                <button type="button" class="btn-teal" data-bs-toggle="modal"
                                        data-bs-target="#modalTerima{{ $p->id }}">
                                    <i class="bi bi-clipboard-check"></i> Checklist Barang
                                </button>
                            @else
                                <a href="{{ route('pembelian.show', $p) }}" class="btn-abu">Lihat Hasil</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada pesanan pembelian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $pembelian->links() }}
</div>

{{-- ===== Popup: Buat Pesanan Pembelian ===== --}}
<div class="modal fade" id="modalBuatPo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('pembelian.store') }}">
                @csrf
                <input type="hidden" name="_form" value="buat">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-file-earmark-plus"></i> Buat Pesanan Pembelian
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    @if ($bukaModalBuat && $errors->any())
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
                            <div class="sup-combo" id="sup-combo">
                                {{-- Nilai yang dikirim ke server: id supplier --}}
                                <input type="hidden" name="supplier_id" id="sup-id" value="{{ old('supplier_id') }}">

                                <input type="text" id="sup-cari" class="form-control" autocomplete="off"
                                placeholder="Ketik nama perusahaan atau contact person...">

                                <div class="sup-list d-none" id="sup-list">
                                    @foreach ($supplier as $s)
                                    <div class="sup-opt" data-id="{{ $s->id }}"
                                    data-nama="{{ $s->nama_perusahaan }}"
                                    data-kontak="{{ $s->nama_kontak }}">
                                    {{ $s->nama_perusahaan }}
                                    @if ($s->nama_kontak)
                                    <small>{{ $s->nama_kontak }}</small>
                                    @endif
                                </div>
                                @endforeach
                                
                                <div id="sup-kosong" class="d-none">Supplier tidak ditemukan.</div>
                                <a href="{{ route('supplier.index') }}" class="sup-tambah">
                                    <i class="bi bi-plus-lg"></i> Tambah data supplier
                                </a>
                            </div>
                        </div>
                        
                        <div class="form-text text-danger d-none" id="sup-hint">Pilih supplier terlebih dahulu.</div>
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

                    <button type="button" id="tambah-baris" class="btn-abu">
                        <i class="bi bi-plus-lg"></i> Tambah Barang
                    </button>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-abu" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Popup checklist: satu popup per PO yang masih menunggu ===== --}}
@foreach ($pembelian as $p)
    @if ($p->status === 'menunggu_pengiriman')
        <div class="modal fade" id="modalTerima{{ $p->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('pembelian.terima', $p) }}"
                          onsubmit="return confirm('Setelah diverifikasi, hasil penerimaan tidak dapat diubah. Lanjutkan?')">
                        @csrf

                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold">
                                    <i class="bi bi-check-square text-primary"></i>
                                    Checklist Penerimaan Fisik &amp; Quality Control
                                </h5>
                                <div class="text-muted mono" style="font-size:.75rem">
                                    {{ $p->nomor_po }} &nbsp;|&nbsp; Supplier: {{ $p->supplier->nama }}
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="kotak-peringatan mono mb-3">
                                Periksa fisik kacamata. Unit yang dinyatakan cacat akan otomatis dipisahkan dari stok
                                dan diajukan retur ke supplier.
                            </div>

                            @foreach ($p->detail as $d)
                                <div class="kotak-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="mono text-success" style="font-size:.75rem">Kode Produk</div>
                                            <div class="fw-semibold">{{ $d->barang->merk }}
                                                <span class="mono text-muted">({{ $d->barang->kode_model }})</span>
                                            </div>
                                        </div>
                                        <div style="font-size:.8rem">
                                            <strong>Dipesan:</strong> <span class="mono">{{ $d->jumlah_dipesan }} pcs</span>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label" style="font-size:.8rem">Jumlah Fisik diterima:</label>
                                            <input type="number" name="item[{{ $d->id }}][diterima]"
                                                   min="0" max="{{ $d->jumlah_dipesan }}" required class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" style="font-size:.8rem">Jumlah Cacat:</label>
                                            <input type="number" name="item[{{ $d->id }}][cacat]"
                                                   min="0" value="0" required class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" style="font-size:.8rem">
                                                Keterangan cacat/kerusakan (Opsional):
                                            </label>
                                            <input type="text" name="item[{{ $d->id }}][keterangan]"
                                                   maxlength="255" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <label class="form-label" style="font-size:.8rem">Catatan Penerima:</label>
                            <textarea name="catatan_penerima" rows="3" class="form-control"></textarea>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-abu" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn-teal" style="padding:9px 16px">
                                <i class="bi bi-check-circle"></i> Verifikasi &amp; Masukkan ke Stok
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- Data untuk JavaScript dititipkan di atribut HTML (tanpa Blade di dalam tag script) --}}
@php
    $barangJs = $barang->map(fn ($b) => ['id' => $b->id, 'teks' => $b->kode_model . ' - ' . $b->merk])->values();
@endphp
<div id="data-pembelian" hidden
     data-barang="{{ json_encode($barangJs) }}"
     data-lama="{{ json_encode($bukaModalBuat ? array_values(old('items', [])) : []) }}"
     data-buka="{{ $bukaModalBuat ? '1' : '0' }}"></div>
@endsection

@push('scripts')
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

    // Kalau form Buat PO ditolak validasi, buka lagi popup-nya otomatis
    if (dataEl.dataset.buka === '1') {
        new bootstrap.Modal(document.getElementById('modalBuatPo')).show();
    }
</script>
<script>
(function () {
    var combo = document.getElementById('sup-combo');
    var input = document.getElementById('sup-cari');
    var hidden = document.getElementById('sup-id');
    var hint = document.getElementById('sup-hint');
    var list = document.getElementById('sup-list');
    var pesanKosong = document.getElementById('sup-kosong');
    var opsi = Array.prototype.slice.call(list.querySelectorAll('.sup-opt'));
    var form = combo.closest('form');
    var modalEl = combo.closest('.modal');
    var sorot = -1;

    function terlihat() {
        return opsi.filter(function (o) { return !o.classList.contains('d-none'); });
    }

    // Isi kolom ketik sesuai supplier yang benar-benar terpilih
    function sinkron() {
        var teks = '';
        opsi.forEach(function (o) {
            if (o.dataset.id === hidden.value) { teks = o.dataset.nama; }
        });
        input.value = teks;
        if (hidden.value !== '') { hint.classList.add('d-none'); }
    }

    function aturSorot(i) {
        var v = terlihat();
        v.forEach(function (o) { o.classList.remove('sorot'); });
        if (!v.length) { sorot = -1; return; }
        sorot = (i + v.length) % v.length;
        v[sorot].classList.add('sorot');
        v[sorot].scrollIntoView({ block: 'nearest' });
    }

    function saring(teks) {
        var q = teks.trim().toLowerCase();
        var ada = false;
        opsi.forEach(function (o) {
            var nama = o.dataset.nama.toLowerCase();
            var kontak = (o.dataset.kontak || '').toLowerCase();
            var cocok = !q || nama.indexOf(q) !== -1 || kontak.indexOf(q) !== -1;
            o.classList.toggle('d-none', !cocok);
            o.classList.remove('sorot');
            if (cocok) { ada = true; }
        });
        pesanKosong.classList.toggle('d-none', ada);
        sorot = -1;
    }

    function buka() { list.classList.remove('d-none'); }
    function tutup() { list.classList.add('d-none'); sinkron(); }
    function pilih(o) { hidden.value = o.dataset.id; tutup(); }

    input.addEventListener('focus', function () { saring(''); buka(); input.select(); });
    input.addEventListener('click', function () {
        if (list.classList.contains('d-none')) { saring(''); buka(); }
    });
    input.addEventListener('input', function () { saring(input.value); buka(); });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault(); buka(); aturSorot(sorot + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault(); buka(); aturSorot(sorot < 0 ? -1 : sorot - 1);
        } else if (e.key === 'Enter') {
            e.preventDefault();   // Enter tidak boleh mengirim form
            if (list.classList.contains('d-none')) { return; }
            var v = terlihat();
            var target = sorot >= 0 ? v[sorot] : v[0];
            if (target) { pilih(target); }
        } else if (e.key === 'Escape') {
            e.stopPropagation();  // Escape hanya menutup daftar, bukan seluruh popup
            tutup();
        }
    });

    opsi.forEach(function (o) {
        o.addEventListener('click', function () { pilih(o); });
    });

    list.addEventListener('mousedown', function (e) { e.preventDefault(); });

    document.addEventListener('click', function (e) {
        if (!combo.contains(e.target)) { tutup(); }
    });

    combo.addEventListener('focusout', function (e) {
        if (!combo.contains(e.relatedTarget)) { tutup(); }
    });

    // Supplier wajib dipilih sebelum form dikirim
    form.addEventListener('submit', function (e) {
        if (hidden.value === '') {
            e.preventDefault();
            hint.classList.remove('d-none');
            input.focus();
        }
    });

    // Popup ditutup: kosongkan pilihan supaya PO berikutnya mulai dari awal
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            hidden.value = '';
            sinkron();
        });
    }

    sinkron();
})();
</script>
@endpush