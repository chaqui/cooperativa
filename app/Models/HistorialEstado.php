<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstado extends Model
{
    protected $table = 'historial_prestamo_hipotecarios';
    protected $fillable = [
        'id_prestamo',
        'id_estado',
        'fecha',
        'razon',
        'anotacion',
        'no_documento_desembolso',
        'tipo_documento_desembolso',
    ];

    public function prestamo()
    {
        return $this->belongsTo(Prestamo_Hipotecario::class, 'id_prestamo');
    }

    public function estado()
    {
        return $this->belongsTo(Estado_Prestamo::class, 'id_estado');
    }

    public static function generarHistorico($idPrestamo, $estado, $data)
    {
        $historial = new HistorialEstado();
        $historial->id_prestamo = $idPrestamo;
        $historial->id_estado = $estado;
        $historial->razon = $data['razon'] ?? null;
        $historial->anotacion = $data['anotacion'] ?? null;
        $historial->no_documento_desembolso = $data['no_documento_desembolso'] ?? null;
        $historial->tipo_documento_desembolso = $data['tipo_documento_desembolso'] ?? null;
        $historial->fecha = $data['fecha'] ?? now();
        return $historial;
    }
}
