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
            $table->dropColumn('monto');
            $table->float('interes', 8, 2)->after('fecha');
            $table->float('capital', 8, 2)->after('interes');
            $table->float('saldo', 8, 2)->after('capital');
            $table->float('monto_pagado', 8, 2)->after('saldo')->nullable();
            $table->float('penalizacion', 8, 2)->after('monto_pagado')->default(0);
            $table->float('capital_pagado', 8, 2)->after('penalizacion')->nullable();
            $table->integer('id_pago_anterior')->after('capital_pagado')->unsigned()->nullable();
            $table->foreign('id_pago_anterior')->references('id')->on('pagos');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            //
        });
    }
};
