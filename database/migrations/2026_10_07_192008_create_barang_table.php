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
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_model')->unique();
            $table->enum('jenis', ['frame', 'lensa', 'softlens']);
            $table->string('merk');
            $table->decimal('harga_jual', 12, 2)->default(0);
            $table->unsignedInteger('stok_tersedia')->default(0);
            $table->unsignedInteger('stok_rusak')->default(0);
            $table->unsignedInteger('stok_minimum')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
