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
        Schema::rename('estado_prestamos', 'estado');
        Schema::table('inversiones', function (Blueprint $table) {
            $table->dropForeign(['id_estado']);
            $table->foreign('id_estado')->references('id')->on('estado');
        });
        Schema::dropIfExists('estado_inversiones');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('estado', 'estado_prestamos');
        Schema::create('estado_inversiones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::table('inversiones', function (Blueprint $table) {
            $table->dropForeign(['id_estado']);
            $table->foreign('id_estado')->references('id')->on('estado_inversiones');
        });
    }
};
