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
        Schema::table('cuenta__bancarias', function (Blueprint $table) {
            $table->string('dpi_cliente');
            $table->foreign('dpi_cliente')->references('dpi')->on('clients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuenta__bancarias', function (Blueprint $table) {
            $table->dropForeign(['dpi_cliente']);
            $table->dropColumn('dpi_cliente');
        });
    }
};
