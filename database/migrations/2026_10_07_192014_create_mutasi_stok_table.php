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
        Schema::create('mutasi_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang');
            $table->string('jenis_kejadian'); // barang_masuk, terjual, retur_supplier, retur_customer, rusak, write_off
            $table->integer('jumlah');        // negatif jika stok berkurang
            $table->integer('saldo_setelah');
            $table->dateTime('tanggal');
            $table->string('referensi_tipe')->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->string('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['barang_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_stok');
    }
};
