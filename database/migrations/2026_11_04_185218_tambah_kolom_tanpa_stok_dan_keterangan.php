<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('barang', 'tanpa_stok')) {
            Schema::table('barang', function (Blueprint $table) {
                $table->boolean('tanpa_stok')->default(false)->after('stok_minimum');
            });
        }

        if (! Schema::hasColumn('detail_penjualan', 'jumlah_kembali')) {
            Schema::table('detail_penjualan', function (Blueprint $table) {
                $table->unsignedInteger('jumlah_kembali')->default(0)->after('jumlah');
            });
        }

        if (! Schema::hasColumn('detail_penjualan', 'keterangan')) {
            Schema::table('detail_penjualan', function (Blueprint $table) {
                $table->string('keterangan', 150)->nullable()->after('hpp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('detail_penjualan', 'keterangan')) {
            Schema::table('detail_penjualan', fn (Blueprint $t) => $t->dropColumn('keterangan'));
        }
        if (Schema::hasColumn('detail_penjualan', 'jumlah_kembali')) {
            Schema::table('detail_penjualan', fn (Blueprint $t) => $t->dropColumn('jumlah_kembali'));
        }
        if (Schema::hasColumn('barang', 'tanpa_stok')) {
            Schema::table('barang', fn (Blueprint $t) => $t->dropColumn('tanpa_stok'));
        }
    }
};