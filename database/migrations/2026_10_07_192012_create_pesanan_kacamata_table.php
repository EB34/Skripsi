<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan_kacamata', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pesanan')->unique();                          // ORD-ddmmyyyy-urutan

            $table->foreignId('customer_id')->constrained('customer');
            $table->foreignId('refraksi_id')->constrained('refraksi');          // resep diambil dari riwayat refraksi pelanggan
            $table->foreignId('frame_id')->nullable()->constrained('barang');   // kosong jika frame bawaan pelanggan
            $table->foreignId('lensa_id')->constrained('barang');               // lensa wajib dari toko

            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete(); // yang mencatat pesanan
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();  // self-assignment: yang mengerjakan

            $table->enum('status', ['menunggu', 'dikerjakan', 'siap_diambil', 'selesai'])->default('menunggu');
            $table->date('estimasi_selesai')->nullable();
            $table->text('keterangan')->nullable();

            $table->timestamp('dikerjakan_pada')->nullable();
            $table->timestamp('siap_diambil_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan_kacamata');
    }
};