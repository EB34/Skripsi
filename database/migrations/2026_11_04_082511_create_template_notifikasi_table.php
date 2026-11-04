<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 40)->unique();   // siap_diambil_lunas, siap_diambil_sisa
            $table->string('nama', 100);
            $table->text('isi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_notifikasi');
    }
};