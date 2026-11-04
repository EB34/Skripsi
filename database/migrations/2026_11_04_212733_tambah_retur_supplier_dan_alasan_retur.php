<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menghubungkan laporan barang rusak dengan retur ke supplier yang dibuat dari keputusan itu
        Schema::table('barang_rusak', function (Blueprint $table) {
            $table->foreignId('retur_supplier_id')
                ->nullable()
                ->after('user_id')
                ->constrained('retur_supplier')
                ->nullOnDelete();
        });

        // Pilihan alasan retur diperluas. Nilai lama (cacat, salah_kirim) tetap berlaku.
        DB::statement("ALTER TABLE detail_retur_supplier MODIFY alasan ENUM('cacat','salah_kirim','rusak_saat_pemasangan','rusak_saat_pengiriman','lainnya') NOT NULL DEFAULT 'cacat'");
    }

    public function down(): void
    {
        // Baris dengan alasan baru harus dikembalikan dulu, karena enum lama tidak memuatnya
        DB::table('detail_retur_supplier')
            ->whereIn('alasan', ['rusak_saat_pemasangan', 'rusak_saat_pengiriman', 'lainnya'])
            ->update(['alasan' => 'cacat']);

        DB::statement("ALTER TABLE detail_retur_supplier MODIFY alasan ENUM('cacat','salah_kirim') NOT NULL DEFAULT 'cacat'");

        Schema::table('barang_rusak', function (Blueprint $table) {
            $table->dropForeign(['retur_supplier_id']);
            $table->dropColumn('retur_supplier_id');
        });
    }
};