<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kerugian_operasional', function (Blueprint $table) {
            $table->id();
            // string, bukan enum: jenis baru (misal kesalahan_toko) bisa ditambah tanpa ubah tabel
            $table->string('jenis', 40);
            $table->foreignId('barang_id')->nullable()->constrained('barang')->nullOnDelete();
            $table->unsignedInteger('jumlah')->default(0);
            $table->decimal('nilai', 12, 2)->default(0);       // nilai kerugian (berdasarkan HPP FIFO)
            $table->dateTime('tanggal');
            $table->string('referensi_tipe', 40)->nullable();  // barang_rusak, opname, dst.
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->string('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal');
            $table->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kerugian_operasional');
    }
};