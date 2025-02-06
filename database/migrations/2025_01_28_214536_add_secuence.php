<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear la secuencia

        DB::statement('CREATE SEQUENCE correlativo_cliente START WITH 10001 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar la secuencia
      DB::statement('DROP SEQUENCE IF EXISTS correlativo_cliente;');
    }
};
