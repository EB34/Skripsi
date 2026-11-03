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
            $table->string('nomor_pesanan')->unique();                              // ORD-ddmmyyyy-urutan
            $table->foreignId('penjualan_id')->unique()->constrained('penjualan');  // satu nota = satu pesanan
            $table->foreignId('customer_id')->constrained('customer');
            $table->foreignId('refraksi_id')->nullable()->constrained('refraksi')->nullOnDelete(); // hanya acuan awal ukuran

            $table->foreignId('frame_id')->nullable()->constrained('barang');       // kosong = frame bawaan pelanggan
            $table->foreignId('lensa_id')->nullable()->constrained('barang');       // kosong = lensa lama pelanggan
            $table->unsignedTinyInteger('jumlah_lensa')->default(0);                // biji lensa dari nota (1 atau 2)

            // Ukuran yang benar-benar dikerjakan (boleh berbeda dari refraksi)
            $table->decimal('od_sphere', 5, 2)->nullable();
            $table->decimal('od_cylinder', 5, 2)->nullable();
            $table->unsignedSmallInteger('od_axis')->nullable();
            $table->decimal('od_add', 4, 2)->nullable();
            $table->decimal('od_mono_pd', 4, 1)->nullable();
            $table->decimal('os_sphere', 5, 2)->nullable();
            $table->decimal('os_cylinder', 5, 2)->nullable();
            $table->unsignedSmallInteger('os_axis')->nullable();
            $table->decimal('os_add', 4, 2)->nullable();
            $table->decimal('os_mono_pd', 4, 1)->nullable();
            $table->decimal('pd_total', 4, 1)->nullable();

            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();   // self-assignment
            $table->enum('status', ['menunggu', 'dikerjakan', 'siap_diambil', 'selesai', 'batal'])->default('menunggu');
            $table->date('estimasi_selesai')->nullable();
            $table->text('keterangan')->nullable();

            $table->timestamp('dikerjakan_pada')->nullable();
            $table->timestamp('siap_diambil_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();

            // Pembatalan (DP hangus)
            $table->string('alasan_batal')->nullable();
            $table->timestamp('dibatalkan_pada')->nullable();
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('frame_dikembalikan')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan_kacamata');
    }
};