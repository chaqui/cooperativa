<?php

namespace App\EstadosPrestamo;

use App\Constants\EstadoPrestamo;
use App\Models\Prestamo_Hipotecario;
use App\Services\ArchivoService;
use App\Services\CuotaHipotecaService;
use App\Services\RetiroService;
use App\Traits\Loggable;
use App\Services\BitacoraInteresService;
use App\Services\DepositoService;

class ControladorEstado
{
    use Loggable;

    private $cuotaHipotecariaService;


    private ArchivoService $archivoService;


    private RetiroService $retiroService;

    private BitacoraInteresService $bitacoraInteresService;

    public function __construct(
        CuotaHipotecaService $cuotaHipotecariaService,
        ArchivoService $archivoService,
        RetiroService $retiroService,
        BitacoraInteresService $bitacoraInteresService
    ) {
        $this->cuotaHipotecariaService = $cuotaHipotecariaService;
        $this->archivoService = $archivoService;
        $this->retiroService = $retiroService;
        $this->bitacoraInteresService = $bitacoraInteresService;
    }

    public  function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {
        // Validar que exista el campo estado
        if (!isset($data['estado'])) {
            throw new \InvalidArgumentException(
                "El código de estado es requerido para cambiar el estado del préstamo"
            );
        }

        // Verificar que el estado sea un número
        if (!is_numeric($data['estado'])) {
            throw new \InvalidArgumentException(
                "El código de estado debe ser un valor numérico"
            );
        }

        $this->log("Intentando cambiar estado de préstamo #{$prestamo->codigo} al estado {$data['estado']}");

        // Obtener el manejador de estado adecuado
        $estado = self::getEstado($data['estado']);
        // Ejecutar el cambio de estado
        $estado->cambiarEstado($prestamo, $data);

        $this->log("Estado de préstamo #{$prestamo->codigo} cambiado exitosamente a {$data['estado']}");
    }

    /**
     * Obtiene la instancia de estado correspondiente según el código de estado proporcionado
     *
     * @param int $estado Código del estado (usar constantes de EstadoPrestamo)
     * @return EstadoBasePrestamo Instancia del estado correspondiente
     * @throws \InvalidArgumentException Si el código de estado no es válido o no está soportado
     */
    private function getEstado(int $estado): EstadoBasePrestamo
    {
        // Definir mapeo de estados a clases de implementación y sus dependencias
        $estadosMap = [
            EstadoPrestamo::$CREADO => [
                'clase' => PrestamoCreado::class,
                'dependencias' => []
            ],
            EstadoPrestamo::$APROBADO => [
                'clase' => PrestamoAprobado::class,
                'dependencias' => [$this->retiroService]
            ],
            EstadoPrestamo::$DESEMBOLZADO => [
                'clase' => PrestamoDesembolsado::class,
                'dependencias' => [$this->cuotaHipotecariaService, $this->archivoService, $this->bitacoraInteresService]
            ],
            EstadoPrestamo::$FINALIZADO => [
                'clase' => PrestamoFinalizado::class,
                'dependencias' => []
            ],
            EstadoPrestamo::$CANCELADO => [
                'clase' => PrestamoCancelado::class,
                'dependencias' => [app(DepositoService::class), $this->bitacoraInteresService]
            ],
            EstadoPrestamo::$RECHAZADO => [
                'clase' => PrestamoRechazado::class,
                'dependencias' => []
            ],
            // Agregar nuevos estados aquí siguiendo el mismo patrón
        ];

        // Verificar si el estado existe en el mapeo
        if (!isset($estadosMap[$estado])) {
            $estadosDisponibles = implode(', ', array_keys($estadosMap));
            throw new \InvalidArgumentException(
                "Estado no válido o no soportado: {$estado}. Estados disponibles: {$estadosDisponibles}"
            );
        }

        // Obtener la información del estado
        $estadoInfo = $estadosMap[$estado];
        $clase = $estadoInfo['clase'];
        $dependencias = $estadoInfo['dependencias'];

        // Instanciar la clase de estado con sus dependencias
        if (empty($dependencias)) {
            return new $clase();
        } else {
            return new $clase(...$dependencias);
        }
    }
}
