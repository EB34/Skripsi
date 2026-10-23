@php($c = $customer ?? null)

<div class="mb-3">
    <label class="form-label">Nama Customer</label>
    <input type="text" name="nama" value="{{ old('nama', $c->nama ?? '') }}"
        class="form-control @error('nama') is-invalid @enderror" required>
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">No. WhatsApp</label>
    <input type="text" name="no_whatsapp" value="{{ old('no_whatsapp', $c->no_whatsapp ?? '') }}"
        class="form-control @error('no_whatsapp') is-invalid @enderror" placeholder="081234567890">
    <div class="form-text">Boleh diketik dengan 0 atau +62, otomatis dirapikan ke format 62...</div>
    @error('no_whatsapp')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>