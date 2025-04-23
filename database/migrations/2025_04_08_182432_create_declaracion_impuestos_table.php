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
        Schema::create('declaracion_impuestos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('id_tipo_impuesto')->unsigned();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('numero_formulario')->nullable();
            $table->date('fecha_presentacion')->nullable();
            $table->boolean('presentado')->default(false)->comment('Indica si la declaración ha sido presentada');
            $table->foreign('id_tipo_impuesto')->references('id')->on('tipo_impuestos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declaracion_impuestos');
    }
};
