<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pembelian', function (Blueprint $table) {
            $table->id();
            // Boleh kosong: batch hasil koreksi stok (opname) tidak berasal dari PO
            $table->foreignId('pembelian_id')->nullable()->constrained('pembelian')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barang');

            // Khusus PO lensa custom: baris nota yang dipesankan (satu baris nota = satu PO). Tanpa foreign key.
            $table->unsignedBigInteger('detail_penjualan_id')->nullable()->unique();
            $table->string('keterangan_ukuran', 150)->nullable();   // salinan ukuran dari nota

            $table->unsignedInteger('jumlah_dipesan')->default(0);
            $table->unsignedInteger('jumlah_diterima')->default(0);
            $table->unsignedInteger('jumlah_cacat')->default(0);
            $table->string('keterangan_cacat', 255)->nullable();
            $table->decimal('harga_beli', 12, 2);
            $table->unsignedInteger('sisa_jumlah')->default(0);     // sisa batch buat FIFO (selalu 0 untuk custom)
            $table->timestamp('tanggal_masuk')->nullable();
            $table->timestamps();

            $table->index(['barang_id', 'sisa_jumlah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pembelian');
    }
};