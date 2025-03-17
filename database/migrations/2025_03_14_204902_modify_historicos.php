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
        Schema::rename('historial_prestamo_hipotecarios', 'historial');
        Schema::table('historial', function (Blueprint $table) {
            $table->bigInteger('id_prestamo')->nullable()->change();
            $table->bigInteger('id_inversion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('historial', 'historial_prestamo_hipotecarios');
        Schema::table('historial_prestamo_hipotecarios', function (Blueprint $table) {
            $table->bigInteger('id_prestamo')->nullable(false)->change();
            $table->dropColumn('id_inversion');
        });
    }
};
