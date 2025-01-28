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
        Schema::create('references', function (Blueprint $table) {
            $table->string('nombre')->nullable();
            $table->string('telefono')->nullable();
            $table->enum('tipo', ['personal', 'laboral', 'familiar','comercial'])->default('personal');
            $table->string('dpi_cliente')->unsigned();
            $table->foreign('dpi_cliente')->references('dpi')->on('clients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('references');
    }
};
