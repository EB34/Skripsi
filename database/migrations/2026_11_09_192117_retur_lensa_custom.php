<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_pembelian', function (Blueprint $table) {
            // Satu baris nota boleh punya lebih dari satu baris PO (pesan ulang setelah retur potong tagihan)
            $table->dropUnique(['detail_penjualan_id']);
            $table->index('detail_penjualan_id');

            // True kalau lensa custom di baris PO ini sudah diretur (potong tagihan), jadi boleh dipesan ulang
            $table->boolean('diretur')->default(false)->after('keterangan_ukuran');
        });

        Schema::table('detail_retur_supplier', function (Blueprint $table) {
            // Baris PO yang diretur. Hanya terisi untuk lensa custom (barang_id saja tidak cukup karena
            // beberapa lensa custom dalam satu PO memakai barang_id yang sama).
            $table->unsignedBigInteger('detail_pembelian_id')->nullable()->after('barang_id');
            $table->index('detail_pembelian_id');
        });
    }

    public function down(): void
    {
        Schema::table('detail_retur_supplier', function (Blueprint $table) {
            $table->dropIndex(['detail_pembelian_id']);
            $table->dropColumn('detail_pembelian_id');
        });

        Schema::table('detail_pembelian', function (Blueprint $table) {
            $table->dropColumn('diretur');
            $table->dropIndex(['detail_penjualan_id']);
            $table->unique('detail_penjualan_id');
        });
    }
};