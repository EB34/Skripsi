<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lokasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            // true = tempat penyimpanan (gudang). Barang keluar mengambil dari lokasi non-gudang lebih dulu.
            $table->boolean('is_gudang')->default(false);
            $table->timestamps();
        });

        Schema::create('stok_lokasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->cascadeOnDelete();
            $table->foreignId('lokasi_id')->constrained('lokasi');
            $table->unsignedInteger('jumlah')->default(0);
            $table->timestamps();

            $table->unique(['barang_id', 'lokasi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_lokasi');
        Schema::dropIfExists('lokasi');
    }
};