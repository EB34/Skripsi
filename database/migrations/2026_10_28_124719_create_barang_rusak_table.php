<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_rusak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang');
            $table->unsignedInteger('jumlah');
            $table->string('alasan');
            $table->enum('status', ['menunggu_keputusan', 'retur_supplier', 'write_off'])
                  ->default('menunggu_keputusan');
            $table->decimal('nilai_hpp', 12, 2)->default(0);   // nilai barang rusak menurut FIFO
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // pelapor
            $table->timestamp('diputuskan_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_rusak');
    }
};