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
            $table->dropForeign(['estado']);
            $table->dropColumn('estado');
            $table->integer('id_estado')->unsigned();
            $table->foreign('id_estado')->references('id')->on('estado_inversiones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inversiones', function (Blueprint $table) {
            $table->dropForeign(['id_estado']);
            $table->dropColumn('id_estado');
            $table->string('estado');
            $table->foreign('estado')->references('id')->on('estado_inversiones');
        });
    }
};
