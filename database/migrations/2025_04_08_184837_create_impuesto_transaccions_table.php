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
        Schema::create('impuesto_transaccions', function (Blueprint $table) {
            $table->id();
            $table->integer('id_declaracion_impuesto')->unsigned();
            $table->decimal('monto_impuesto', 15, 2)->default(0)->comment('Monto del impuesto');
            $table->date('fecha_transaccion')->comment('Fecha de la transacción');
            $table->string('descripcion')->nullable()->comment('Descripción de la transacción');
            $table->foreign('id_declaracion_impuesto')->references('id')->on('declaracion_impuestos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impuesto_transaccions');
    }
};
