<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\RefraksiController;
use App\Http\Controllers\StokController;      // BARU
use App\Http\Controllers\LokasiController;    // BARU

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
    Route::resource('barang', BarangController::class)->only(['index', 'store', 'update', 'destroy']);

    // BARU: Pengelolaan Stok, tiga tab
    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');
    Route::get('/stok/mutasi', [StokController::class, 'mutasi'])->name('stok.mutasi');
    Route::get('/stok/rusak', [StokController::class, 'rusak'])->name('stok.rusak');
    Route::post('/stok/rusak', [StokController::class, 'laporRusak'])->name('stok.rusak.store');
    Route::post('/stok/rusak/{barangRusak}/putuskan', [StokController::class, 'putuskanRusak'])->name('stok.rusak.putuskan');

    // BARU: detail barang dan aksinya (rute literal di atas harus mendahului {barang})
    Route::get('/stok/{barang}', [StokController::class, 'show'])->whereNumber('barang')->name('stok.show');
    Route::post('/stok/{barang}/sesuaikan', [StokController::class, 'sesuaikan'])->whereNumber('barang')->name('stok.sesuaikan');
    Route::post('/stok/{barang}/pindah', [StokController::class, 'pindahLokasi'])->whereNumber('barang')->name('stok.pindah');
    Route::patch('/stok/{barang}/pengaturan', [StokController::class, 'aturPengaturan'])->whereNumber('barang')->name('stok.pengaturan');

    // BARU: kelola lokasi stok
    Route::get('/lokasi', [LokasiController::class, 'index'])->name('lokasi.index');
    Route::post('/lokasi', [LokasiController::class, 'store'])->name('lokasi.store');
    Route::put('/lokasi/{lokasi}', [LokasiController::class, 'update'])->name('lokasi.update');
    Route::delete('/lokasi/{lokasi}', [LokasiController::class, 'destroy'])->name('lokasi.destroy');

    // Page khusus Owner
    Route::middleware('role:owner')->group(function () {
        Route::get('/laporan', fn () => 'Halaman laporan (khusus Owner)');
    });
});