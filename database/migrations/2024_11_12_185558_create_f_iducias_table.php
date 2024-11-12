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
        Schema::create('fiducias', function (Blueprint $table) {
            $table->id();
            $table->integer('id_tipo_fiducia')->unsigned();
            $table->integer('id_cliente')->unsigned();
            $table->float('monto');
            $table->integer('plazo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('id_tipo_plazo')->unsigned();
            $table->integer('id_estado_fiducia')->unsigned();
            $table->float('interes');
            $table->float('interes_mora');
            $table->float('id_tipo_interes')->unsigned();
            $table->foreign('id_tipo_fiducia')->references('id')->on('tipo_fiducias');
            $table->foreign('id_cliente')->references('id')->on('clientes');
            $table->foreign('id_tipo_plazo')->references('id')->on('tipo_plazos');
            $table->foreign('id_estado_fiducia')->references('id')->on('estado_prestamos');
            $table->foreign('id_tipo_interes')->references('id')->on('tipo_tasa_interes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('f_iducias');
    }
};
