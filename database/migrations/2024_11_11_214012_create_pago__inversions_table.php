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
        Schema::create('pago_inversions', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->float('monto');
            $table->float('fecha_pago');
            $table->boolean('realizado');
            $table->integer('inversion_id')->unsigned();
            $table->foreign('inversion_id')->references('id')->on('inversiones');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago__inversions');
    }
};
