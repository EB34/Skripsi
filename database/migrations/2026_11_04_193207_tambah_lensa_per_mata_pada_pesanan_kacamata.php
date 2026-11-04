<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan_kacamata', function (Blueprint $table) {
            // Baris nota (detail_penjualan) yang dipakai untuk tiap mata. Kosong = tidak memakai mode nota.
            $table->foreignId('lensa_od_detail_id')->nullable()->after('jumlah_lensa')
                ->constrained('detail_penjualan')->nullOnDelete();
            $table->foreignId('lensa_os_detail_id')->nullable()->after('lensa_od_detail_id')
                ->constrained('detail_penjualan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_kacamata', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lensa_od_detail_id');
            $table->dropConstrainedForeignId('lensa_os_detail_id');
        });
    }
};