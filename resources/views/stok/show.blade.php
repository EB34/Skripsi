@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('stok.index') }}" class="text-decoration-none small">&larr; Kembali ke Monitoring Stok</a>
            <h4 class="mb-0">{{ $barang->kode_model }} &ndash; {{ $barang->merk }}</h4>
            <div class="text-muted">{{ $barang->kategori_label }}</div>
        </div>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalSesuaikan">
            Koreksi Stok
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
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
        <div class="col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="text-muted small">Nilai Persediaan</div>
                <div class="fs-4 fw-bold">Rp {{ number_format($nilai, 0, ',', '.') }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Stok per lokasi --}}
        <div class="col-md-7">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Stok per Lokasi</span>
                    @if($barang->stok_tersedia > 0)
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalPindah">
                            Pindah Lokasi
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
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

        {{-- Pengaturan barang --}}
        <div class="col-md-5">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Pengaturan Barang</div>
                <div class="card-body">
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
                </div>
            </div>
        </div>
    </div>

    {{-- Batch FIFO aktif --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold">Batch FIFO Aktif (urut dari yang terlama)</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal Masuk</th>
                        <th class="text-end">Diterima</th>
                        <th class="text-end">Sisa</th>
                        <th class="text-end">Harga Beli</th>
                        <th class="text-end">Nilai Sisa</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($batchAktif as $bt)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($bt->tanggal_masuk)->format('d/m/Y') }}</td>
                        <td class="text-end">{{ $bt->jumlah_diterima }}</td>
                        <td class="text-end">{{ $bt->sisa_jumlah }}</td>
                        <td class="text-end">Rp {{ number_format($bt->harga_beli, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($bt->sisa_jumlah * $bt->harga_beli, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada batch aktif.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Kartu stok --}}
    <div class="card shadow-sm">
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
        <div class="card-footer">{{ $mutasi->links() }}</div>
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

{{-- Modal koreksi stok (opname) --}}
<div class="modal fade" id="modalSesuaikan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('stok.sesuaikan', $barang->id) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Koreksi Stok (Opname)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Stok di sistem</label>
                    <input type="text" class="form-control" value="{{ $barang->stok_tersedia }}" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah fisik (hasil hitung)</label>
                    <input type="number" name="jumlah_fisik" min="0" max="{{ $barang->stok_tersedia }}"
                           value="{{ old('jumlah_fisik') }}" class="form-control" required>
                    <div class="form-text">Hanya boleh lebih kecil atau sama dengan stok sistem.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alasan</label>
                    <input type="text" name="alasan" maxlength="255" value="{{ old('alasan') }}"
                           class="form-control" placeholder="Contoh: barang hilang, selisih hitung" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning">Simpan Koreksi</button>
            </div>
        </form>
    </div>
</div>
@endsection