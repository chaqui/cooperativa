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
        Schema::create('inversiones', function (Blueprint $table) {
            $table->id();
            $table->float('monto');
            $table->date('fecha');
            $table->float('interes');
            $table->integer('plazo');
            $table->integer('estado')->unsigned();
            $table->integer('cliente')->unsigned();
            $table->integer('tipo_taza')->unsigned();
            $table->integer('tipo_plazo')->unsigned();
            $table->integer('tipo_inversion')->unsigned();
            $table->integer('cuenta_recaudadora')->unsigned();
            $table->foreign('tipo_inversion')->references('id')->on('tipo_inversiones');
            $table->foreign('tipo_taza')->references('id')->on('tipo_tasa_interes');
            $table->foreign('tipo_plazo')->references('id')->on('tipo_plazos');
            $table->foreign('estado')->references('id')->on('estado_inversiones');
            $table->foreign('cliente')->references('dpi')->on('clientes');
            $table->foreign('cuenta_recaudadora')->references('id')->on('cuentas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inversions');
    }
};
