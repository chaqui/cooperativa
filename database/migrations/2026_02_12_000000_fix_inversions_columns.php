<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convierte todos los campos float a decimal en todas las tablas
     */
    public function up(): void
    {
        // Corregir tabla inversiones
        Schema::table('inversiones', function (Blueprint $table) {
            $table->decimal('monto', 12, 2)->change();
            $table->decimal('interes', 12, 2)->change();
        });

        // Corregir tabla pago_inversions
        Schema::table('pago_inversions', function (Blueprint $table) {
            $table->decimal('monto', 12, 2)->change();
            // fecha_pago está mal tipado como float, debe ser date
            $table->date('fecha_pago')->change();
            $table->boolean('existente')->default(false)->after('realizado');
        });

        // Corregir tabla fiducias
        if (Schema::hasTable('fiducias')) {
            Schema::table('fiducias', function (Blueprint $table) {
                $table->decimal('monto', 12, 2)->change();
                $table->decimal('interes', 12, 2)->change();
                $table->decimal('interes_mora', 12, 2)->change();
            });
        }

        // Corregir tabla cuenta_bancarias
        if (Schema::hasTable('cuenta_bancarias')) {
            Schema::table('cuenta_bancarias', function (Blueprint $table) {
                $table->decimal('cuota', 12, 2)->nullable()->change();
            });
        }

        // Corregir tabla clients - Se maneja en migración separada para PostgreSQL
        // if (Schema::hasTable('clients')) {
        //     Schema::table('clients', function (Blueprint $table) {
        //         $table->decimal('limite_credito', 12, 2)->nullable()->change();
        //         $table->decimal('credito_disponible', 12, 2)->nullable()->change();
        //         $table->decimal('ingresos_mensuales', 12, 2)->change();
        //         $table->decimal('capacidad_pago', 12, 2)->nullable()->change();
        //     });
        // }

        // Corregir tabla prestamo_hipotecarios (si aún tiene float)
        if (Schema::hasTable('prestamo_hipotecarios')) {
            Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
                $table->decimal('monto', 12, 2)->change();
                $table->decimal('interes', 12, 2)->change();
                $table->decimal('cuota', 12, 2)->nullable()->change();
            });
        }

        // Corregir tabla cuenta_interna
        if (Schema::hasTable('cuenta_interna')) {
            Schema::table('cuenta_interna', function (Blueprint $table) {
                $table->decimal('ingreso', 12, 2)->change();
                $table->decimal('egreso', 12, 2)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inversiones', function (Blueprint $table) {
            $table->float('monto')->change();
            $table->float('interes')->change();
        });

        Schema::table('pago_inversions', function (Blueprint $table) {
            $table->float('monto')->change();
            $table->float('fecha_pago')->change();
            $table->dropColumn('existente');
        });

        if (Schema::hasTable('fiducias')) {
            Schema::table('fiducias', function (Blueprint $table) {
                $table->float('monto')->change();
                $table->float('interes')->change();
                $table->float('interes_mora')->change();
            });
        }

        if (Schema::hasTable('cuenta_bancarias')) {
            Schema::table('cuenta_bancarias', function (Blueprint $table) {
                $table->float('cuota')->nullable()->change();
            });
        }

        // Corregir tabla clients - Se maneja en migración separada para PostgreSQL
        // if (Schema::hasTable('clients')) {
        //     Schema::table('clients', function (Blueprint $table) {
        //         $table->float('limite_credito')->nullable()->change();
        //         $table->float('credito_disponible')->nullable()->change();
        //         $table->float('ingresos_mensuales')->change();
        //         $table->float('capacidad_pago')->nullable()->change();
        //     });
        // }

        if (Schema::hasTable('prestamo_hipotecarios')) {
            Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
                $table->float('monto')->change();
                $table->float('interes')->change();
                $table->float('cuota')->nullable()->change();
            });
        }

        if (Schema::hasTable('cuenta_interna')) {
            Schema::table('cuenta_interna', function (Blueprint $table) {
                $table->float('ingreso')->change();
                $table->float('egreso')->change();
            });
        }
    }
};
