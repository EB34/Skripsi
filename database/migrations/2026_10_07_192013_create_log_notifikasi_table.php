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
        Schema::create('log_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan_kacamata')->cascadeOnDelete();
            $table->string('no_tujuan', 20);
            $table->text('isi_pesan');
            $table->string('status_kirim')->default('menunggu'); // menunggu / berhasil / gagal
            $table->dateTime('waktu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_notifikasi');
    }
};
