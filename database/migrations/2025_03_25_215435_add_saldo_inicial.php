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
        Schema::table('tipo_cuenta_interna', function (Blueprint $table) {
            $table->decimal('saldo_inicial', 15, 2)->default(0)->after('numero_cuenta');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_cuenta_interna', function (Blueprint $table) {
            $table->dropColumn('saldo_inicial');
        });
    }
};
