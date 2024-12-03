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
        Schema::table('inversiones', function (Blueprint $table) {
            $table->dropForeign(['cuenta_recaudadora']);
            $table->foreign('cuenta_recaudadora')->references('numero_cuenta')->on('cuenta__bancarias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inversiones', function (Blueprint $table) {
            $table->dropForeign(['cuenta_recaudadora']);
            $table->foreign('cuenta_recaudadora')->references('id')->on('cuenta__bancarias');
        });
    }
};
