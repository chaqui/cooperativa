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
        Schema::dropIfExists('beneficiarios');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('beneficiarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('parentezco');
            $table->integer('porcentaje')->default(0)->comment('Porcentaje de participación');
            $table->integer('id_inversion')->unsigned()->comment('ID de la inversión asociada');
            $table->foreign('id_inversion')->references('id')->on('inversiones')->onDelete('cascade');
        });
    }
};
