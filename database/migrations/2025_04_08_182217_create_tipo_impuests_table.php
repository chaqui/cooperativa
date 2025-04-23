<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_impuestos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nombre');
            $table->string('descripcion');
            $table->integer('porcentaje')->default(0)->comment('Porcentaje del impuesto');
            $table->integer('dia_inicio')->default(1)->comment('Día de inicio del periodo');
            $table->integer('dia_fin')->default(31)->comment('Día de fin del periodo');
            $table->decimal('cantidad_excedente', 10, 2)->nullable()->comment('Cantidad a partir de la cual se aplica el porcentaje excedente');
            $table->integer('porcentaje_excedente')->default(0)->comment('Porcentaje al revalsar el monto de la cantidad excedente');

            $table->string('plazo')->default('Mensual')->comment('Mensual, Trimestral, Anual');
        });

        DB::table('tipo_impuestos')->insert([
            'nombre' => 'IVA',
            'descripcion' => 'Impuesto al Valor Agregado',
            'porcentaje' => 12,
            'dia_inicio' => 1,
            'dia_fin' => 31,
            'plazo' => 'Mensual'
        ]);

        DB::table('tipo_impuestos')->insert([
            'nombre' => 'ISR',
            'descripcion' => 'Impuesto Sobre la Renta',
            'porcentaje' => 5,
            'dia_inicio' => 1,
            'cantidad_excedente' => 30000,
            'porcentaje_excedente' => 7,
            'dia_fin' => 31,
            'plazo' => 'Trimestral'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tipo_impuestos')->where('nombre', 'IVA')->delete();
        DB::table('tipo_impuestos')->where('nombre', 'ISR')->delete();
        Schema::dropIfExists('tipo_impuestos');
    }
};
