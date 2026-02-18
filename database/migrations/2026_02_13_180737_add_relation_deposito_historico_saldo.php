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
        Schema::create('depositos_historico_saldo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_deposito')->nullable()->after('id');
            $table->foreign('id_deposito')->references('id')->on('depositos')->onDelete('set null');
            $table->unsignedInteger('id_historico_saldo')->nullable()->after('id_deposito');
            $table->foreign('id_historico_saldo')->references('id')->on('historial_saldo_interes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depositos_historico_saldo');
    }
};
