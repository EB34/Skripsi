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
        Schema::create('detail_retur_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_supplier_id')->constrained('retur_supplier')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barang');
            $table->unsignedInteger('jumlah');
            $table->enum('alasan', ['cacat', 'salah_kirim'])->default('cacat');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_retur_supplier');
    }
};
