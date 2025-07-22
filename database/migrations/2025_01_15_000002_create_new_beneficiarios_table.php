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
        Schema::create('beneficiarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->comment('Nombre del beneficiario');
            $table->string('parentezco')->comment('Parentesco con el cliente');
            $table->integer('porcentaje')->default(0)->comment('Porcentaje de participación');
            $table->string('dpi_cliente')->comment('DPI del cliente asociado');
            $table->string('afinidad')->nullable()->comment('Afinidad con el cliente');
            $table->timestamps();

            $table->foreign('dpi_cliente')
                ->references('dpi')
                ->on('clients')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiarios');
    }
};
