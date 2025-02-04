<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string("correo")->nullable()->change();
            $table->float("capacidad_pago")->nullable()->change();
            $table->float("credito_disponible")->nullable()->change();
            $table->float("credito_disponible")->nullable()->change();
            $table->date('calificacion_temp')->nullable();
            // Paso 3: Eliminar la columna original
            $table->dropColumn('calificacion');
            // Paso 4: Renombrar la columna temporal
            $table->renameColumn('calificacion_temp', 'calificacion');
            $table->date("fecha_actualizacion_calificacion")->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string("correo")->nullable(false)->change();
            $table->float("capacidad_pago")->nullable(false)->change();
            $table->float("credito_disponible")->nullable(false)->change();
            $table->float("credito_disponible")->nullable(false)->change();
            $table->date("calificacion")->nullable(false)->change();
            $table->date("fecha_actualizacion_calificacion")->nullable(false)->change();
        });
    }
};
