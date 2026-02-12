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
        Schema::table('pagos', function (Blueprint $table) {
            // Cambiar float a decimal con precisión correcta
            $table->decimal('interes', 12, 2)->change();
            $table->decimal('capital', 12, 2)->change();
            $table->decimal('saldo', 12, 2)->change();
            $table->decimal('monto_pagado', 12, 2)->nullable()->change();
            $table->decimal('penalizacion', 12, 2)->change();
            $table->decimal('capital_pagado', 12, 2)->nullable()->change();
            $table->decimal('recargo', 12, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Revertir a float si es necesario
            $table->float('interes')->change();
            $table->float('capital')->change();
            $table->float('saldo')->change();
            $table->float('monto_pagado')->nullable()->change();
            $table->float('penalizacion')->change();
            $table->float('capital_pagado')->nullable()->change();
            $table->float('recargo')->nullable()->change();
        });
    }
};
