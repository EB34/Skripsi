<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualan')->cascadeOnDelete();
            $table->enum('jenis', ['penuh', 'dp', 'pelunasan']);
            $table->enum('metode', ['tunai', 'qris', 'transfer', 'kartu']);
            $table->decimal('jumlah', 12, 2);                      // nilai yang masuk ke nota
            $table->decimal('uang_diterima', 12, 2)->nullable();   // uang tunai yang diserahkan (untuk kembalian)
            $table->dateTime('dibayar_pada');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_penjualan');
    }
};