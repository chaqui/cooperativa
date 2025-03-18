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
            $table->decimal('montoInteres', 10, 2)->nullable();
            $table->decimal('montoISR', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pago_inversions', function (Blueprint $table) {
            $table->dropColumn('montoInteres');
            $table->dropColumn('montoISR');
        });
    }
};
