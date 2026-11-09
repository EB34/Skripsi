<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom detail_pembelian (jumlah_dipesan, jumlah_diterima, jumlah_cacat, keterangan_cacat,
        // tanggal_masuk, sisa_jumlah default 0) sekarang sudah dibuat di migration create_detail_pembelian_table,
        // jadi tidak diubah lagi di sini.
        Schema::table('pembelian', function (Blueprint $table) {
            $table->string('nomor_po')->nullable()->unique()->after('id');
            $table->enum('status', ['menunggu_pengiriman', 'diterima_sebagian', 'diterima_lengkap'])
                  ->default('menunggu_pengiriman')->after('supplier_id');
            $table->text('catatan_penerima')->nullable()->after('status');
            $table->dateTime('diterima_pada')->nullable()->after('catatan_penerima');
        });
    }

    public function down(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->dropUnique(['nomor_po']);
            $table->dropColumn(['nomor_po', 'status', 'catatan_penerima', 'diterima_pada']);
        });
    }
};