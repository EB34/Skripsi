<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penggantian_kesalahan_toko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan_kacamata');
            $table->string('jenis_kesalahan', 40);
            $table->foreignId('lensa_writeoff_id')->constrained('barang');   // lensa lama yang salah (di-write off)
            $table->foreignId('lensa_pengganti_id')->constrained('barang');  // lensa baru dari stok
            $table->unsignedInteger('jumlah')->default(1);                   // jumlah biji yang diganti
            $table->decimal('nilai_kerugian', 12, 2)->default(0);            // HPP lensa pengganti (FIFO)
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penggantian_kesalahan_toko');
    }
};