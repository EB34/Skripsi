<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_nota')->nullable()->unique();
            $table->date('tanggal');
            $table->foreignId('customer_id')->nullable()->constrained('customer')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('diskon', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('metode_pembayaran', ['tunai', 'qris', 'transfer', 'kartu'])->default('tunai');
            $table->decimal('uang_diterima', 12, 2)->nullable();
            // berjalan = masih menunggu pesanan/pelunasan, selesai = tuntas, batal = pesanan dibatalkan (DP hangus)
            $table->enum('status', ['berjalan', 'selesai', 'batal'])->default('selesai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan');
    }
};