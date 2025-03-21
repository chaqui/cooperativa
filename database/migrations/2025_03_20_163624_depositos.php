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
        Schema::create('depositos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_documento');
            $table->string('numero_documento');
            $table->string('imagen')->nullable();
            $table->decimal('monto', 10, 2);
            $table->integer('id_inversion')->nullable();
            $table->integer('id_pago')->nullable();
            $table->boolean('realizado')->default(false);
            $table->foreign('id_inversion')->references('id')->on('inversiones')->onDelete('cascade');
            $table->foreign('id_pago')->references('id')->on('pagos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('retiros', function (Blueprint $table) {
            $table->id();
            $table->string('numero_cuenta')->unsigned()->nullable();
            $table->string('tipo_documento');
            $table->integer('id_prestamo')->nullable();
            $table->integer('id_pago_inversions')->nullable();
            $table->string('numero_documento');
            $table->decimal('monto', 10, 2);
            $table->string('motivo')->nullable();
            $table->string('imagen')->nullable();
            $table->string('beneficiario')->nullable();
            $table->boolean('realizado')->default(false);
            $table->timestamps();
            $table->foreign('id_pago_inversions')->references('id')->on('pago_inversions')->onDelete('cascade');
            $table->foreign('id_prestamo')->references('id')->on('prestamo_hipotecarios')->onDelete('cascade');
            $table->foreign('numero_cuenta')->references('numero_cuenta')->on('cuenta__bancarias')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
