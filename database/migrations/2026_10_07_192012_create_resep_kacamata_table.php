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
        Schema::create('resep_kacamata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->unique()->constrained('pesanan_kacamata')->cascadeOnDelete();
            $table->decimal('sphere', 4, 2)->nullable();
            $table->decimal('cylinder', 4, 2)->nullable();
            $table->unsignedSmallInteger('axis')->nullable();
            $table->decimal('add', 4, 2)->nullable();
            $table->decimal('pd', 4, 1)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resep_kacamata');
    }
};
