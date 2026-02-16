<?php

namespace App\Services;

use App\Traits\Loggable;
use App\Traits\ErrorHandler;
use App\Constants\RollBackCampos;
use App\Models\Pago;
use App\Models\Deposito;
use App\Models\Cuenta_Interna;
use App\Models\historico_saldo;
use App\Models\Rollback_prestamo;
use App\Models\HistoricoRollback;
use App\Models\ImpuestoTransaccion;
use App\Models\Prestamo_Hipotecario;
use App\Models\DepositoHistoricoSaldo;
use Illuminate\Support\Facades\DB;


class RollBackservice
{
    use Loggable;
    use ErrorHandler;

    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function solicitarRollback($prestamoId, $datos)
    {
        $this->log("Iniciando el proceso de rollback para el préstamo hipotecario ID: $prestamoId");

        $rollback = Rollback_prestamo::where('prestamo_hipotecario_id', $prestamoId)->first();
        if (!$rollback) {
            $this->logError("No se encontró un registro de rollback para el préstamo hipotecario ID: $prestamoId");
            return false;
        }

        HistoricoRollback::create(attributes: [
            'prestamo_hipotecario_id' => $prestamoId,
            'razon' => $datos['razon'] ?? 'No especificada',
            'rollback_id' => $rollback->id,
        ]);
        return true;
    }

    public function autorizarRollback($rollBackHistoricoId)
    {
        DB::beginTransaction();
        try {
            $this->log("Autorizando el rollback ID: $rollBackHistoricoId");
            $rollBackHistorico = $this->getRollBackHistorico($rollBackHistoricoId);
            $rollBack = $rollBackHistorico->rollback;

            if (!$rollBack) {
                throw new \Exception("No se encontró el registro de rollback asociado");
            }

            // Validación preventiva: detectar si hay 'id' en datos_a_modificar
            $this->validarDatosAModificar($rollBack);

            $datosAnteriores = [];
            $datosNuevos = [];

            // Procesar eliminación y acumular datos anteriores
            $datosAnteriores = $this->procesarDatosAEliminar($rollBack, $datosAnteriores);

            // Procesar modificación y acumular datos anteriores/nuevos
            [$datosAnteriores, $datosNuevos] = $this->procesarDatosAModificar($rollBack, $datosAnteriores, $datosNuevos);

            // Guardar los datos de auditoría en el histórico
            $rollBackHistorico->datos_anteriores = json_encode($datosAnteriores);
            $rollBackHistorico->datos_nuevos = json_encode($datosNuevos);
            $rollBackHistorico->fecha_autorizacion = now();

            $usuario = $this->userService->getUserOfToken();
            if ($usuario) {
                $rollBackHistorico->user_id = $usuario->id;
            }

            $rollBackHistorico->save();

            $this->log("Rollback ID: $rollBackHistoricoId autorizado exitosamente");
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError("Error al autorizar el rollback ID: $rollBackHistoricoId - " . $e->getMessage());
            throw $e;
        }
    }

    private function procesarDatosAEliminar($rollBack, $datosAnteriores = []): array
    {
        $prestamoId = $rollBack->prestamo_hipotecario_id;
        $datosAEliminar = json_decode($rollBack->datos_a_eliminar, true) ?? [];

        foreach ($datosAEliminar as $nombreCampo => $datos) {
            // Asegurar que $datos sea un array
            $idsAEliminar = is_array($datos) ? $datos : (is_scalar($datos) ? [$datos] : []);

            if (empty($idsAEliminar)) {
                continue;
            }

            try {
                if ($nombreCampo == RollBackCampos::$impuestosTransacciones) {
                    $datosAnteriores[$nombreCampo] = ImpuestoTransaccion::whereIn('id', $idsAEliminar)->get()->toArray();
                    ImpuestoTransaccion::whereIn('id', $idsAEliminar)->delete();
                    $this->log("Impuestos de transacciones eliminados: " . count($idsAEliminar) . " registros para el préstamo hipotecario ID: $prestamoId");
                }

                if ($nombreCampo == RollBackCampos::$cuentasInternas) {
                    $datosAnteriores[$nombreCampo] = Cuenta_Interna::whereIn('id', $idsAEliminar)->get()->toArray();
                    Cuenta_Interna::whereIn('id', $idsAEliminar)->delete();
                    $this->log("Cuentas internas eliminadas: " . count($idsAEliminar) . " registros para el préstamo hipotecario ID: $prestamoId");
                }

                if ($nombreCampo == RollBackCampos::$depositoHistoricoSaldo) {
                    $datosAnteriores[$nombreCampo] = DepositoHistoricoSaldo::whereIn('id', $idsAEliminar)->get()->toArray();
                    DepositoHistoricoSaldo::whereIn('id', $idsAEliminar)->delete();
                    $this->log("Depósitos históricos de saldo eliminados: " . count($idsAEliminar) . " registros para el préstamo hipotecario ID: $prestamoId");
                }

                if ($nombreCampo == RollBackCampos::$depositos) {
                    $datosAnteriores[$nombreCampo] = Deposito::whereIn('id', $idsAEliminar)->get()->toArray();
                    Deposito::whereIn('id', $idsAEliminar)->delete();
                    $this->log("Depósitos eliminados: " . count($idsAEliminar) . " registros para el préstamo hipotecario ID: $prestamoId");
                }

                if ($nombreCampo == RollBackCampos::$interesPagado) {
                    $datosAnteriores[$nombreCampo] = historico_saldo::whereIn('id', $idsAEliminar)->get()->toArray();
                    historico_saldo::whereIn('id', $idsAEliminar)->delete();
                    $this->log("Intereses pagados eliminados: " . count($idsAEliminar) . " registros para el préstamo hipotecario ID: $prestamoId");
                }
            } catch (\Exception $e) {
                $this->logError("Error eliminando datos de $nombreCampo: " . $e->getMessage());
                throw $e;
            }
        }

        return $datosAnteriores;
    }

    private function procesarDatosAModificar($rollBack, $datosAnteriores = [], $datosNuevos = []): array
    {
        $prestamoId = $rollBack->prestamo_hipotecario_id;
        $datosAModificar = $rollBack->datos_a_modificar ? json_decode($rollBack->datos_a_modificar, true) : [];

        foreach ($datosAModificar as $nombreCampo => $datos) {
            try {
                if ($nombreCampo == RollBackCampos::$fecha_fin_prestamo) {
                    $prestamo = Prestamo_Hipotecario::find($prestamoId);
                    if ($prestamo) {
                        $datosAnteriores[$nombreCampo] = $prestamo->fecha_fin;
                        $prestamo->fecha_fin = $datos['fecha_fin'] ?? null;
                        $prestamo->save();
                        $datosNuevos[$nombreCampo] = $datos['fecha_fin'] ?? null;
                        $this->log("Fecha fin del préstamo hipotecario ID: $prestamoId restaurada de {$datosAnteriores[$nombreCampo]} a {$datosNuevos[$nombreCampo]}");
                    }
                }

                if ($nombreCampo == RollBackCampos::$cuotas) {
                    $datosAnteriores[$nombreCampo] = [];
                    $datosNuevos[$nombreCampo] = [];
                    foreach ($datos as $cuotaId => $campos) {
                        // Validar y convertir ID a integer
                        $cuotaId = (int) $cuotaId;
                        if ($cuotaId <= 0) {
                            $this->log("Advertencia: ID de cuota inválido: $cuotaId. Se omite.");
                            continue;
                        }
                        $cuota = Pago::find($cuotaId);
                        if ($cuota) {
                            $datosAnteriores[$nombreCampo][$cuotaId] = $cuota->toArray();
                            // Filtrar 'id' para evitar violación de PRIMARY KEY
                            $camposAActualizar = array_filter($campos, function ($key) {
                                return $key !== 'id';
                            }, ARRAY_FILTER_USE_KEY);
                            foreach ($camposAActualizar as $campo => $valor) {
                                $cuota->$campo = $valor;
                            }
                            $cuota->save();
                            // Guardar estado post-guardado para auditoría
                            $datosNuevos[$nombreCampo][$cuotaId] = $cuota->toArray();
                            $this->log("Pago hipotecario ID: $cuotaId restaurado.");
                        } else {
                            $cuota = Pago::create($campos);
                            $this->log("Pago hipotecario ID: $cuotaId recreado.");
                            $datosNuevos[$nombreCampo][$cuotaId] = $cuota->toArray();
                        }
                    }
                }

                if ($nombreCampo === RollBackCampos::$interesPagado) {
                    $datosAnteriores[$nombreCampo] = [];
                    $datosNuevos[$nombreCampo] = [];
                    foreach ($datos as $historicoId => $campos) {
                        // Validar y convertir ID a integer
                        $historicoId = (int) $historicoId;
                        if ($historicoId <= 0) {
                            $this->log("Advertencia: ID de histórico inválido: $historicoId. Se omite.");
                            continue;
                        }
                        $historico = historico_saldo::find($historicoId);
                        if ($historico) {
                            $datosAnteriores[$nombreCampo][$historicoId] = $historico->toArray();
                            // Filtrar 'id' para evitar violación de PRIMARY KEY
                            $camposAActualizar = array_filter($campos, function ($key) {
                                return $key !== 'id';
                            }, ARRAY_FILTER_USE_KEY);
                            foreach ($camposAActualizar as $campo => $valor) {
                                $historico->$campo = $valor;
                            }
                            $historico->save();
                            // Guardar estado post-guardado para auditoría
                            $datosNuevos[$nombreCampo][$historicoId] = $historico->toArray();
                            $this->log("Histórico de saldo ID: $historicoId restaurado.");
                        } else {
                            $historico = historico_saldo::create($campos);
                            $this->log("Histórico de saldo ID: $historicoId recreado.");
                            $datosNuevos[$nombreCampo][$historicoId] = $historico->toArray();
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logError("Error modificando datos de $nombreCampo: " . $e->getMessage());
                throw $e;
            }
        }

        return [$datosAnteriores, $datosNuevos];
    }

    /**
     * Validar que datos_a_modificar tenga estructura correcta:
     * - Las claves del array anidado deben ser IDs numéricos
     * - No deben contener 'id' como valor (es la clave de búsqueda)
     */
    private function validarDatosAModificar($rollBack): void
    {
        $datosAModificar = $rollBack->datos_a_modificar ? json_decode($rollBack->datos_a_modificar, true) : [];

        foreach ($datosAModificar as $nombreCampo => $datos) {
            if (is_array($datos)) {
                foreach ($datos as $id => $campos) {
                    // Validar que la clave sea numérica
                    if (!is_numeric($id) || (int)$id <= 0) {
                        $this->logError("Advertencia: Campo '$nombreCampo' contiene ID inválido: '$id'. Esperaba ID numérico positivo.");
                    }
                    // Validar que no haya 'id' dentro de los campos a modificar
                    if (is_array($campos) && isset($campos['id'])) {
                        $this->logError("Advertencia: Campo '$nombreCampo' contiene 'id' en los datos a modificar. El 'id' será ignorado para evitar violación de PRIMARY KEY.");
                    }
                }
            }
        }
    }

    private function getRollBackHistorico($rollBackHistoricoId)
    {
        $rollBackHistorico = HistoricoRollback::find($rollBackHistoricoId);
        if (!$rollBackHistorico) {
            $this->logError("No se encontró un registro histórico de rollback con ID: $rollBackHistoricoId");
            throw new \Exception("Registro histórico de rollback no encontrado.");
        }
        return $rollBackHistorico;
    }

    public function getRollBacksPendientes()
    {
        return HistoricoRollback::whereNull('fecha_autorizacion')->get();
    }

    public function getAllRollBacks()
    {
        return HistoricoRollback::all();
    }
}
