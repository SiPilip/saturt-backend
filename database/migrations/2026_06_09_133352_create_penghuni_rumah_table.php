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
        Schema::create('penghuni_rumah', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_penghuni');
            $table->uuid('id_rumah');
            $table->date('tanggal_masuk');
            $table->date('tanggal_keluar')->nullable();
            $table->timestamps();

            $table->foreign('id_penghuni')
                ->references('id')
                ->on('penghuni')
                ->cascadeOnDelete();

            $table->foreign('id_rumah')
                ->references('id')
                ->on('rumah')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penghuni_rumah');
    }
};
