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
            $table->string(column: 'nit')->nullable();
            $table->string(column: 'puesto')->nullable();
            $table->date(column: 'fechaInicio')->nullable();
            $table->string('tipoCliente')->nullable();
            $table->double(column: 'otrosIngresos')->nullable();
            $table->string(column: 'numeroPatente')->nullable();
            $table->string(column: 'nombreEmpresa')->nullable();
            $table->string(column: 'telefonoEmpresa')->nullable();
            $table->string(column: 'direccionEmpresa')->nullable();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('nit');
            $table->dropColumn('puesto');
            $table->dropColumn('fechaInicio');
            $table->dropColumn('tipoCliente');
            $table->dropColumn('otrosIngresos');
            $table->dropColumn('numeroPatente');
            $table->dropColumn('nombreEmpresa');
            $table->dropColumn('telefonoEmpresa');
            $table->dropColumn('direccionEmpresa');

        });
    }
};
