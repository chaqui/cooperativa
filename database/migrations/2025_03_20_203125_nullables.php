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
        $table->string('tipo_documento')->nullable()->change();
        $table->string('numero_documento')->nullable()->change();
        $table->string('imagen')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depositos', function (Blueprint $table) {
        $table->string('tipo_documento')->nullable(false)->change();
        $table->string('numero_documento')->nullable(false)->change();
        $table->string('imagen')->nullable(false)->change();

        });
    }
};
