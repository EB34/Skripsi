@extends('layouts.app')

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    // Hanya Owner yang boleh mengubah batas stok menipis dan melihat harga beli/nilai persediaan
    $isOwner        = $user->isOwner();
    $bolehAturBatas = $isOwner;
@endphp

<style>
    .btn-kembali {
        background-color: #dadada;
        border: none;
        border-radius: 8px;
        color: #212529;
        padding: 8px 16px;
        transition: background-color 0.15s ease-in-out;
    }
    .btn-kembali:hover {
        background-color: #bdbdbd;
        color: #212529;
    }
    .ukuran-lensa { font-weight: 600; color: #1f5f87; }

    /* .tabel-abu-stok {
            background: #fff;
            border: 1px solid #d9d9d9;
            overflow: hidden;
        }
        .tabel-abu-stok table {
            margin: 0;
            font-size: .8rem;
            color: #1e1e1e;
            --bs-table-bg: transparent;
        }
        .tabel-abu-stok thead th {
            background: #ececec;
            font-size: .8rem;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
            border-bottom: 1px solid #d9d9d9;
            border-left: 1px solid #d9d9d9;
            padding: 14px 10px;
            white-space: nowrap;
        }
        .tabel-abu-stok tbody td {
            text-align: center;
            vertical-align: middle;
            border-left: 1px solid #e3e3e3;
            border-bottom: 1px solid #e3e3e3;
            padding: 14px 10px;
        }
        .tabel-abu-stok thead th:first-child,
        .tabel-abu-stok tbody td:first-child { border-left: 0; }
        .tabel-abu-stok tbody tr:last-child td { border-bottom: 0; } */
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('stok.index') }}" class="btn btn-kembali mb-2">&larr; Kembali ke Monitoring Stok</a>
            <h4 class="mb-0">{{ $barang->kode_model }} &ndash; {{ $barang->merk }}</h4>
            <div class="text-muted">
                {{ $barang->kategori_label }}
                @if ($barang->ukuran_label)
                    &middot; <span class="ukuran-lensa">{{ $barang->ukuran_label }}</span>
                @endif
            </div>
        </div>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalSesuaikan">
            Koreksi Stok
        </button>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="text-muted small">Stok Tersedia</div>
                <div class="fs-4 fw-bold">{{ $barang->stok_tersedia }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="text-muted small">Status</div>
                <div class="fs-5 fw-bold text-capitalize">{{ $barang->status_stok }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="text-muted small">Stok Rusak (menunggu tindak lanjut)</div>
                <div class="fs-4 fw-bold">{{ $barang->stok_rusak }}</div>
            </div></div>
        </div>
        @if ($isOwner)
            <div class="col-md-3">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small">Nilai Persediaan</div>
                    <div class="fs-4 fw-bold">Rp {{ number_format($nilai, 0, ',', '.') }}</div>
                </div></div>
            </div>
        @endif
    </div>

    <div class="row g-3 mb-3">
        {{-- Stok per lokasi --}}
        <div class="col-md-7">
            <div class="tabel-abu card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Stok per Lokasi</span>
                    @if($barang->stok_tersedia > 0)
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalPindah">
                            Pindah Lokasi
                        </button>
                    @endif
                </div>
                <div class="tabel-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Lokasi</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($daftarLokasi as $l)
                            <tr>
                                <td>
                                    {{ $l->nama }}
                                    @if($l->is_gudang)<span class="badge bg-secondary">Gudang</span>@endif
                                </td>
                                <td class="text-end">{{ $petaLokasi[$l->id] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">Belum ada lokasi. Tambahkan di Kelola Lokasi.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if((int) $petaLokasi->sum() !== (int) $barang->stok_tersedia)
                    <div class="card-footer small text-danger">
                        Jumlah per lokasi ({{ (int) $petaLokasi->sum() }}) tidak sama dengan stok tersedia
                        ({{ $barang->stok_tersedia }}). Data stok per lokasi belum sinkron.
                    </div>
                @endif
            </div>
        </div>

        {{-- Pengaturan barang: batas stok menipis hanya bisa diubah Owner --}}
        <div class="col-md-5">
            <div class="tabel-abu card shadow-sm h-100">
                <div class="card-header fw-semibold">Pengaturan Barang</div>
                <div class="card-body">
                    @if($bolehAturBatas)
                        <form method="POST" action="{{ route('stok.pengaturan', $barang->id) }}">
                            @csrf
                            @method('PATCH')
                            <label class="form-label">Batas Stok Menipis</label>
                            <div class="d-flex gap-2">
                                <input type="number" name="stok_minimum" min="0" max="100000"
                                value="{{ old('stok_minimum', $barang->stok_minimum) }}" class="form-control" required>
                                <button class="btn btn-outline-secondary">Simpan</button>
                            </div>
                            <div class="form-text">Dianggap menipis jika stok &le; angka ini.</div>
                        </form>
                    @else
                        <label class="form-label">Batas Stok Menipis</label>
                        <input type="text" class="form-control" value="{{ $barang->stok_minimum }}" disabled>
                        <div class="form-text">
                            Dianggap menipis jika stok &le; angka ini. Hanya Owner yang dapat mengubah batas ini.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Batch FIFO aktif (harga beli dan nilai sisa hanya untuk Owner) --}}
    <div class="tabel-abu card shadow-sm mb-3">
        <div class="card-header fw-semibold">Batch FIFO Aktif (urut dari yang terlama)</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal Masuk</th>
                        <th>Diterima</th>
                        <th>Sisa</th>
                        @if ($isOwner)
                            <th>Harga Beli</th>
                            <th>Nilai Sisa</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @forelse($batchAktif as $bt)
                    <tr>
                        <td>
                            {{ \Carbon\Carbon::parse($bt->tanggal_masuk)->format('d/m/Y') }}
                            @if(!$bt->pembelian_id)
                                <span class="badge bg-warning text-dark">Koreksi</span>
                            @endif
                        </td>
                        <td>{{ $bt->jumlah_diterima }}</td>
                        <td>{{ $bt->sisa_jumlah }}</td>
                        @if ($isOwner)
                            <td>Rp {{ number_format($bt->harga_beli, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($bt->sisa_jumlah * $bt->harga_beli, 0, ',', '.') }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $isOwner ? 5 : 3 }}" class="text-center text-muted py-3">Tidak ada batch aktif.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Kartu stok --}}
    <div class="tabel-abu card shadow-sm">
        <div class="card-header fw-semibold">Kartu Stok (Riwayat Mutasi Barang Ini)</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis Mutasi</th>
                        <th class="text-center">Perubahan</th>
                        <th class="text-center">Stok Akhir</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($mutasi as $m)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($m->tanggal)->format('d/m/Y H.i') }}</td>
                        <td>{{ $m->jenis_label }}</td>
                        <td class="text-center">
                            @if($m->jumlah > 0)
                                <span class="text-success fw-semibold">+{{ $m->jumlah }}</span>
                            @elseif($m->jumlah < 0)
                                <span class="text-danger fw-semibold">{{ $m->jumlah }}</span>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $m->saldo_setelah }}</td>
                        <td class="small">{{ $m->keterangan_tampil }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada mutasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
            <!-- <div class="card-footer small text-muted">
                Total {{ $mutasi->total() }} catatan mutasi. (Halaman {{ $mutasi->currentPage() }} dari {{ $mutasi->lastPage() }})
            </div> -->
            <!-- @if($mutasi->hasPages()) -->
            <div class="card-footer">{{ $mutasi->links() }}</div>
            <!-- @endif -->
    </div>
</div>

{{-- Modal pindah lokasi --}}
<div class="modal fade" id="modalPindah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('stok.pindah', $barang->id) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Pindah Lokasi Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Barang yang dipindah (supaya jelas ukuran yang mana) --}}
                <div class="alert alert-light border small">
                    <div class="fw-semibold">{{ $barang->kode_model }} &ndash; {{ $barang->merk }}</div>
                    @if ($barang->ukuran_label)
                        <div class="ukuran-lensa">{{ $barang->ukuran_label }}</div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Dari lokasi</label>
                    <select name="dari_lokasi_id" class="form-select" required>
                        <option value="">Pilih lokasi asal...</option>
                        @foreach($daftarLokasi as $l)
                            @if(($petaLokasi[$l->id] ?? 0) > 0)
                                <option value="{{ $l->id }}" @selected(old('dari_lokasi_id') == $l->id)>
                                    {{ $l->nama }} (ada {{ $petaLokasi[$l->id] }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ke lokasi</label>
                    <select name="ke_lokasi_id" class="form-select" required>
                        <option value="">Pilih lokasi tujuan...</option>
                        @foreach($daftarLokasi as $l)
                            <option value="{{ $l->id }}" @selected(old('ke_lokasi_id') == $l->id)>{{ $l->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah</label>
                    <input type="number" name="jumlah" min="1" value="{{ old('jumlah', 1) }}" class="form-control" required>
                    <div class="form-text">Jumlahnya bebas, tidak mengikuti FIFO. Harga dan HPP tidak berubah.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan (opsional)</label>
                    <input type="text" name="catatan" maxlength="150" value="{{ old('catatan') }}" class="form-control"
                           placeholder="Contoh: isi ulang etalase">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Pindahkan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal koreksi stok (opname): boleh mengurangi maupun menambah (menambah hanya Owner, karena butuh harga beli) --}}
<div class="modal fade" id="modalSesuaikan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('stok.sesuaikan', $barang->id) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Koreksi Stok (Opname)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Barang yang dikoreksi: kode, merk, jenis, dan ukuran (SPH/CYL) --}}
                <div class="alert alert-light border small">
                    <div class="fw-semibold">{{ $barang->kode_model }} &ndash; {{ $barang->merk }}</div>
                    <div class="text-muted">{{ $barang->kategori_label }}</div>
                    @if ($barang->ukuran_label)
                        <div class="ukuran-lensa">{{ $barang->ukuran_label }}</div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Stok di sistem</label>
                    <input type="text" class="form-control" value="{{ $barang->stok_tersedia }}" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah fisik (hasil hitung)</label>
                    <input type="number" name="jumlah_fisik" id="fisik" min="0"
                           data-stok="{{ $barang->stok_tersedia }}"
                           data-owner="{{ $isOwner ? 1 : 0 }}"
                           value="{{ old('jumlah_fisik') }}" class="form-control" required>
                    <div class="form-text">
                        @if ($isOwner)
                            Boleh lebih kecil (barang hilang, selisih hitung) atau lebih besar (salah hitung) dari stok sistem.
                        @else
                            Boleh lebih kecil (barang hilang, selisih hitung) dari stok sistem. Menambah stok lewat koreksi hanya bisa dilakukan Owner.
                        @endif
                    </div>
                </div>

                @if ($isOwner)
                    {{-- Hanya tampil kalau jumlah fisik lebih besar dari stok sistem --}}
                    <div class="mb-3 d-none" id="blok-harga">
                        <label class="form-label">Harga beli per unit (Rp)</label>
                        <input type="number" name="harga_beli" id="harga-beli" min="0" step="any"
                               value="{{ old('harga_beli', $hargaTerakhir) }}" class="form-control"
                               placeholder="Belum ada riwayat harga, isi manual" disabled>
                        <div class="form-text">
                            Stok bertambah <strong id="info-tambah">0</strong> unit. Dicatat sebagai batch FIFO baru
                            dengan harga ini dan masuk ke lokasi gudang. Terisi otomatis dari harga batch terbaru, boleh diubah.
                        </div>
                    </div>
                @else
                    {{-- Petugas: penambahan stok ditolak, minta Owner yang melakukan --}}
                    <div class="alert alert-warning small d-none" id="blok-tolak">
                        Jumlah fisik lebih besar dari stok sistem. Penambahan stok lewat koreksi hanya bisa dilakukan Owner.
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Alasan</label>
                    <input type="text" name="alasan" maxlength="150" value="{{ old('alasan') }}"
                           class="form-control" placeholder="Contoh: barang hilang, salah hitung awal" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning" id="btn-simpan-koreksi">Simpan Koreksi</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var fisik  = document.getElementById('fisik');
    var owner  = fisik.dataset.owner === '1';
    var blok   = document.getElementById('blok-harga');   // hanya ada untuk Owner
    var harga  = document.getElementById('harga-beli');   // hanya ada untuk Owner
    var info   = document.getElementById('info-tambah');  // hanya ada untuk Owner
    var tolak  = document.getElementById('blok-tolak');   // hanya ada untuk Petugas
    var simpan = document.getElementById('btn-simpan-koreksi');
    var stok   = parseInt(fisik.dataset.stok, 10);

    function perbarui() {
        var nilai  = parseInt(fisik.value, 10);
        var tambah = (!isNaN(nilai) && nilai > stok) ? nilai - stok : 0;

        if (owner) {
            // Kolom harga hanya aktif kalau jumlah fisik lebih besar dari stok sistem
            blok.classList.toggle('d-none', tambah === 0);
            harga.disabled = (tambah === 0);
            harga.required = (tambah > 0);
            info.textContent = tambah;
        } else {
            // Petugas tidak boleh menambah stok
            tolak.classList.toggle('d-none', tambah === 0);
            simpan.disabled = (tambah > 0);
        }
    }

    fisik.addEventListener('input', perbarui);
    perbarui();
})();
</script>
@endpush