<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('jenis', 30)->default('manual'); // siap_diambil, manual, dst.
            $table->string('tujuan', 20);                   // format 62812...
            $table->text('pesan');
            $table->enum('status', ['menunggu', 'terkirim', 'gagal'])->default('menunggu');
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->text('respons')->nullable();            // pesan error / balasan gateway
            $table->string('referensi_tipe', 30)->nullable(); // contoh: pesanan
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamp('dikirim_pada')->nullable();
            $table->timestamps();

            $table->index(['referensi_tipe', 'referensi_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_notifikasi');
    }
};