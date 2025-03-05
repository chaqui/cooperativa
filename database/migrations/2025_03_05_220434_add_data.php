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
            $table->date('fecha_aprobacion')->nullable();
            $table->date('fecha_desembolso')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            $table->date('fecha_cancelacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
            $table->dropColumn('fecha_aprobacion');
            $table->dropColumn('fecha_desembolso');
            $table->dropColumn('fecha_finalizacion');
            $table->dropColumn('fecha_cancelacion');
        });
    }
};
