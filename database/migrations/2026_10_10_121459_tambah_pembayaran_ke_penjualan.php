<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->string('nomor_nota')->nullable()->unique()->after('id');
            $table->enum('metode_pembayaran', ['tunai', 'qris', 'transfer', 'kartu'])
                  ->default('tunai')->after('diskon');
            $table->decimal('uang_diterima', 12, 2)->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropUnique(['nomor_nota']);
            $table->dropColumn(['nomor_nota', 'metode_pembayaran', 'uang_diterima']);
        });
    }
};