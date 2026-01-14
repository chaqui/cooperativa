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
        DB::statement("
            CREATE OR REPLACE VIEW view_clients AS
            SELECT
                c.dpi AS client_dpi,
                c.nombres || ' ' || c.apellidos AS nombre_completo,
                c.telefono AS telefono,
                c.correo AS correo,
                c.direccion AS direccion,
                c.genero AS genero,
                c.fecha_nacimiento AS fecha_nacimiento,
                c.codigo AS codigo_cliente
            FROM
                clients c
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_clients");
    }
};
