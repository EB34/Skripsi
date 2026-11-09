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
    .pos-card .ukuran { font-size: .7rem; font-weight: 600; color: #1f5f87; }
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
        display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
        background: #fff; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px;
    }
    .cart-info { flex: 1; min-width: 0; }
    .cart-nama { font-size: .8rem; font-weight: 600; }
    .cart-ukuran { font-size: .68rem; font-weight: 600; color: #1f5f87; }
    .cart-harga { font-size: .7rem; color: #555; }
    .cart-qty { display: flex; align-items: center; gap: 6px; font-size: .8rem; font-weight: 600; }
    /* Isian harga dan ukuran untuk lensa custom */
    .cart-extra { flex: 0 0 100%; display: grid; gap: 6px; }
    .cart-extra input { font-size: .75rem; }
    .cart-ukuran-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
    .cart-ukuran-grid label { font-size: .62rem; margin: 0; font-weight: 600; color: #555; }
    .cart-ukuran-grid input { padding: 3px 5px; text-align: center; }

    /* Kotak DP */
    .box-dp {
        background: #fff3b0;
        border: 1px solid #d9a400;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 14px;
        font-size: .78rem;
    }
    .box-dp .judul-dp { font-weight: 700; }
    .box-dp small { display: block; color: #555; margin-top: 2px; }

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
    .baris-total.merah { color: #a31515; font-weight: 600; }
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
                <input type="text" id="cari" placeholder="Cari nama, kode, atau ukuran SPH barang...">
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
                    // Definisi "menipis" per barang (stok_minimum), sama dengan halaman Pengelolaan Stok
                    $kelasStok = $b->status_stok === 'aman' ? '' : $b->status_stok;
                    // Lensa custom tidak punya stok, jadi tidak pernah dianggap habis
                    $habis = ! $b->tanpa_stok && $b->stok_tersedia <= 0;
                @endphp
                <div class="pos-card {{ $habis ? 'habis' : '' }}"
                     data-id="{{ $b->id }}"
                     data-kode="{{ $b->kode_model }}"
                     data-merk="{{ $b->merk }}"
                     data-jenis="{{ $b->jenis }}"
                     data-ukuran="{{ $b->ukuran_label }}"
                     data-harga="{{ (int) $b->harga_jual }}"
                     data-stok="{{ $b->stok_tersedia }}"
                     data-custom="{{ $b->tanpa_stok ? 1 : 0 }}">
                    <div class="top">
                        <span class="kode-chip mono">{{ $b->kode_model }}</span>
                        @if ($b->tanpa_stok)
                            <span class="badge-stok">Custom</span>
                        @else
                            <span class="badge-stok {{ $kelasStok }}">Stok: {{ $b->stok_tersedia }}</span>
                        @endif
                    </div>
                    <div class="nama">{{ $b->merk }}</div>
                    <div class="info">
                        {{ ucfirst($b->jenis) }}
                        @if ($b->tanpa_stok)
                            <br><span class="ukuran">Harga dan ukuran diisi di keranjang</span>
                        @elseif ($b->ukuran_label)
                            <br><span class="ukuran">{{ $b->ukuran_label }}</span>
                        @endif
                    </div>
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

        {{-- Opsi DP: muncul otomatis kalau keranjang berisi frame + lensa dan total setelah diskon > harga lensa --}}
        <div class="box-dp d-none" id="box-dp">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="bayar_dp" value="1" id="cek-dp" @checked(old('bayar_dp'))>
                <label class="form-check-label judul-dp" for="cek-dp">Bayar DP saja</label>
            </div>
            <small id="info-dp"></small>
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

        {{-- Hanya tampil kalau DP dicentang --}}
        <div class="baris-total d-none" id="baris-dp"><span>Dibayar sekarang (DP)</span><strong id="dp-nominal">Rp 0</strong></div>
        <div class="baris-total merah d-none" id="baris-sisa"><span>Sisa dilunasi saat pengambilan</span><span id="dp-sisa">Rp 0</span></div>

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
    const boxDp      = document.getElementById('box-dp');
    const cekDp      = document.getElementById('cek-dp');
    const infoDp     = document.getElementById('info-dp');
    const barisDp    = document.getElementById('baris-dp');
    const barisSisa  = document.getElementById('baris-sisa');
    const elDpNom    = document.getElementById('dp-nominal');
    const elDpSisa   = document.getElementById('dp-sisa');

    // kunci -> {id, kode, merk, jenis, ukuran, harga, stok, qty, custom, sph, cyl, axis, add}
    // Barang biasa memakai id barang sebagai kunci (satu baris per barang).
    // Lensa custom memakai kunci unik "c1", "c2", ... (satu baris per biji).
    const keranjang = {};
    let urutCustom = 0;

    function rp(angka) {
        return 'Rp ' + Math.round(angka).toLocaleString('id-ID');
    }

    function metodeAktif() {
        return document.querySelector('input[name="metode_pembayaran"]:checked').value;
    }

    // Ringkasan isi keranjang: subtotal, apakah ada frame, dan total harga semua lensa
    function ringkas() {
        let subtotal = 0, subLensa = 0, adaFrame = false;
        Object.values(keranjang).forEach(it => {
            subtotal += it.harga * it.qty;
            if (it.jenis === 'frame') adaFrame = true;
            if (it.jenis === 'lensa') subLensa += it.harga * it.qty;
        });
        return { subtotal: subtotal, subLensa: subLensa, adaFrame: adaFrame };
    }

    // Lensa custom wajib punya harga dan SPH; kalau CYL terisi (bukan 0), AXIS wajib
    function customLengkap() {
        return Object.values(keranjang).every(it => {
            if (!it.custom) return true;
            if (!(it.harga > 0)) return false;
            if (it.sph === '') return false;
            if (it.cyl !== '' && parseFloat(it.cyl) !== 0 && it.axis === '') return false;
            return true;
        });
    }

    function hitung() {
        const r      = ringkas();
        const diskon = Math.min(parseFloat(elDiskon.value) || 0, r.subtotal);
        const total  = r.subtotal - diskon;
        const tunai  = metodeAktif() === 'tunai';
        const uang   = parseFloat(elUang.value) || 0;

        // DP hanya untuk frame + lensa, dan hanya kalau total setelah diskon masih lebih besar dari harga lensa
        const dpBisa = r.adaFrame && r.subLensa > 0 && total > r.subLensa;
        boxDp.classList.toggle('d-none', !dpBisa);
        if (!dpBisa) cekDp.checked = false;

        const dp            = dpBisa && cekDp.checked;
        const bayarSekarang = dp ? r.subLensa : total;

        if (dpBisa) {
            infoDp.textContent = 'Bayar ' + rp(r.subLensa) + ' (sama dengan harga lensa) sekarang, sisanya dilunasi saat kacamata diambil. Berlaku untuk semua metode pembayaran.';
        }

        barisDp.classList.toggle('d-none', !dp);
        barisSisa.classList.toggle('d-none', !dp);
        elDpNom.textContent  = rp(r.subLensa);
        elDpSisa.textContent = rp(total - r.subLensa);

        // Customer wajib dipilih sebelum transaksi boleh dibayar
        const adaCustomer = document.getElementById('cust-id').value !== '';

        elSubtotal.textContent = rp(r.subtotal);
        elTotal.textContent    = rp(total);
        elKembali.textContent  = rp(Math.max(uang - bayarSekarang, 0));
        btnBayar.textContent   = dp ? 'Bayar DP' : 'Bayar';

        boxTunai.style.display = tunai ? 'block' : 'none';
        elUang.disabled        = !tunai;

        btnBayar.disabled = !(adaCustomer && r.subtotal > 0 && customLengkap() && (!tunai || uang >= bayarSekarang));
    }

    function render() {
        daftar.innerHTML = '';
        const kunciSemua = Object.keys(keranjang);
        kosong.style.display = kunciSemua.length ? 'none' : 'block';

        kunciSemua.forEach((kunci, i) => {
            const it = keranjang[kunci];

            const row = document.createElement('div');
            row.className = 'cart-row';
            row.innerHTML = `
                <div class="cart-info">
                    <div class="cart-nama"></div>
                    <div class="cart-ukuran"></div>
                    <div class="cart-harga"></div>
                </div>
                <div class="cart-qty"></div>
                <button type="button" class="icon-btn hapus" title="Hapus"><i class="bi bi-x-lg"></i></button>
                <input type="hidden" name="items[${i}][barang_id]" value="${it.id}">
                <input type="hidden" name="items[${i}][jumlah]" value="${it.qty}">
            `;
            row.querySelector('.cart-nama').textContent   = it.merk + ' (' + it.kode + ')';
            row.querySelector('.cart-ukuran').textContent = it.ukuran;

            const kolomQty = row.querySelector('.cart-qty');

            if (it.custom) {
                // Lensa custom: 1 biji per baris, harga dan ukuran diketik petugas
                row.querySelector('.cart-harga').textContent = 'Lensa custom (tanpa stok)';
                kolomQty.textContent = '1 biji';

                const extra = document.createElement('div');
                extra.className = 'cart-extra';

                const inHarga = document.createElement('input');
                inHarga.type = 'number';
                inHarga.min = '0';
                inHarga.step = 'any';
                inHarga.name = 'items[' + i + '][harga]';
                inHarga.className = 'form-control form-control-sm';
                inHarga.placeholder = 'Harga lensa (Rp) *';
                inHarga.value = it.harga > 0 ? it.harga : '';
                inHarga.addEventListener('input', () => {
                    it.harga = parseFloat(inHarga.value) || 0;
                    hitung();                  // tanpa render ulang supaya kursor tidak pindah
                });
                extra.appendChild(inHarga);

                // Kotak ukuran: SPH, CYL, ADD naik 0,25; AXIS bilangan bulat 0-180
                const kotak = document.createElement('div');
                kotak.className = 'cart-ukuran-grid';
                [
                    ['sph',  'SPH *', '0.25'],
                    ['cyl',  'CYL',   '0.25'],
                    ['axis', 'AXIS',  '1'],
                    ['add',  'ADD',   '0.25'],
                ].forEach(([f, label, langkah]) => {
                    const bungkus = document.createElement('div');

                    const lb = document.createElement('label');
                    lb.textContent = label;

                    const inp = document.createElement('input');
                    inp.type = 'number';
                    inp.step = langkah;
                    if (f === 'axis') { inp.min = '0'; inp.max = '180'; }
                    inp.name = 'items[' + i + '][' + f + ']';
                    inp.className = 'form-control form-control-sm';
                    inp.value = it[f];
                    inp.addEventListener('input', () => {
                        it[f] = inp.value;
                        hitung();
                    });

                    bungkus.appendChild(lb);
                    bungkus.appendChild(inp);
                    kotak.appendChild(bungkus);
                });
                extra.appendChild(kotak);

                row.appendChild(extra);
            } else {
                row.querySelector('.cart-harga').textContent = rp(it.harga) + ' x ' + it.qty + ' = ' + rp(it.harga * it.qty);

                kolomQty.innerHTML = `
                    <button type="button" class="icon-btn kurang">-</button>
                    <span class="jml"></span>
                    <button type="button" class="icon-btn tambah">+</button>
                `;
                kolomQty.querySelector('.jml').textContent = it.qty;

                kolomQty.querySelector('.kurang').onclick = () => {
                    it.qty--;
                    if (it.qty <= 0) delete keranjang[kunci];
                    render();
                };
                kolomQty.querySelector('.tambah').onclick = () => {
                    if (it.qty < it.stok) it.qty++;
                    render();
                };
            }

            row.querySelector('.hapus').onclick = () => {
                delete keranjang[kunci];
                render();
            };

            daftar.appendChild(row);
        });

        hitung();
    }

    // isian dipakai untuk memulihkan lensa custom: {harga, sph, cyl, axis, add}
    function tambahKeKeranjang(kartu, jumlah = 1, isian = null) {
        const jenis  = kartu.dataset.jenis;
        const custom = kartu.dataset.custom === '1';

        if (custom) {
            // Tiap klik = satu baris baru (satu biji)
            const kunci = 'c' + (++urutCustom);
            const nilai = v => (v === null || v === undefined) ? '' : String(v);
            keranjang[kunci] = {
                id: kartu.dataset.id,
                kode: kartu.dataset.kode,
                merk: kartu.dataset.merk,
                jenis: jenis,
                ukuran: '',
                harga: isian ? (parseFloat(isian.harga) || 0) : (parseFloat(kartu.dataset.harga) || 0),
                stok: 1,
                qty: 1,
                custom: true,
                sph:  isian ? nilai(isian.sph)  : '',
                cyl:  isian ? nilai(isian.cyl)  : '',
                axis: isian ? nilai(isian.axis) : '',
                add:  isian ? nilai(isian.add)  : '',
            };
            render();
            return;
        }

        const stok = parseInt(kartu.dataset.stok);
        if (stok <= 0) return;

        const id = kartu.dataset.id;
        if (!keranjang[id]) {
            keranjang[id] = {
                id: id,
                kode: kartu.dataset.kode,
                merk: kartu.dataset.merk,
                jenis: jenis,
                ukuran: kartu.dataset.ukuran || '',
                harga: parseFloat(kartu.dataset.harga),
                stok: stok,
                qty: 0,
                custom: false,
                sph: '', cyl: '', axis: '', add: '',
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
            const teks = (k.dataset.kode + ' ' + k.dataset.merk + ' ' + (k.dataset.ukuran || '')).toLowerCase();
            k.style.display = (cocokJenis && teks.includes(kata)) ? '' : 'none';
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
    cekDp.addEventListener('change', () => hitung());
    document.querySelectorAll('input[name="metode_pembayaran"]').forEach(r =>
        r.addEventListener('change', () => hitung())
    );

    // Cegah klik ganda pada tombol Bayar
    formPos.addEventListener('submit', () => { btnBayar.disabled = true; });

    // Pulihkan keranjang kalau server menolak transaksi
    const lama = JSON.parse(document.getElementById('data-pos').dataset.lama);
    lama.forEach(it => {
        const kartu = grid.querySelector('.pos-card[data-id="' + it.barang_id + '"]');
        if (!kartu) return;

        if (kartu.dataset.custom === '1') {
            tambahKeKeranjang(kartu, 1, {
                harga: it.harga, sph: it.sph, cyl: it.cyl, axis: it.axis, add: it.add
            });
        } else {
            tambahKeKeranjang(kartu, parseInt(it.jumlah) || 1);
        }
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