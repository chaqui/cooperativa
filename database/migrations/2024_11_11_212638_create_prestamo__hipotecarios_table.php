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
        Schema::create('prestamo_hipotecarios', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_fin');
            $table->date('fecha_inicio');
            $table->float('monto');
            $table->float('interes');
            $table->integer('plazo');
            $table->integer('estado')->unsigned();
            $table->string('cliente')->unsigned();
            $table->integer('tipo_taza')->unsigned();
            $table->integer('tipo_plazo')->unsigned();
            $table->integer("propiedad")->unsigned();
            $table->foreign('tipo_taza')->references('id')->on('tipo_tasa_interes');
            $table->foreign('tipo_plazo')->references('id')->on('tipo_plazos');
            $table->foreign('estado')->references('id')->on('estado_prestamos');
            $table->foreign('cliente')->references('dpi')->on('clients');
            $table->foreign('propiedad')->references('id')->on('propiedades');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamo__hipotecarios');
    }
};
