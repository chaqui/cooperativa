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
        Schema::create('firmantes__contratos', function (Blueprint $table) {
            $table->id();
            $table->integer('id_contrato')->unsigned();
            $table->string('dpi_firmante')->unsigned();
            $table->foreign('id_contrato')->references('id')->on('contratos');
            $table->foreign('dpi_firmante')->references('dpi')->on('clients');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firmantes__contratos');
    }
};
