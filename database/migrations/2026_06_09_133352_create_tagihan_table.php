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
        Schema::create('tagihan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_rumah');
            $table->uuid('id_iuran');
            $table->uuid('id_penghuni')->nullable();
            $table->tinyInteger('bulan');
            $table->smallInteger('tahun');
            $table->integer('nominal');
            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->foreign('id_rumah')
                ->references('id')
                ->on('rumah')
                ->cascadeOnDelete();

            $table->foreign('id_iuran')
                ->references('id')
                ->on('iuran')
                ->cascadeOnDelete();

            $table->foreign('id_penghuni')
                ->references('id')
                ->on('penghuni')
                ->nullOnDelete();

            // Mencegah duplikasi tagihan per rumah per iuran per bulan/tahun
            $table->unique(['id_rumah', 'id_iuran', 'bulan', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
