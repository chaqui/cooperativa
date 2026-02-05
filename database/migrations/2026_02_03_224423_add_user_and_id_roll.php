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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('rollback_id')->constrained('rollback_prestamos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_rollbacks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['rollback_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('rollback_id');
        });
    }
};
