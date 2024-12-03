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
            $table->dropForeign(['propiedad']);
            $table->dropColumn('propiedad');
            $table->integer('propiedad_id')->unsigned();
            $table->foreign('propiedad_id')->references('id')->on('propiedades');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
            $table->dropForeign(['propiedad_id']);
            $table->dropColumn('propiedad_id');
            $table->string('propiedad');
            $table->foreign('propiedad')->references('id')->on('propiedades');
        });
    }
};
