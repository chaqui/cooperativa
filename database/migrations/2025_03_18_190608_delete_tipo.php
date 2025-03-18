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
            $table->dropForeign(['tipo_inversion']);
            $table->dropColumn('tipo_inversion');
        });
        Schema::dropIfExists('tipo_inversion');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('tipo_inversion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
        });
        Schema::table('inversiones', function (Blueprint $table) {
            $table->string('tipo_inversion');
            $table->foreign('tipo_inversion')->references('id')->on('tipo_inversion');
        });

    }
};
