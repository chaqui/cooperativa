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
        Schema::create('tipo_cuenta_interna', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_banco');
            $table->string('tipo_cuenta');
            $table->string('numero_cuenta');

            $table->timestamps();
        });
        Schema::table('cuenta_interna', function (Blueprint $table) {
            $table->foreignId('tipo_cuenta_interna_id')->nullable()->constrained('tipo_cuenta_interna')->onDelete('cascade');
        });
        Schema::table('retiros', function (Blueprint $table) {
            $table->foreignId('tipo_cuenta_interna_id')->nullable()->constrained('tipo_cuenta_interna')->onDelete('cascade');
        });
        Schema::table('depositos', function (Blueprint $table) {
            $table->foreignId('tipo_cuenta_interna_id')->nullable()->constrained('tipo_cuenta_interna')->onDelete('cascade');
        });

        Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
            $table->float('cuota')->nullable();
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
