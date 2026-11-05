<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\RefraksiController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\KerugianController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ReturSupplierController;
use App\Http\Controllers\ReturCustomerController;
use App\Http\Controllers\KesalahanTokoController;
use App\Http\Controllers\LogNotifikasiController;
use App\Http\Controllers\TemplateNotifikasiController;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::redirect('/', '/dashboard');

// Klo blm login lgsg dianggap guest trs diarahkan ke page login, klo udh login lgsg diarahkan ke dashboard
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::resource('supplier', SupplierController::class)->except('show','create','edit');
    Route::resource('customer', CustomerController::class)->except('show');
    Route::post('customer/{customer}/refraksi', [RefraksiController::class, 'store'])->name('refraksi.store');
    Route::resource('pembelian', PembelianController::class)->only(['index', 'store', 'show']);
    Route::post('pembelian/{pembelian}/terima', [PembelianController::class, 'terima'])->name('pembelian.terima');
    Route::resource('penjualan', PenjualanController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('penjualan/{penjualan}/tutup', [PenjualanController::class, 'tutup'])->name('penjualan.tutup');   // BARU
    Route::resource('barang', BarangController::class)->only(['index', 'store', 'update', 'destroy']);

    // Pengelolaan Stok, tiga tab
    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');
    Route::get('/stok/mutasi', [StokController::class, 'mutasi'])->name('stok.mutasi');
    Route::get('/stok/rusak', [StokController::class, 'rusak'])->name('stok.rusak');
    Route::post('/stok/rusak', [StokController::class, 'laporRusak'])->name('stok.rusak.store');
    // (rute stok.rusak.putuskan dipindah ke grup role:owner di bawah)

    // Detail barang dan aksinya (rute literal di atas harus mendahului {barang})
    Route::get('/stok/{barang}', [StokController::class, 'show'])->whereNumber('barang')->name('stok.show');
    Route::post('/stok/{barang}/sesuaikan', [StokController::class, 'sesuaikan'])->whereNumber('barang')->name('stok.sesuaikan');
    Route::post('/stok/{barang}/pindah', [StokController::class, 'pindahLokasi'])->whereNumber('barang')->name('stok.pindah');
    // (rute stok.pengaturan dipindah ke grup role:owner di bawah)

    // Kelola lokasi stok
    Route::get('/lokasi', [LokasiController::class, 'index'])->name('lokasi.index');
    Route::post('/lokasi', [LokasiController::class, 'store'])->name('lokasi.store');
    Route::put('/lokasi/{lokasi}', [LokasiController::class, 'update'])->name('lokasi.update');
    Route::delete('/lokasi/{lokasi}', [LokasiController::class, 'destroy'])->name('lokasi.destroy');

    // Pesanan Kacamata (Owner dan Petugas). Method selesaikan dan batalkan dibuat di Bagian 2.
    Route::get('/pesanan', [PesananController::class, 'index'])->name('pesanan.index');
    Route::post('/pesanan', [PesananController::class, 'store'])->name('pesanan.store');
    Route::post('/pesanan/{pesanan}/ambil', [PesananController::class, 'ambil'])->name('pesanan.ambil');
    Route::patch('/pesanan/{pesanan}/status', [PesananController::class, 'ubahStatus'])->name('pesanan.status');
    Route::post('/pesanan/{pesanan}/selesai', [PesananController::class, 'selesaikan'])->name('pesanan.selesai');
    Route::post('/pesanan/{pesanan}/batal', [PesananController::class, 'batalkan'])->name('pesanan.batal');

    // Log notifikasi WhatsApp dan template pesan (Owner dan Petugas)
    Route::get('/notifikasi', [LogNotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/{log}/kirim-ulang', [LogNotifikasiController::class, 'kirimUlang'])->whereNumber('log')->name('notifikasi.kirim-ulang');
    Route::get('/notifikasi/template', [TemplateNotifikasiController::class, 'index'])->name('notifikasi.template');
    Route::put('/notifikasi/template/{kode}', [TemplateNotifikasiController::class, 'update'])->name('notifikasi.template.update');
    Route::post('/notifikasi/template/{kode}/reset', [TemplateNotifikasiController::class, 'reset'])->name('notifikasi.template.reset');

    // Modul Retur: tab untuk Owner dan Petugas (Petugas mencatat keluhan dan kejadian)
    Route::get('/retur/customer', [ReturCustomerController::class, 'index'])->name('retur.customer');
    Route::post('/retur/customer', [ReturCustomerController::class, 'store'])->name('retur.customer.store');
    Route::get('/retur/kesalahan-toko', [KesalahanTokoController::class, 'index'])->name('retur.kesalahan');
    Route::post('/retur/kesalahan-toko', [KesalahanTokoController::class, 'store'])->name('retur.kesalahan.store');

    // Modul Retur, tab Retur ke Supplier: daftar dan cetak bisa dilihat Owner dan Petugas
    Route::get('/retur/supplier', [ReturSupplierController::class, 'index'])->name('retur.supplier');
    Route::get('/retur/supplier/{retur}/nota', [ReturSupplierController::class, 'nota'])->name('retur.supplier.nota');
    Route::get('/retur/supplier/{retur}/surat-jalan', [ReturSupplierController::class, 'suratJalan'])->name('retur.supplier.suratjalan');

    // Page khusus Owner
    Route::middleware('role:owner')->group(function () {
        // Laporan Laba Rugi: angka keuangan toko hanya untuk Owner
        Route::get('/laporan/labarugi', [LaporanController::class, 'labaRugi'])->name('laporan.labarugi');

        // Keputusan barang rusak (retur ke supplier atau write off) = wewenang Owner
        Route::post('/stok/rusak/{barangRusak}/putuskan', [StokController::class, 'putuskanRusak'])->name('stok.rusak.putuskan');

        // Batas stok menipis per barang: hanya Owner yang boleh mengatur
        Route::patch('/stok/{barang}/pengaturan', [StokController::class, 'aturPengaturan'])->whereNumber('barang')->name('stok.pengaturan');

        // Kerugian operasional: nilai rupiah kerugian toko hanya untuk Owner
        Route::get('/kerugian', [KerugianController::class, 'index'])->name('kerugian.index');

        // Retur ke Supplier: validasi dan tandai selesai = wewenang Owner
        Route::post('/retur/supplier/{retur}/kirim', [ReturSupplierController::class, 'kirim'])->name('retur.supplier.kirim');
        Route::post('/retur/supplier/{retur}/selesai', [ReturSupplierController::class, 'selesai'])->name('retur.supplier.selesai');

        // Memproses retur customer (konfirmasi supplier + tindak lanjut) = wewenang Owner
        Route::post('/retur/customer/{retur}/proses', [ReturCustomerController::class, 'proses'])->name('retur.customer.proses');
    });
});