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
        Schema::create('pesanan_kacamata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customer');
            $table->foreignId('frame_id')->nullable()->constrained('barang');   // kosong jika frame bawaan pelanggan
            $table->foreignId('lensa_id')->constrained('barang');               // lensa wajib dari toko
            $table->foreignId('petugas_id')->nullable()->constrained('users');  // self-assignment
            $table->enum('status', ['menunggu', 'dikerjakan', 'siap_diambil'])->default('menunggu');
            $table->date('estimasi_selesai')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan_kacamata');
    }
};
