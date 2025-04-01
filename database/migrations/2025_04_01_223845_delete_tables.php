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

        Schema::dropIfExists('tipo_cuentas');
        Schema::dropIfExists('firmantes__contratos');
        Schema::dropIfExists('contratos');
        Schema::dropIfExists('fiducias');
        Schema::dropIfExists('tipo_fiducias');
        Schema::dropIfExists('tasa__interes');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
