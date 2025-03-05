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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('conyuge')->nullable();
            $table->integer('cargas_familiares')->nullable();
            $table->integer('integrantes_nucleo_familiar')->nullable();
            $table->string('tipo_vivienda')->nullable();
            $table->integer('estabilidad_domiciliaria')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Drop columns
            $table->dropColumn('conyuge');
            $table->dropColumn('cargas_familiares');
            $table->dropColumn('integrantes_nucleo_familiar');
            $table->dropColumn('tipo_vivienda');
            $table->dropColumn('estabilidad_domiciliaria');
        });
    }
};
