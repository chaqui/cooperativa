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
        Schema::create('clients', function (Blueprint $table) {
            $table->string("dpi")->unique()->primary();
            $table->string("nombres");
            $table->string("apellidos");
            $table->string("telefono");
            $table->string("correo")->unique()->nullable();
            $table->string("direccion");
            $table->string("ciudad");
            $table->string("departamento");
            $table->string("estado_civil");
            $table->string("genero");
            $table->string("nivel_academico");
            $table->string("profesion");
            $table->date("fecha_nacimiento");
            $table->integer("etado_cliente")->unsigned();
            $table->float("limite_credito")->nullable();
            $table->float("credito_disponible")->nullable();
            $table->float("ingresos_mensuales");
            $table->float("egresos_mensuales");
            $table->float("capacidad_pago");
            $table->float("calificacion");
            $table->date("fecha_actualizacion_calificacion")->nullable();
            $table->foreign("etado_cliente")->references("id")->on("estado_clientes");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
