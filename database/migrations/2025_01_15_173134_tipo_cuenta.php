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

        Schema::table('tipo_cuentas', function (Blueprint $table) {
            $table->drop();
        });

        Schema::table('cuenta__bancarias', function (Blueprint $table) {
            $table->string('tipo_cuenta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion');
        });

        Schema::table('cuenta__bancarias', function (Blueprint $table) {
            $table->dropColumn('tipo_cuenta');
        });
    }
};
