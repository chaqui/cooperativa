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
        Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
            $table->dropColumn('fecha_aprobacion');
            $table->dropColumn('fecha_desembolso');
            $table->dropColumn('fecha_finalizacion');
            $table->dropColumn('fecha_cancelacion');
        });

        Schema::create('historial_prestamo_hipotecarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_prestamo');
            $table->unsignedBigInteger('id_estado');
            $table->string('razon')->nullable();
            $table->date('fecha');
            $table->string('anotacion')->nullable();
            $table->string('no_documento_desembolso')->nullable();
            $table->string('tipo_documento_desembolso')->nullable();
            $table->timestamps();

            $table->foreign('id_prestamo')->references('id')->on('prestamo_hipotecarios');
            $table->foreign('id_estado')->references('id')->on('estado_prestamos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
            $table->string('razon_cancelacion')->nullable();
            $table->date('fecha_aprobacion')->nullable();
            $table->date('fecha_desembolso')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            $table->date('fecha_cancelacion')->nullable();
        });

        Schema::dropIfExists('historial_prestamo_hipotecarios');
    }
};
