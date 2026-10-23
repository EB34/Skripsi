<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->string('nomor_po')->nullable()->unique()->after('id');
            $table->enum('status', ['menunggu_pengiriman', 'diterima_sebagian', 'diterima_lengkap'])
                  ->default('menunggu_pengiriman')->after('supplier_id');
            $table->text('catatan_penerima')->nullable()->after('status');
            $table->dateTime('diterima_pada')->nullable()->after('catatan_penerima');
        });

        Schema::table('detail_pembelian', function (Blueprint $table) {
            $table->renameColumn('jumlah', 'jumlah_dipesan');
        });

        Schema::table('detail_pembelian', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_diterima')->default(0)->after('jumlah_dipesan');
            $table->unsignedInteger('jumlah_cacat')->default(0)->after('jumlah_diterima');
            $table->string('keterangan_cacat')->nullable()->after('jumlah_cacat');
            $table->dateTime('tanggal_masuk')->nullable()->after('sisa_jumlah');
        });

        Schema::table('detail_pembelian', function (Blueprint $table) {
            $table->unsignedInteger('sisa_jumlah')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('detail_pembelian', function (Blueprint $table) {
            $table->dropColumn(['jumlah_diterima', 'jumlah_cacat', 'keterangan_cacat', 'tanggal_masuk']);
        });

        Schema::table('detail_pembelian', function (Blueprint $table) {
            $table->renameColumn('jumlah_dipesan', 'jumlah');
        });

        Schema::table('pembelian', function (Blueprint $table) {
            $table->dropUnique(['nomor_po']);
            $table->dropColumn(['nomor_po', 'status', 'catatan_penerima', 'diterima_pada']);
        });
    }
};