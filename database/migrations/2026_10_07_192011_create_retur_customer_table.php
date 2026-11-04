<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualan');
            $table->foreignId('barang_id')->constrained('barang');          // frame yang dikembalikan
            $table->text('keluhan');
            $table->date('tanggal');
            $table->string('status', 20)->default('dicatat');               // dicatat / selesai
            $table->string('status_konfirmasi', 30)->nullable();            // supplier_tersedia / supplier_habis
            $table->string('tindak_lanjut', 30)->nullable();                // tukar_supplier / tukar_stok_toko / refund
            $table->foreignId('barang_pengganti_id')->nullable()->constrained('barang');
            $table->decimal('jumlah_refund', 12, 2)->default(0);
            $table->decimal('nilai_kerugian', 12, 2)->default(0);           // HPP frame pengganti dari stok toko
            $table->string('catatan_owner')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retur_customer');
    }
};