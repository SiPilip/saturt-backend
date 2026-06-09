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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_tagihan')->unique(); // 1 tagihan = 1 pembayaran
            $table->uuid('id_penghuni');
            $table->integer('jumlah_bayar');
            $table->date('tanggal_bayar');
            $table->timestamps();

            $table->foreign('id_tagihan')
                ->references('id')
                ->on('tagihan')
                ->cascadeOnDelete();

            $table->foreign('id_penghuni')
                ->references('id')
                ->on('penghuni')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
