<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No invoice dari supplier (beda dengan nomor PO buatan toko)
        Schema::table('pembelian', function (Blueprint $table) {
            $table->string('nomor_invoice_supplier', 100)->nullable()->after('nomor_po');
        });

        Schema::table('retur_supplier', function (Blueprint $table) {
            $table->string('jenis_retur', 20)->nullable()->after('status');          // tukar_barang / potong_tagihan
            $table->decimal('nilai_potongan', 12, 2)->default(0)->after('jenis_retur'); // potongan tagihan PO
        });

        Schema::table('detail_retur_supplier', function (Blueprint $table) {
            $table->string('keterangan', 255)->nullable()->after('alasan');          // keterangan di surat jalan
            $table->unsignedInteger('jumlah_pengganti')->default(0)->after('keterangan');
            $table->unsignedInteger('jumlah_pengganti_cacat')->default(0)->after('jumlah_pengganti');
        });

        // Keterangan kerusakan yang diminta saat barang dilaporkan rusak
        Schema::table('barang_rusak', function (Blueprint $table) {
            $table->string('keterangan', 255)->nullable()->after('alasan');
        });

        // Retur lama yang sudah divalidasi dianggap jenis "ganti barang"
        DB::table('retur_supplier')
            ->whereNull('jenis_retur')
            ->where('status', '!=', 'diajukan')
            ->update(['jenis_retur' => 'tukar_barang']);
    }

    public function down(): void
    {
        Schema::table('barang_rusak', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });

        Schema::table('detail_retur_supplier', function (Blueprint $table) {
            $table->dropColumn(['keterangan', 'jumlah_pengganti', 'jumlah_pengganti_cacat']);
        });

        Schema::table('retur_supplier', function (Blueprint $table) {
            $table->dropColumn(['jenis_retur', 'nilai_potongan']);
        });

        Schema::table('pembelian', function (Blueprint $table) {
            $table->dropColumn('nomor_invoice_supplier');
        });
    }
};