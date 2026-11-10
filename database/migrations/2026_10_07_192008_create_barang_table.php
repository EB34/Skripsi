<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kategori barang (material frame, tipe lensa, tipe softlens). Dibuat sebelum barang karena barang merujuk ke sini.
        Schema::create('kategori_barang', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['frame', 'lensa', 'softlens']);
            $table->string('nama', 100);
            $table->timestamps();

            $table->unique(['jenis', 'nama']); // nama yang sama boleh asal jenisnya beda
        });

        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            // DIUBAH: kode_model tidak unik. Kode yang sama boleh dipakai berulang, termasuk untuk merk yang sama.
            $table->string('kode_model');
            $table->enum('jenis', ['frame', 'lensa', 'softlens']);
            $table->string('merk');
            // Kategori opsional. Tidak bisa dihapus kalau masih dipakai barang.
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_barang')->restrictOnDelete();
            // Ukuran lensa (hanya terisi untuk jenis lensa).
            $table->decimal('sph', 4, 2)->nullable();
            $table->decimal('cyl', 4, 2)->nullable();
            $table->decimal('harga_jual', 12, 2)->default(0);
            $table->unsignedInteger('stok_tersedia')->default(0);
            $table->unsignedInteger('stok_rusak')->default(0);
            $table->unsignedInteger('stok_minimum')->default(5);
            $table->boolean('tanpa_stok')->default(false);
            $table->timestamps();

            // BARU: index untuk pengurutan merk A-Z, lalu kode model
            $table->index(['merk', 'kode_model']);
            // BARU: index untuk pencarian kode model
            $table->index('kode_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang');
        Schema::dropIfExists('kategori_barang');
    }
};