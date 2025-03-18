<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstado extends Model
{
    protected $table = 'historial';
    protected $fillable = [
        'id_prestamo',
        'id_estado',
        'id_inversion',
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
        return $this->belongsTo(Estado::class, 'id_estado');
    }

    /**
     * Genera un registro histórico para préstamo o inversión
     *
     * @param int $estado ID del estado
     * @param array $data Datos adicionales del histórico
     * @param int|null $idPrestamo ID del préstamo (null si es inversión)
     * @param int|null $idInversion ID de la inversión (null si es préstamo)
     * @return HistorialEstado
     */
    public static function generarHistorico($estado, array $data, $idPrestamo = null, $idInversion = null)
    {
        $historial = new HistorialEstado();

        // Asignar ID de préstamo o inversión según corresponda
        if ($idPrestamo !== null) {
            $historial->id_prestamo = $idPrestamo;
        } elseif ($idInversion !== null) {
            $historial->id_inversion = $idInversion;
        }

        // Asignar estado y campos comunes
        $historial->id_estado = $estado;
        $historial->razon = $data['razon'] ?? null;
        $historial->anotacion = $data['anotacion'] ?? null;
        $historial->no_documento_desembolso = $data['no_documento_desembolso'] ?? null;
        $historial->tipo_documento_desembolso = $data['tipo_documento_desembolso'] ?? null;
        $historial->fecha = $data['fecha'] ?? now();

        return $historial;
    }

    /**
     * Genera un histórico específico para préstamos (método helper)
     *
     * @param int $idPrestamo
     * @param int $estado
     * @param array $data
     * @return HistorialEstado
     */
    public static function generarHistoricoPrestamo($idPrestamo, $estado, array $data)
    {
        return self::generarHistorico($estado, $data, $idPrestamo, null);
    }

    /**
     * Genera un histórico específico para inversiones (método helper)
     *
     * @param int $idInversion
     * @param int $estado
     * @param array $data
     * @return HistorialEstado
     */
    public static function generarHistoricoInversion($idInversion, $estado, array $data)
    {
        return self::generarHistorico($estado, $data, null, $idInversion);
    }
}
