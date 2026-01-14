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
        Schema::create('clients_changes', function (Blueprint $table) {
            $table->id();

            $table->json('cambios')->comment('Cambios realizados en el cliente');
            $table->string('dpi_cliente');
            $table->string('usuario_modifico')->nullable();
            $table->foreign('dpi_cliente')->references('dpi')->on('clients');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients_changes');
    }
};
