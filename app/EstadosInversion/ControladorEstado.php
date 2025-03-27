<?php

namespace App\EstadosInversion;

use App\Models\Inversion;
use App\Constants\EstadoInversion;
use App\Services\CuentaInternaService;
use App\Services\CuotaInversionService;
use App\Services\DepositoService;
use App\Traits\Loggable;

class ControladorEstado
{
    use Loggable;

    private CuotaInversionService $cuotaInversionService;

    private DepositoService $depositoService;

    public function __construct(
        CuotaInversionService $cuotaInversionService,
        DepositoService $depositoService
    ) {
        $this->cuotaInversionService = $cuotaInversionService;
        $this->depositoService = $depositoService;
    }
    /**
     * Cambia el estado de una inversión según el estado proporcionado en los datos
     *
     * @param Inversion $inversion La inversión a la que se cambiará el estado
     * @param array $data Datos para el cambio de estado, debe incluir:
     *        - estado: (requerido) Código del nuevo estado (usar constantes de EstadoInversion)
     *        - razon: (opcional) Motivo del cambio
     *        - descripcion: (opcional) Descripción detallada
     * @throws \Exception Si ocurre un error durante el cambio de estado
     */
    public function cambiarEstado(Inversion $inversion, $data)
    {
        $this->log("Iniciando cambio de estado para inversión #{$inversion->id}, código {$inversion->codigo}");
        $estadoAnterior = $inversion->id_estado;
        $nuevoEstado = $data['estado'];

        $this->log("Cambio de estado: {$estadoAnterior} -> {$nuevoEstado}");

        // Obtener la instancia de estado correspondiente
        $estadoHandler = $this->getEstado($nuevoEstado);

        // Ejecutar el cambio de estado
        $estadoHandler->cambiarEstado($inversion, $data);

        $this->log("Cambio de estado completado exitosamente para inversión #{$inversion->id}");
    }

    /**
     * Obtiene la instancia de estado correspondiente según el código de estado proporcionado
     *
     * @param int $estado Código del estado (usar constantes de EstadoInversion)
     * @return EstadoBaseInversion Instancia del estado correspondiente
     * @throws \InvalidArgumentException Si el código de estado no es válido
     * @throws \Exception Si ocurre otro error durante la creación del estado
     */
    private function getEstado($estado)
    {
        $this->log("Obteniendo instancia para estado: $estado");

        try {
            // Mapa de estados a sus clases correspondientes y dependencias
            $estadosMap = [
                EstadoInversion::$CREADO => [
                    'clase' => InversionCreada::class,
                    'dependencias' => [$this->depositoService]
                ],
                EstadoInversion::$DEPOSITADO => [
                    'clase' => InversionDepositada::class,
                    'dependencias' => []
                ],
                EstadoInversion::$APROBADO => [
                    'clase' => InversionAutorizada::class,
                    'dependencias' => [$this->cuotaInversionService]
                ],
                // Agregar nuevos estados aquí sin modificar el método
            ];

            // Verificar si el estado existe en el mapa
            if (!isset($estadosMap[$estado])) {
                $estadosDisponibles = implode(', ', array_keys($estadosMap));
                throw new \InvalidArgumentException(
                    "Estado inválido: $estado. Estados disponibles: $estadosDisponibles"
                );
            }

            $estadoInfo = $estadosMap[$estado];
            $clase = $estadoInfo['clase'];
            $dependencias = $estadoInfo['dependencias'];

            // Crear instancia usando Reflection para manejar un número variable de dependencias
            if (empty($dependencias)) {
                return new $clase();
            } else {
                // ReflectionClass permite crear instancias con un array de parámetros
                $reflection = new \ReflectionClass($clase);
                return $reflection->newInstanceArgs($dependencias);
            }
        } catch (\InvalidArgumentException $e) {
            $this->logError("Error de argumento inválido: " . $e->getMessage());
            throw $e;
        } catch (\ReflectionException $e) {
            $this->logError("Error de reflexión al crear estado: " . $e->getMessage());
            throw new \Exception("Error al crear instancia de estado: " . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            $this->logError("Error general al obtener estado: " . $e->getMessage());
            throw $e;
        }
    }
}
