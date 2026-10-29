<div class="modal fade" id="modalLaporRusak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('stok.rusak.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Lapor Barang Rusak / Cacat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Barang</label>
                    <select name="barang_id" class="form-select" required>
                        <option value="">Pilih barang...</option>
                        @foreach($opsiBarang as $o)
                            <option value="{{ $o->id }}" @selected(old('barang_id') == $o->id)>
                                {{ $o->kode_model }} - {{ $o->merk }} (stok {{ $o->stok_tersedia }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah</label>
                    <input type="number" name="jumlah" min="1" value="{{ old('jumlah', 1) }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alasan kerusakan</label>
                    <input type="text" name="alasan" list="daftarAlasan" maxlength="255"
                           value="{{ old('alasan') }}" class="form-control"
                           placeholder="Pilih atau ketik alasan" required>
                    <datalist id="daftarAlasan">
                        <option value="Cacat Pabrik">
                        <option value="Rusak saat Display">
                        <option value="Rusak saat Pengiriman">
                        <option value="Rusak saat Pemasangan">
                    </datalist>
                </div>
                <div class="alert alert-warning small mb-0">
                    Barang akan dikeluarkan dari stok layak jual, lalu menunggu keputusan:
                    retur ke supplier atau write off.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Simpan Laporan</button>
            </div>
        </form>
    </div>
</div>