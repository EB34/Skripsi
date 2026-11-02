<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penggantian_kesalahan_toko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan_kacamata');
            $table->string('jenis_kesalahan');
            $table->foreignId('lensa_writeoff_id')->constrained('barang');
            $table->foreignId('lensa_pengganti_id')->constrained('barang');
            $table->decimal('nilai_kerugian', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggantian_kesalahan_toko');
    }
};
