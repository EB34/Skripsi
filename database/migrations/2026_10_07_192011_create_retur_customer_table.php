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
        Schema::create('retur_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualan');
            $table->string('kode_model');
            $table->text('keluhan');
            $table->string('status_konfirmasi')->nullable();
            $table->string('tindak_lanjut')->nullable(); // tukar_supplier / tukar_stok_toko / refund
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_customer');
    }
};
