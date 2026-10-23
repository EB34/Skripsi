<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['username' => 'owner'], [
            'nama' => 'Owner Solo Optik', 'password' => 'password', 'role' => 'owner',
        ]);
        User::updateOrCreate(['username' => 'petugas'], [
            'nama' => 'Petugas Optik', 'password' => 'password', 'role' => 'petugas',
        ]);

        // Stok dibuat 0 krn stok msk lwt Pembelian supaya batch FIFO terbentuk
        $barang = [
            ['kode_model' => 'FR-001', 'jenis' => 'frame',    'merk' => 'Contoh Merk A',       'harga_jual' => 350000],
            ['kode_model' => 'LN-001', 'jenis' => 'lensa',    'merk' => 'Lensa Minus Standar', 'harga_jual' => 200000],
            ['kode_model' => 'LN-002', 'jenis' => 'lensa',    'merk' => 'Lensa Photochromic',  'harga_jual' => 450000],
            ['kode_model' => 'SL-001', 'jenis' => 'softlens', 'merk' => 'Softlens Bulanan',    'harga_jual' => 120000],
        ];

        foreach ($barang as $b) {
            Barang::updateOrCreate(['kode_model' => $b['kode_model']], $b);
        }
    }
}