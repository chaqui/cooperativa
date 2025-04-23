<?php

namespace App\Models;

use App\Constants\PlazoImpuesto;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Model;

class TipoImpuesto extends Model
{
    use Loggable;
    protected $table = 'tipo_impuestos';
    protected $fillable = [
        'nombre',
        'descripcion',
        'porcentaje',
        'cantidad_excedente',
        'porcentaje_excedente',
    ];

    public function declaracionImpuestos()
    {
        return $this->hasMany(Declaracion_Impuesto::class, 'id_tipo_impuesto');
    }

    public function declaracionImpuestoFecha($fecha)
    {
        $this->log("Buscando declaración de impuesto por fecha: " . ($fecha instanceof \DateTime ? $fecha->format('Y-m-d') : $fecha));
        return $this->declaracionImpuestos()->where('fecha_inicio', '<=', $fecha)->where('fecha_fin', '>=', $fecha)->first();
    }

    public function declaracionImpuestoMasCercana($fecha)
    {
        return $this->declaracionImpuestos()
            ->where('fecha_fin', '<=', $fecha)
            ->orderBy('fecha_fin', 'asc')
            ->first();
    }

    public function mesesPlazo()
    {
        return PlazoImpuesto::mesesPlazo($this->plazo);
    }

    public function calcularMontoImpuesto($monto)
    {
        $montoImpuesto = $monto * ($this->porcentaje / 100);
        if ($this->cantidad_excedente && $monto > $this->cantidad_excedente) {
            $montoImpuesto += ($monto - $this->cantidad_excedente) * ($this->porcentaje_excedente / 100);
        }
        return $montoImpuesto;
    }
}
