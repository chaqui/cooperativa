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
        Schema::create('cuenta_interna', function (Blueprint $table) {
            $table->id();
            $table->float('ingreso', 8, 2)->default(0);
            $table->float('egreso', 8, 2)->default(0);
            $table->string('descripcion');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_interna');
    }
};
