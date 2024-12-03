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
            $table->dropForeign(['estado']);
            $table->dropColumn('estado');
            $table->unsignedBigInteger('estado_id')->unsigned();
            $table->foreign('estado_id')->references('id')->on('estado_prestamos');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamo_hipotecarios', function (Blueprint $table) {
            //
        });
    }
};
