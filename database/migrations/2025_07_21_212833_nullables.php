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
        Schema::table('propiedades', function (Blueprint $table) {
            $table->string('Direccion')->nullable()->change();
            $table->decimal('Valor_tasacion', 15, 2)->nullable()->change();
            $table->decimal('Valor_comercial', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propiedades', function (Blueprint $table) {
            $table->string('Direccion')->nullable(false)->change();
            $table->decimal('Valor_tasacion', 15, 2)->nullable(false)->change();
            $table->decimal('Valor_comercial', 15, 2)->nullable(false)->change();
        });
    }
};
