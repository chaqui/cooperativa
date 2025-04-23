<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Declaracion_Impuesto extends Model
{
    protected $table = 'declaracion_impuestos';
    protected $fillable = [
        'id_tipo_impuesto',
        'fecha_inicio',
        'fecha_fin',
        'numero_formulario',
        'fecha_presentacion',
        'presentado'
    ];

    public static function generateDeclaracion($data)
    {
        $declaracion = new Declaracion_Impuesto();
        $declaracion->id_tipo_impuesto = $data['id_tipo_impuesto'];
        $declaracion->fecha_inicio = $data['fecha_inicio'];
        $declaracion->fecha_fin = $data['fecha_fin'];
        return $declaracion;
    }
    public function tipoImpuesto()
    {
        return $this->belongsTo(TipoImpuesto::class, 'id_tipo_impuesto');
    }

    public function impuestoTransacciones()
    {
        return $this->hasMany(ImpuestoTransaccion::class, 'id_declaracion_impuesto');
    }
    public function getTotalImpuestoAttribute()
    {
        return $this->impuestoTransacciones->sum('monto_impuesto');
    }
    public function getTotalImpuestoExcedenteAttribute()
    {
        return $this->impuestoTransacciones->sum('monto_impuesto') - $this->tipoImpuesto->cantidad_excedente;
    }

    public function declarar($data)
    {
        $this->numero_formulario = $data['numero_formulario'];
        $this->fecha_presentacion = $data['fecha_presentacion'];
        $this->presentado = true;
    }
}
