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
        Schema::table('propiedades', function (Blueprint $table) {
            $table->dropForeign(['tipo_propiedad']);
        });
        Schema::dropIfExists('tipo_propiedad');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::create('tipo_propiedad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });
        Schema::table('propiedades', function (Blueprint $table) {
            $table->foreign('tipo_propiedad')->references('id')->on('tipo_propiedades');
        });
    }
};
