<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\RefraksiController;

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

    // Page khusus Owner
    Route::middleware('role:owner')->group(function () {
        Route::get('/laporan', fn () => 'Halaman laporan (khusus Owner)');
    });
});