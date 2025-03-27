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
        Schema::table('cuenta_interna', function (Blueprint $table) {
            $table->decimal('ganancia', 10, 2)->default(0)->after('egreso');
            $table->decimal('capital', 10, 2)->default(0)->after('ganancia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuenta_interna', function (Blueprint $table) {
            $table->dropColumn('ganancia');
            $table->dropColumn('capital');
        });
    }
};
