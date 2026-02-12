<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Manejo especial para PostgreSQL con conversión de tipos
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // Para PostgreSQL, usar SQL raw para conversión segura
            DB::statement('ALTER TABLE "clients" ALTER COLUMN "ingresos_mensuales" TYPE numeric(12,2) USING "ingresos_mensuales"::numeric(12,2)');
            DB::statement('ALTER TABLE "clients" ALTER COLUMN "limite_credito" TYPE numeric(12,2) USING "limite_credito"::numeric(12,2)');
            DB::statement('ALTER TABLE "clients" ALTER COLUMN "credito_disponible" TYPE numeric(12,2) USING "credito_disponible"::numeric(12,2)');
            DB::statement('ALTER TABLE "clients" ALTER COLUMN "capacidad_pago" TYPE numeric(12,2) USING "capacidad_pago"::numeric(12,2)');
        } else {
            // Para MySQL/SQLite, usar change() normal
            Schema::table('clients', function (Blueprint $table) {
                $table->decimal('ingresos_mensuales', 12, 2)->change();
                $table->decimal('limite_credito', 12, 2)->nullable()->change();
                $table->decimal('credito_disponible', 12, 2)->nullable()->change();
                $table->decimal('capacidad_pago', 12, 2)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "clients" ALTER COLUMN "ingresos_mensuales" TYPE real USING "ingresos_mensuales"::real');
            DB::statement('ALTER TABLE "clients" ALTER COLUMN "limite_credito" TYPE real USING "limite_credito"::real');
            DB::statement('ALTER TABLE "clients" ALTER COLUMN "credito_disponible" TYPE real USING "credito_disponible"::real');
            DB::statement('ALTER TABLE "clients" ALTER COLUMN "capacidad_pago" TYPE real USING "capacidad_pago"::real');
        } else {
            Schema::table('clients', function (Blueprint $table) {
                $table->float('ingresos_mensuales')->change();
                $table->float('limite_credito')->nullable()->change();
                $table->float('credito_disponible')->nullable()->change();
                $table->float('capacidad_pago')->nullable()->change();
            });
        }
    }
};
