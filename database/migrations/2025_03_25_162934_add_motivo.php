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

        Schema::table('depositos', function (Blueprint $table) {
            $table->string('motivo')->nullable()->after('numero_cuenta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('depositos', function (Blueprint $table) {
            $table->dropColumn('motivo');
        });
    }
};
