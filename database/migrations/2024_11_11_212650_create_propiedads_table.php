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
        Schema::create('propiedades', function (Blueprint $table) {
            $table->id();
            $table->string('Direccion');
            $table->string('Descripcion');
            $table->float('Valor_tasacion');
            $table->float('Valor_comercial');
            $table->integer('tipo_propiedad')->unsigned();
            $table->foreign('tipo_propiedad')->references('id')->on('tipo_propiedad');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propiedads');
    }
};
