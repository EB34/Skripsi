@php($b = $barang ?? null)

<div class="mb-3">
    <label class="form-label">Kode Model</label>
    <input type="text" name="kode_model" value="{{ old('kode_model', $b->kode_model ?? '') }}"
           class="form-control @error('kode_model') is-invalid @enderror" required>
    @error('kode_model')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Jenis</label>
    <select name="jenis" class="form-select @error('jenis') is-invalid @enderror" required>
        <option value="">-- Pilih jenis --</option>
        @foreach (['frame' => 'Frame', 'lensa' => 'Lensa', 'softlens' => 'Softlens'] as $nilai => $label)
            <option value="{{ $nilai }}" @selected(old('jenis', $b->jenis ?? '') === $nilai)>{{ $label }}</option>
        @endforeach
    </select>
    @error('jenis')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Merk</label>
    <input type="text" name="merk" value="{{ old('merk', $b->merk ?? '') }}"
           class="form-control @error('merk') is-invalid @enderror" required>
    @error('merk')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Harga Jual (Rp)</label>
    <input type="number" name="harga_jual" min="0" step="any"
           value="{{ old('harga_jual', isset($b) ? (int) $b->harga_jual : '') }}"
           class="form-control @error('harga_jual') is-invalid @enderror" required>
    @error('harga_jual')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>