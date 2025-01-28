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
            $table->dropForeign(['tipo_taza']);
            $table->dropColumn('tipo_taza');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inversiones', function (Blueprint $table) {
            $table->string('tipo_taza');
            $table->foreign('tipo_taza')->references('id')->on('tipo_tasa_interes');
        });

    }
};
