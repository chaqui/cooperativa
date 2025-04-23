<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpuestoTransaccion extends Model
{
    protected $table = 'impuesto_transaccions';

    protected $fillable = [
        'id_declaracion_impuesto',
        'monto_impuesto',
        'fecha_transaccion',
        'descripcion'
    ];

    public function declaracionImpuesto()
    {
        return $this->belongsTo(declaracion_impuesto::class, 'id_declaracion_impuesto');
    }

    public static function generateTransaccion($data)
    {
        $transaccion = new ImpuestoTransaccion();
        $transaccion->id_declaracion_impuesto = $data['id_declaracion_impuesto'];
        $transaccion->monto_impuesto = $data['monto_impuesto'];
        $transaccion->fecha_transaccion = $data['fecha_transaccion'];
        $transaccion->descripcion = $data['descripcion'];
        return $transaccion;
    }
}
