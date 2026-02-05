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
        Schema::create('rollback_prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_hipotecario_id')->constrained('prestamo_hipotecarios')->onDelete('cascade');
            $table->json('datos_a_eliminar')->nullable();
            $table->json('datos_a_modificar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('roollback_prestamos');
    }
};
