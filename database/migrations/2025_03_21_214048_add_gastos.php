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
        Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
            $table->decimal('gastos_administrativos', 10, 2)->default(0)->after('gastos_hipotecarios');
            $table->decimal('gastos_formalidad', 10, 2)->default(0)->after('gastos_administrativos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
            $table->dropColumn('gastos_administrativos');
            $table->dropColumn('gastos_formalidad');
        });
    }
};
