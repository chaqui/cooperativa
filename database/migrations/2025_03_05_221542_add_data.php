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
            $table->string('razon_cancelacion')->nullable();
            $table->dropForeign('prestamo_hipotecarios_estado_foreign');
            $table->dropColumn('estado');
            $table->integer('estado')->unsigned()->nullable( );
            $table->foreign('estado')->references('id')->on('estado_prestamos');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
            $table->dropColumn('razon_cancelacion');
            $table->dropForeign('prestamo_hipotecarios_estado_foreign');
            $table->dropColumn('estado');
            $table->integer('estado')->unsigned();
            $table->foreign('estado')->references('id')->on('estado_prestamos');
        });
    }
};
