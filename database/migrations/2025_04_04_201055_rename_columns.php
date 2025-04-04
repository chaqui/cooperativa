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
        Schema::table('pago_inversions', function (Blueprint $table) {
            $table->renameColumn('montoInteres', 'monto_interes')->comment('Monto de interes');
            $table->renameColumn('montoISR', 'monto_isr')->comment('Monto de ISR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pago_inversions', function (Blueprint $table) {
            $table->renameColumn('monto_interes', 'montoInteres')->comment('Monto de interes');
            $table->renameColumn('monto_isr', 'montoISR')->comment('Monto de ISR');
        });
    }
};
