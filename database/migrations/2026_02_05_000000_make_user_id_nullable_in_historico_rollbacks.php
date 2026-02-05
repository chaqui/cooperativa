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
        Schema::table('historico_rollbacks', function (Blueprint $table) {
            // Modificar user_id para que acepte nulls
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_rollbacks', function (Blueprint $table) {
            // Revertir a no-nullable
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
