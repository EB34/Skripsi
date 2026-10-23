@extends('layouts.app')

@section('title', 'Transaksi Penjualan')

@push('styles')
<style>
    .pos-wrap {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 380px;
        gap: 22px;
        align-items: start;
    }
    button.chip { border: 0; cursor: pointer; }

    /* Kartu barang */
    .pos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 14px;
        margin-top: 16px;
    }
    .pos-card {
        background: var(--abu-kartu);
        border-radius: 10px;
        padding: 12px 14px;
        cursor: pointer;
        border: 2px solid transparent;
        user-select: none;
    }
    .pos-card:hover { border-color: var(--kuning); }
    .pos-card.habis { opacity: .5; cursor: not-allowed; }
    .pos-card.habis:hover { border-color: transparent; }
    .pos-card .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .pos-card .nama { font-weight: 700; font-size: .88rem; }
    .pos-card .info { font-size: .68rem; color: #555; margin-bottom: 8px; }
    .pos-card .bawah { border-top: 1px solid #d0d0d0; padding-top: 6px; display: flex; justify-content: space-between; align-items: center; }

    /* Panel keranjang */
    .pos-keranjang {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 18px;
        position: sticky;
        top: 90px;
    }
    .pos-keranjang h2 { font-size: 1.05rem; font-weight: 700; margin: 0 0 14px; }
    .area-keranjang {
        background: #efefef;
        border-radius: 10px;
        min-height: 150px;
        max-height: 280px;
        overflow-y: auto;
        padding: 10px;
        margin: 12px 0 16px;
    }
    #keranjang-kosong { text-align: center; color: #888; font-size: .8rem; padding: 40px 12px; }
    .cart-row {
        display: flex; align-items: center; gap: 8px;
        background: #fff; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px;
    }
    .cart-info { flex: 1; min-width: 0; }
    .cart-nama { font-size: .8rem; font-weight: 600; }
    .cart-harga { font-size: .7rem; color: #555; }
    .cart-qty { display: flex; align-items: center; gap: 6px; font-size: .8rem; font-weight: 600; }

    /* Pencarian customer (kolom ketik + daftar pilihan) */
    .cust-combo { position: relative; }
    .cust-list {
        position: absolute; left: 0; right: 0; top: 100%;
        z-index: 20;
        margin-top: 4px;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 8px;
        max-height: 230px;
        overflow-y: auto;
        box-shadow: 0 6px 16px rgba(0, 0, 0, .15);
    }
    .cust-opt { padding: 8px 12px; font-size: .8rem; cursor: pointer; }
    .cust-opt small { display: block; color: #2a6fb5; font-size: .7rem; }
    .cust-opt:hover, .cust-opt.sorot { background: #fff3b0; }
    #cust-kosong { padding: 10px 12px; font-size: .78rem; color: #888; }
    .cust-tambah {
        position: sticky; bottom: 0;
        display: block;
        background: #fff;
        border-top: 1px solid #ddd;
        padding: 9px 12px;
        font-size: .8rem; font-weight: 600;
        color: #000; text-decoration: none;
    }
    .cust-tambah:hover { background: #ffe11a; color: #000; }

    /* Metode pembayaran */
    .metode-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 8px 0 14px; }
    .metode-opt input { display: none; }
    .metode-opt span {
        display: flex; align-items: center; gap: 8px;
        border: 1px solid #bbb; border-radius: 8px;
        padding: 9px 10px; font-size: .75rem; cursor: pointer; background: #fff;
    }
    .metode-opt input:checked + span { border-color: #d9a400; background: #fff3b0; font-weight: 600; }

    .box-tunai { background: #efefef; border-radius: 10px; padding: 12px; margin-bottom: 14px; }
    .baris-total { display: flex; justify-content: space-between; font-size: .8rem; margin-bottom: 6px; }
    .baris-total.besar { font-size: 1rem; font-weight: 800; margin: 10px 0 12px; }
    .btn-bayar {
        width: 100%; border: 0; border-radius: 8px;
        background: var(--kuning); color: #000;
        font-weight: 700; padding: 11px;
    }
    .btn-bayar:disabled { background: #e8dca8; color: #888; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Transaksi Penjualan</h1>
    <a href="{{ route('penjualan.index') }}" class="btn-abu">
        <i class="bi bi-clock-history"></i> Riwayat Penjualan
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="pos-wrap">

    {{-- ===== Kiri: pilih barang ===== --}}
    <section>
        <div class="panel-abu">
            <div class="search-box mb-2">
                <i class="bi bi-search"></i>
                <input type="text" id="cari" placeholder="Cari nama barang...">
            </div>
            <div class="d-flex gap-2 flex-wrap" id="chips">
                <button type="button" class="chip active" data-jenis="">Semua Kategori</button>
                <button type="button" class="chip" data-jenis="frame">Frame</button>
                <button type="button" class="chip" data-jenis="lensa">Lensa</button>
                <button type="button" class="chip" data-jenis="softlens">Softlens</button>
            </div>
        </div>

        <div class="pos-grid" id="grid-barang">
            @foreach ($barang as $b)
                @php
                    $kelasStok = $b->stok_tersedia <= 0 ? 'habis' : ($b->stok_tersedia <= $batasStok ? 'menipis' : '');
                @endphp
                <div class="pos-card {{ $b->stok_tersedia <= 0 ? 'habis' : '' }}"
                     data-id="{{ $b->id }}"
                     data-kode="{{ $b->kode_model }}"
                     data-merk="{{ $b->merk }}"
                     data-jenis="{{ $b->jenis }}"
                     data-harga="{{ (int) $b->harga_jual }}"
                     data-stok="{{ $b->stok_tersedia }}">
                    <div class="top">
                        <span class="kode-chip mono">{{ $b->kode_model }}</span>
                        <span class="badge-stok {{ $kelasStok }}">Stok: {{ $b->stok_tersedia }}</span>
                    </div>
                    <div class="nama">{{ $b->merk }}</div>
                    <div class="info">{{ ucfirst($b->jenis) }}</div>
                    <div class="bawah">
                        <span class="harga">Rp {{ number_format($b->harga_jual, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===== Kanan: keranjang & pembayaran ===== --}}
    <form method="POST" action="{{ route('penjualan.store') }}" id="form-pos" class="pos-keranjang">
        @csrf

        <h2><i class="bi bi-cart3 text-warning"></i> Transaksi Penjualan</h2>

        <label class="form-label" style="font-size:.8rem">Pilih Customer: <span class="text-danger">*</span></label>
        <div class="cust-combo" id="cust-combo">
            {{-- Nilai yang dikirim ke server: id customer (wajib diisi) --}}
            <input type="hidden" name="customer_id" id="cust-id" value="{{ old('customer_id') }}">

            <input type="text" id="cust-cari" class="form-control form-control-sm" autocomplete="off"
                   placeholder="Ketik nama atau nomor pelanggan...">

            <div class="cust-list d-none" id="cust-list">
                @foreach ($customer as $c)
                    <div class="cust-opt" data-id="{{ $c->id }}" data-nama="{{ $c->nama }}" data-wa="{{ $c->no_whatsapp }}">
                        {{ $c->nama }}
                        @if ($c->no_whatsapp)
                            <small>{{ $c->no_whatsapp }}</small>
                        @endif
                    </div>
                @endforeach

                <div id="cust-kosong" class="d-none">Customer tidak ditemukan.</div>

                <a href="{{ route('customer.index') }}" class="cust-tambah">
                    <i class="bi bi-plus-lg"></i> Tambah data customer
                </a>
            </div>
        </div>
        <div class="form-text text-danger d-none" id="cust-hint">Pilih Customer dulu</div>

        <div class="area-keranjang">
            <div id="keranjang-kosong">Keranjang masih kosong. Klik barang di sebelah kiri untuk menambahkan.</div>
            <div id="daftar-keranjang"></div>
        </div>

        <div class="fw-semibold" style="font-size:.8rem">Pilih Metode Pembayaran:</div>
        @php
            $daftarMetode = [
                'tunai'    => ['Uang Tunai (Cash)', 'bi-cash-stack'],
                'qris'     => ['QRIS Bank', 'bi-qr-code'],
                'transfer' => ['Transfer Bank', 'bi-bank'],
                'kartu'    => ['Kartu Debit/Kredit', 'bi-credit-card'],
            ];
        @endphp
        <div class="metode-grid">
            @foreach ($daftarMetode as $nilai => [$label, $ikon])
                <label class="metode-opt">
                    <input type="radio" name="metode_pembayaran" value="{{ $nilai }}"
                           @checked(old('metode_pembayaran', 'tunai') === $nilai)>
                    <span><i class="bi {{ $ikon }}"></i> {{ $label }}</span>
                </label>
            @endforeach
        </div>

        <div class="box-tunai" id="box-tunai">
            <label class="form-label mb-1" style="font-size:.8rem; font-weight:600">Cash Diterima:</label>
            <input type="number" name="uang_diterima" id="uang" min="0" step="any"
                   value="{{ old('uang_diterima') }}" class="form-control form-control-sm" placeholder="Contoh: 50000">
            <div class="baris-total mt-2 mb-0">
                <span>Kembalian:</span><strong id="kembalian">Rp 0</strong>
            </div>
        </div>

        <div class="baris-total"><span>Subtotal Belanja</span><span id="subtotal">Rp 0</span></div>
        <div class="baris-total align-items-center">
            <span>Diskon (Rp)</span>
            <input type="number" name="diskon" id="diskon" min="0" step="any" value="{{ old('diskon', 0) }}"
                   class="form-control form-control-sm" style="width: 130px; text-align:right">
        </div>
        <div class="baris-total besar"><span>Total Bayar:</span><span id="total">Rp 0</span></div>

        <button type="submit" id="btn-bayar" class="btn-bayar" disabled>Bayar</button>
    </form>
</div>

{{-- Isian lama (dipulihkan kalau transaksi ditolak server) --}}
<div id="data-pos" hidden data-lama="{{ json_encode(array_values(old('items', []))) }}"></div>
@endsection

@push('scripts')
<script>
    const grid       = document.getElementById('grid-barang');
    const daftar     = document.getElementById('daftar-keranjang');
    const kosong     = document.getElementById('keranjang-kosong');
    const elDiskon   = document.getElementById('diskon');
    const elUang     = document.getElementById('uang');
    const elSubtotal = document.getElementById('subtotal');
    const elTotal    = document.getElementById('total');
    const elKembali  = document.getElementById('kembalian');
    const boxTunai   = document.getElementById('box-tunai');
    const btnBayar   = document.getElementById('btn-bayar');
    const formPos    = document.getElementById('form-pos');

    const keranjang = {};          // id barang -> {id, kode, merk, harga, stok, qty}
    let subtotalSekarang = 0;

    function rp(angka) {
        return 'Rp ' + Math.round(angka).toLocaleString('id-ID');
    }

    function metodeAktif() {
        return document.querySelector('input[name="metode_pembayaran"]:checked').value;
    }

    function hitung(subtotal) {
        if (subtotal !== undefined) subtotalSekarang = subtotal;

        const diskon = Math.min(parseFloat(elDiskon.value) || 0, subtotalSekarang);
        const total  = subtotalSekarang - diskon;
        const tunai  = metodeAktif() === 'tunai';
        const uang   = parseFloat(elUang.value) || 0;

        // Customer wajib dipilih sebelum transaksi boleh dibayar
        const adaCustomer = document.getElementById('cust-id').value !== '';

        elSubtotal.textContent = rp(subtotalSekarang);
        elTotal.textContent    = rp(total);
        elKembali.textContent  = rp(Math.max(uang - total, 0));

        boxTunai.style.display = tunai ? 'block' : 'none';
        elUang.disabled        = !tunai;

        btnBayar.disabled = !(adaCustomer && subtotalSekarang > 0 && (!tunai || uang >= total));
    }

    function render() {
        daftar.innerHTML = '';
        const ids = Object.keys(keranjang);
        kosong.style.display = ids.length ? 'none' : 'block';

        let subtotal = 0;
        ids.forEach((id, i) => {
            const it = keranjang[id];
            subtotal += it.harga * it.qty;

            const row = document.createElement('div');
            row.className = 'cart-row';
            row.innerHTML = `
                <div class="cart-info">
                    <div class="cart-nama"></div>
                    <div class="cart-harga"></div>
                </div>
                <div class="cart-qty">
                    <button type="button" class="icon-btn kurang">-</button>
                    <span class="jml"></span>
                    <button type="button" class="icon-btn tambah">+</button>
                </div>
                <button type="button" class="icon-btn hapus" title="Hapus"><i class="bi bi-x-lg"></i></button>
                <input type="hidden" name="items[${i}][barang_id]" value="${it.id}">
                <input type="hidden" name="items[${i}][jumlah]" value="${it.qty}">
            `;
            row.querySelector('.cart-nama').textContent  = it.merk + ' (' + it.kode + ')';
            row.querySelector('.cart-harga').textContent = rp(it.harga) + ' x ' + it.qty + ' = ' + rp(it.harga * it.qty);
            row.querySelector('.jml').textContent        = it.qty;

            row.querySelector('.kurang').onclick = () => {
                it.qty--;
                if (it.qty <= 0) delete keranjang[id];
                render();
            };
            row.querySelector('.tambah').onclick = () => {
                if (it.qty < it.stok) it.qty++;
                render();
            };
            row.querySelector('.hapus').onclick = () => {
                delete keranjang[id];
                render();
            };

            daftar.appendChild(row);
        });

        hitung(subtotal);
    }

    function tambahKeKeranjang(kartu, jumlah = 1) {
        const stok = parseInt(kartu.dataset.stok);
        if (stok <= 0) return;

        const id = kartu.dataset.id;
        if (!keranjang[id]) {
            keranjang[id] = {
                id: id,
                kode: kartu.dataset.kode,
                merk: kartu.dataset.merk,
                harga: parseFloat(kartu.dataset.harga),
                stok: stok,
                qty: 0,
            };
        }
        keranjang[id].qty = Math.min(keranjang[id].qty + jumlah, stok);
        render();
    }

    // Klik kartu barang = tambah ke keranjang
    grid.addEventListener('click', e => {
        const kartu = e.target.closest('.pos-card');
        if (kartu) tambahKeKeranjang(kartu);
    });

    // Pencarian dan filter kategori (di browser, tanpa memuat ulang halaman)
    const elCari = document.getElementById('cari');
    let jenisAktif = '';

    function saring() {
        const kata = elCari.value.trim().toLowerCase();
        grid.querySelectorAll('.pos-card').forEach(k => {
            const cocokJenis = !jenisAktif || k.dataset.jenis === jenisAktif;
            const cocokKata  = (k.dataset.kode + ' ' + k.dataset.merk).toLowerCase().includes(kata);
            k.style.display = (cocokJenis && cocokKata) ? '' : 'none';
        });
    }

    elCari.addEventListener('input', saring);
    document.getElementById('chips').addEventListener('click', e => {
        const chip = e.target.closest('.chip');
        if (!chip) return;
        document.querySelectorAll('#chips .chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        jenisAktif = chip.dataset.jenis;
        saring();
    });

    // Pembayaran
    elDiskon.addEventListener('input', () => hitung());
    elUang.addEventListener('input', () => hitung());
    document.querySelectorAll('input[name="metode_pembayaran"]').forEach(r =>
        r.addEventListener('change', () => hitung())
    );

    // Cegah klik ganda pada tombol Bayar
    formPos.addEventListener('submit', () => { btnBayar.disabled = true; });

    // Pulihkan keranjang kalau server menolak transaksi
    const lama = JSON.parse(document.getElementById('data-pos').dataset.lama);
    lama.forEach(it => {
        const kartu = grid.querySelector('.pos-card[data-id="' + it.barang_id + '"]');
        if (kartu) tambahKeKeranjang(kartu, parseInt(it.jumlah) || 1);
    });

    render();
</script>

{{-- Pencarian customer (dibungkus agar nama variabelnya tidak bentrok dengan script di atas) --}}
<script>
(function () {
    var combo = document.getElementById('cust-combo');
    var input = document.getElementById('cust-cari');
    var hidden = document.getElementById('cust-id');
    var hint = document.getElementById('cust-hint');
    var list = document.getElementById('cust-list');
    var pesanKosong = document.getElementById('cust-kosong');
    var opsi = Array.prototype.slice.call(list.querySelectorAll('.cust-opt'));
    var sorot = -1; // posisi opsi yang disorot (di antara opsi yang tampil)

    function terlihat() {
        return opsi.filter(function (o) { return !o.classList.contains('d-none'); });
    }

    // Isi kolom ketik sesuai customer yang benar-benar terpilih (kosong kalau belum ada),
    // lalu perbarui pesan peringatan dan status tombol Bayar
    function sinkron() {
        var teks = '';
        opsi.forEach(function (o) {
            if (o.dataset.id === hidden.value) {
                teks = o.dataset.nama;
            }
        });
        input.value = teks;

        hint.classList.toggle('d-none', hidden.value !== '');
        if (typeof hitung === 'function') { hitung(); }
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
        // Nomor yang diketik 0812... dicocokkan dengan format tersimpan 62812...
        var angka = q.replace(/\D/g, '');
        if (angka.indexOf('0') === 0) { angka = '62' + angka.slice(1); }

        var ada = false;
        opsi.forEach(function (o) {
            var nama = o.dataset.nama.toLowerCase();
            var wa = o.dataset.wa || '';
            var cocok = !q || nama.indexOf(q) !== -1 || (angka !== '' && wa.indexOf(angka) !== -1);
            o.classList.toggle('d-none', !cocok);
            o.classList.remove('sorot');
            if (cocok) { ada = true; }
        });
        pesanKosong.classList.toggle('d-none', ada);
        sorot = -1;
    }

    function buka() { list.classList.remove('d-none'); }

    function tutup() {
        list.classList.add('d-none');
        sinkron();
    }

    function pilih(o) {
        hidden.value = o.dataset.id;
        tutup();
    }

    input.addEventListener('focus', function () {
        saring('');
        buka();
        input.select();
    });

    input.addEventListener('click', function () {
        if (list.classList.contains('d-none')) {
            saring('');
            buka();
        }
    });

    input.addEventListener('input', function () {
        saring(input.value);
        buka();
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            buka();
            aturSorot(sorot + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            buka();
            aturSorot(sorot < 0 ? -1 : sorot - 1);
        } else if (e.key === 'Enter') {
            // Enter tidak boleh mengirim form transaksi
            e.preventDefault();
            if (list.classList.contains('d-none')) { return; }
            var v = terlihat();
            var target = sorot >= 0 ? v[sorot] : v[0];
            if (target) { pilih(target); }
        } else if (e.key === 'Escape') {
            tutup();
        }
    });

    opsi.forEach(function (o) {
        o.addEventListener('click', function () { pilih(o); });
    });

    // Klik pada daftar tidak boleh membuat kolom ketik kehilangan fokus sebelum pilihan terbaca
    list.addEventListener('mousedown', function (e) { e.preventDefault(); });

    // Klik di luar = tutup
    document.addEventListener('click', function (e) {
        if (!combo.contains(e.target)) { tutup(); }
    });

    // Tombol Tab keluar dari kolom = tutup
    combo.addEventListener('focusout', function (e) {
        if (!combo.contains(e.relatedTarget)) { tutup(); }
    });

    // Awal: tampilkan customer yang tadinya terpilih (misalnya setelah transaksi ditolak server)
    sinkron();
})();
</script>
@endpush