<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualan')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barang');
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('jumlah_kembali')->default(0);   // frame yang dikembalikan ke stok saat pesanan batal
            $table->decimal('harga_jual', 12, 2);
            $table->decimal('hpp', 12, 2)->default(0);
            $table->string('keterangan', 150)->nullable();           // BARU: ukuran/spesifikasi yang diketik petugas untuk lensa custom
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_penjualan');
    }
};