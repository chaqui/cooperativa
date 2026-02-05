<?php

namespace App\Traits;

use App\Constants\RollBackCampos;
use App\Http\Resources\Rol;
use App\Models\Rollback_prestamo;

trait RegistrarRollback
{



    use Loggable;
    /**
     * Inicia un nuevo registro de rollback para un préstamo hipotecario.
     *
     * @param int $prestamoId El ID del préstamo hipotecario.
     * @return int El ID del registro de rollback creado.
     */
    public function iniciarRollback($prestamoId)
    {
        $this->log("Iniciando el almacenamiento del rollback para el préstamo hipotecario ID: $prestamoId");
        // Eliminar cualquier rollback existente que no tenga históricos
        $rollbackSinHistoricos = Rollback_prestamo::where('prestamo_hipotecario_id', $prestamoId)
            ->doesntHave('historicos')
            ->first();
        if ($rollbackSinHistoricos) {
            $rollbackSinHistoricos->delete();
        }
        $rollback = Rollback_prestamo::create([
            'prestamo_hipotecario_id' => $prestamoId,
            'datos_a_eliminar' => json_encode([]),
            'datos_a_modificar' => json_encode([])
        ]);
        return $rollback->id;
    }

    /**
     * Agrega datos a eliminar al registro de rollback de un préstamo hipotecario.
     *
     * @param int $prestamoId El ID del préstamo hipotecario.
     * @param mixed $datos Los datos a eliminar.
     * @param string $nombreCampo El nombre del campo asociado a los datos.
     * @return void
     */
    public function agregarDatosEliminar($prestamoId, $datos, $nombreCampo)
    {
        if(!RollBackCampos::esCampoValido($nombreCampo)){
            $this->error("El campo '$nombreCampo' no es válido para rollback.");
            return;
        }
        $this->log("Agregando datos a eliminar para el préstamo hipotecario ID: $prestamoId, campo: $nombreCampo");
        // Buscar rollback activo que no tenga históricos
        $rollback = Rollback_prestamo::where('prestamo_hipotecario_id', $prestamoId)
            ->doesntHave('historicos')
            ->first();
        if (!$rollback) {
            $this->iniciarRollback($prestamoId);
            $rollback = Rollback_prestamo::where('prestamo_hipotecario_id', $prestamoId)
                ->doesntHave('historicos')
                ->first();
        }
        $datosExistentes = json_decode($rollback->datos_a_eliminar, true) ?? [];
        $datosExistentes[$nombreCampo] = $datos;
        $rollback->datos_a_eliminar = json_encode($datosExistentes);
        $rollback->save();
        $this->log("Datos a eliminar actualizados para el préstamo hipotecario ID: $prestamoId");
    }

    /**
     * Agrega datos a modificar al registro de rollback de un préstamo hipotecario.
     *
     * @param int $prestamoId El ID del préstamo hipotecario.
     * @param mixed $datos Los datos a modificar.
     * @param string $nombreCampo El nombre del campo asociado a los datos.
     * @return void
     */
    public function agregarDatosModificar($prestamoId, $datos, $nombreCampo)
    {
        if(!RollBackCampos::esCampoValido($nombreCampo)){
            $this->error("El campo '$nombreCampo' no es válido para rollback.");
            return;
        }
        $this->log("Agregando datos a modificar para el préstamo hipotecario ID: $prestamoId, campo: $nombreCampo");
        // Buscar rollback activo que no tenga históricos
        $rollback = Rollback_prestamo::where('prestamo_hipotecario_id', $prestamoId)
            ->doesntHave('historicos')
            ->first();
        if (!$rollback) {
            $this->iniciarRollback($prestamoId);
            $rollback = Rollback_prestamo::where('prestamo_hipotecario_id', $prestamoId)
                ->doesntHave('historicos')
                ->first();
        }
        $datosExistentes = json_decode($rollback->datos_a_modificar, true) ?? [];
        $datosExistentes[$nombreCampo] = $datos;
        $rollback->datos_a_modificar = json_encode($datosExistentes);
        $rollback->save();
        $this->log("Datos a modificar actualizados para el préstamo hipotecario ID: $prestamoId");
    }
}
