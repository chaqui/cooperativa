<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE correlativo_prestamo START WITH 10001 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;');
        DB::statement('CREATE SEQUENCE correlativo_inversion START WITH 10001 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP SEQUENCE IF EXISTS correlativo_prestamo;');
        DB::statement('DROP SEQUENCE IF EXISTS correlativo_inversion;');
    }
};
