<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Manejo especial para PostgreSQL - conversión segura de float a decimal
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // Tabla pagos
            if (Schema::hasTable('pagos')) {
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "interes" TYPE numeric(12,2) USING "interes"::numeric(12,2)');
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "capital" TYPE numeric(12,2) USING "capital"::numeric(12,2)');
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "saldo" TYPE numeric(12,2) USING "saldo"::numeric(12,2)');
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "monto_pagado" TYPE numeric(12,2) USING "monto_pagado"::numeric(12,2)');
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "penalizacion" TYPE numeric(12,2) USING "penalizacion"::numeric(12,2)');
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "capital_pagado" TYPE numeric(12,2) USING "capital_pagado"::numeric(12,2)');
                if (DB::connection()->getSchemaBuilder()->hasColumn('pagos', 'recargo')) {
                    DB::statement('ALTER TABLE "pagos" ALTER COLUMN "recargo" TYPE numeric(12,2) USING "recargo"::numeric(12,2)');
                }
            }

            // Tabla inversiones
            if (Schema::hasTable('inversiones')) {
                DB::statement('ALTER TABLE "inversiones" ALTER COLUMN "monto" TYPE numeric(12,2) USING "monto"::numeric(12,2)');
                DB::statement('ALTER TABLE "inversiones" ALTER COLUMN "interes" TYPE numeric(12,2) USING "interes"::numeric(12,2)');
            }

            // Tabla pago_inversions
            if (Schema::hasTable('pago_inversions')) {
                DB::statement('ALTER TABLE "pago_inversions" ALTER COLUMN "monto" TYPE numeric(12,2) USING "monto"::numeric(12,2)');
                // fecha_pago debe ser date, no numeric
                if (DB::connection()->getSchemaBuilder()->hasColumn('pago_inversions', 'fecha_pago')) {
                    DB::statement('ALTER TABLE "pago_inversions" ALTER COLUMN "fecha_pago" TYPE date USING "fecha_pago"::date');
                }
            }

            // Tabla fiducias
            if (Schema::hasTable('fiducias')) {
                DB::statement('ALTER TABLE "fiducias" ALTER COLUMN "monto" TYPE numeric(12,2) USING "monto"::numeric(12,2)');
                DB::statement('ALTER TABLE "fiducias" ALTER COLUMN "interes" TYPE numeric(12,2) USING "interes"::numeric(12,2)');
                DB::statement('ALTER TABLE "fiducias" ALTER COLUMN "interes_mora" TYPE numeric(12,2) USING "interes_mora"::numeric(12,2)');
            }

            // Tabla cuenta_bancarias
            if (Schema::hasTable('cuenta_bancarias')) {
                if (DB::connection()->getSchemaBuilder()->hasColumn('cuenta_bancarias', 'cuota')) {
                    DB::statement('ALTER TABLE "cuenta_bancarias" ALTER COLUMN "cuota" TYPE numeric(12,2) USING "cuota"::numeric(12,2)');
                }
            }

            // Tabla prestamo_hipotecarios
            if (Schema::hasTable('prestamo_hipotecarios')) {
                DB::statement('ALTER TABLE "prestamo_hipotecarios" ALTER COLUMN "monto" TYPE numeric(12,2) USING "monto"::numeric(12,2)');
                DB::statement('ALTER TABLE "prestamo_hipotecarios" ALTER COLUMN "interes" TYPE numeric(12,2) USING "interes"::numeric(12,2)');
                if (DB::connection()->getSchemaBuilder()->hasColumn('prestamo_hipotecarios', 'cuota')) {
                    DB::statement('ALTER TABLE "prestamo_hipotecarios" ALTER COLUMN "cuota" TYPE numeric(12,2) USING "cuota"::numeric(12,2)');
                }
            }

            // Tabla cuenta_interna
            if (Schema::hasTable('cuenta_interna')) {
                DB::statement('ALTER TABLE "cuenta_interna" ALTER COLUMN "ingreso" TYPE numeric(12,2) USING "ingreso"::numeric(12,2)');
                DB::statement('ALTER TABLE "cuenta_interna" ALTER COLUMN "egreso" TYPE numeric(12,2) USING "egreso"::numeric(12,2)');
            }
        } else {
            // Para MySQL/SQLite, usar las migraciones normales de change()
            // Las tablas ya se convertirán con las otras migraciones
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // Revertir a float (real en PostgreSQL)
            if (Schema::hasTable('pagos')) {
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "interes" TYPE real USING "interes"::real');
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "capital" TYPE real USING "capital"::real');
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "saldo" TYPE real USING "saldo"::real');
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "monto_pagado" TYPE real USING "monto_pagado"::real');
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "penalizacion" TYPE real USING "penalizacion"::real');
                DB::statement('ALTER TABLE "pagos" ALTER COLUMN "capital_pagado" TYPE real USING "capital_pagado"::real');
                if (DB::connection()->getSchemaBuilder()->hasColumn('pagos', 'recargo')) {
                    DB::statement('ALTER TABLE "pagos" ALTER COLUMN "recargo" TYPE real USING "recargo"::real');
                }
            }

            if (Schema::hasTable('inversiones')) {
                DB::statement('ALTER TABLE "inversiones" ALTER COLUMN "monto" TYPE real USING "monto"::real');
                DB::statement('ALTER TABLE "inversiones" ALTER COLUMN "interes" TYPE real USING "interes"::real');
            }

            if (Schema::hasTable('pago_inversions')) {
                DB::statement('ALTER TABLE "pago_inversions" ALTER COLUMN "monto" TYPE real USING "monto"::real');
                if (DB::connection()->getSchemaBuilder()->hasColumn('pago_inversions', 'fecha_pago')) {
                    DB::statement('ALTER TABLE "pago_inversions" ALTER COLUMN "fecha_pago" TYPE real USING "fecha_pago"::real');
                }
            }

            if (Schema::hasTable('fiducias')) {
                DB::statement('ALTER TABLE "fiducias" ALTER COLUMN "monto" TYPE real USING "monto"::real');
                DB::statement('ALTER TABLE "fiducias" ALTER COLUMN "interes" TYPE real USING "interes"::real');
                DB::statement('ALTER TABLE "fiducias" ALTER COLUMN "interes_mora" TYPE real USING "interes_mora"::real');
            }

            if (Schema::hasTable('cuenta_bancarias')) {
                if (DB::connection()->getSchemaBuilder()->hasColumn('cuenta_bancarias', 'cuota')) {
                    DB::statement('ALTER TABLE "cuenta_bancarias" ALTER COLUMN "cuota" TYPE real USING "cuota"::real');
                }
            }

            if (Schema::hasTable('prestamo_hipotecarios')) {
                DB::statement('ALTER TABLE "prestamo_hipotecarios" ALTER COLUMN "monto" TYPE real USING "monto"::real');
                DB::statement('ALTER TABLE "prestamo_hipotecarios" ALTER COLUMN "interes" TYPE real USING "interes"::real');
                if (DB::connection()->getSchemaBuilder()->hasColumn('prestamo_hipotecarios', 'cuota')) {
                    DB::statement('ALTER TABLE "prestamo_hipotecarios" ALTER COLUMN "cuota" TYPE real USING "cuota"::real');
                }
            }

            if (Schema::hasTable('cuenta_interna')) {
                DB::statement('ALTER TABLE "cuenta_interna" ALTER COLUMN "ingreso" TYPE real USING "ingreso"::real');
                DB::statement('ALTER TABLE "cuenta_interna" ALTER COLUMN "egreso" TYPE real USING "egreso"::real');
            }
        }
    }
};
