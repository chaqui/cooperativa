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
        //TODO: descomentar cuando se cambie a Postgres
        //DB::statement('CREATE SEQUENCE correlativo_cliente START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar la secuencia
      //  DB::statement('DROP SEQUENCE IF EXISTS correlativo_cliente;');
    }
};
