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
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('numero_contrato');
            $table->string('dpi_cliente')->unsigned();
            $table->integer('id_hipoteca')->unsigned()->nullable();
            $table->integer('id_inversion')->unsigned()->nullable();
            $table->integer('id_fiducia')->unsigned()->nullable();
            $table->foreign('dpi_cliente')->references('dpi')->on('clients');
            $table->foreign('id_hipoteca')->references('id')->on('prestamo_hipotecarios');
            $table->foreign('id_inversion')->references('id')->on('inversiones');
            $table->foreign('id_fiducia')->references('id')->on('fiducias');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
