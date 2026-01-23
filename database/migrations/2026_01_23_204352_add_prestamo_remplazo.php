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
        Schema::create('prestamos_reemplazo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prestamo_cancelado');
            $table->unsignedBigInteger('prestamo_remplazo');
            $table->foreign('prestamo_cancelado')->references('id')->on('prestamo_hipotecarios');
            $table->foreign('prestamo_remplazo')->references('id')->on('prestamo_hipotecarios');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos_reemplazo');
    }
};
