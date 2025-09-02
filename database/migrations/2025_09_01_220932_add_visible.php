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
            $table->boolean(column: 'visible')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_cuenta_interna', function (Blueprint $table) {
            $table->dropColumn('visible');
        });
    }
};
