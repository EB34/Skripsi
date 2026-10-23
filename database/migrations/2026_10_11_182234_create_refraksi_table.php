<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refraksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customer')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_periksa');

            // Mata kanan (OD)
            $table->decimal('od_sphere', 5, 2)->nullable();
            $table->decimal('od_cylinder', 5, 2)->nullable();
            $table->unsignedSmallInteger('od_axis')->nullable();
            $table->decimal('od_add', 4, 2)->nullable();
            $table->decimal('od_mono_pd', 4, 1)->nullable();

            // Mata kiri (OS)
            $table->decimal('os_sphere', 5, 2)->nullable();
            $table->decimal('os_cylinder', 5, 2)->nullable();
            $table->unsignedSmallInteger('os_axis')->nullable();
            $table->decimal('os_add', 4, 2)->nullable();
            $table->decimal('os_mono_pd', 4, 1)->nullable();

            $table->decimal('pd_total', 4, 1)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refraksi');
    }
};