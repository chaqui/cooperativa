<?

namespace App\Services;

use App\Constants\PlazoImpuesto;
use App\Models\ImpuestoTransaccion;
use App\Services\TipoImpuestoService;
use App\Services\DeclaracionImpuestoService;


use App\Traits\Loggable;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\ErrorHandler;

class ImpuestoTransaccionService
{
    use Loggable;

     use ErrorHandler;
    private TipoImpuestoService $tipoImpuestoService;
    private DeclaracionImpuestoService $declaracionImpuestoService;

    private TipoCuentaInternaService $tipoCuentaInternaService;

    public function __construct(
        TipoImpuestoService $tipoImpuestoService,
        DeclaracionImpuestoService $declaracionImpuestoService,
        TipoCuentaInternaService $tipoCuentaInternaService
    ) {
        $this->tipoImpuestoService = $tipoImpuestoService;
        $this->declaracionImpuestoService = $declaracionImpuestoService;
        $this->tipoCuentaInternaService = $tipoCuentaInternaService;
    }

    public function crearTransaccion($data)
    {
        $this->validate($data);
        DB::beginTransaction();
        try {
            $this->log("Creando transacción de impuesto con datos: " . json_encode($data));
            // Obtener el tipo de impuesto
            $tipoImpuesto = $this->tipoImpuestoService->getTipoImpustoByNombre($data['tipo_impuesto']);
            if (!$tipoImpuesto) {
                $this->lanzarExcepcionConCodigo("El tipo de impuesto no existe");
            }

            $this->log("Tipo de impuesto encontrado: " . $tipoImpuesto->nombre);

            // Verificar si existe una declaración de impuesto para la fecha
            $declaracionImpuesto = $tipoImpuesto->declaracionImpuestoFecha($data['fecha_transaccion']);
            // Si no existe, generar una nueva declaración de impuesto
            if (!$declaracionImpuesto) {
                $this->log("No existe declaración de impuesto para la fecha: " . $data['fecha_transaccion']);
                // Generar una nueva declaración si no existe
                $declaracionImpuesto = $this->generarDeclaracionImpuesto($tipoImpuesto, $data['fecha_transaccion']);
            }
            $this->log("Declaración de impuesto generada con ID: " . $declaracionImpuesto->id);

            // Calcular el monto del impuesto
            $montoImpuesto = $tipoImpuesto->calcularMontoImpuesto($data['monto_transaccion']);

            // Preparar los datos para la transacción
            $transaccionData = [
                'id_declaracion_impuesto' => $declaracionImpuesto->id,
                'tipo_impuesto_id' => $tipoImpuesto->id,
                'monto_transaccion' => $data['monto_transaccion'],
                'monto_impuesto' => $montoImpuesto,
                'fecha_transaccion' => date('Y-m-d', strtotime($data['fecha_transaccion'])),
                'descripcion' => $this->generarDescripcion($tipoImpuesto, $data),
            ];

            // Crear la transacción
            $transaccion = ImpuestoTransaccion::create($transaccionData);

            // Registrar en los logs
            $this->log("Transacción de impuesto creada con ID: " . $transaccion->id .
                " - Monto: Q" . $transaccion->monto_transaccion .
                " - Monto Impuesto: Q" . $transaccion->monto_impuesto .
                " - Fecha: " . $transaccion->fecha_transaccion);

            // Bloquear el monto del impuesto en la cuenta interna
            $this->tipoCuentaInternaService->bloquearMonto($data['id_cuenta'], $transaccion->monto_impuesto);

            $this->log("Monto bloqueado en la cuenta interna: " . $data['id_cuenta'] . " por Q" . $transaccion->monto_impuesto);
            // Confirmar la transacción de base de datos
            DB::commit();

            return $transaccion;
        } catch (\Exception $e) {
            $this->manejarError($e);
        }
    }

    /**
     * Genera una descripción para la transacción
     *
     * @param object $tipoImpuesto Tipo de impuesto
     * @param array $data Datos de la transacción
     * @return string Descripción generada
     */
    private function generarDescripcion($tipoImpuesto, array $data): string
    {
        return sprintf(
            'Se registró el pago del impuesto %s del monto de Q%.2f. Motivo: %s',
            $tipoImpuesto->nombre,
            $data['monto_transaccion'],
            $data['descripcion']
        );
    }

    /**
     * Valida los datos necesarios para crear una transacción
     *
     * @param array $data Datos a validar
     * @throws Exception Si los datos son inválidos
     */
    private function validate($data)
    {
        if (!isset($data['tipo_impuesto'])) {
            $this->lanzarExcepcionConCodigo("El tipo de impuesto es requerido");
        }
        if ($data['monto_transaccion'] <= 0.001) {
            $this->lanzarExcepcionConCodigo("El monto de la transacción debe ser mayor a cero");
        }

        if (!isset($data['fecha_transaccion'])) {
            $this->lanzarExcepcionConCodigo("La fecha de la transacción es requerida");
        }
        if (!isset($data['descripcion'])) {
            $this->lanzarExcepcionConCodigo("La descripción de la transacción es requerida");
        }
        if (!isset($data['id_cuenta'])) {
            $this->lanzarExcepcionConCodigo("El id de la cuenta es requerido");
        }
    }

    private function generarDeclaracionImpuesto($tipoImpuesto, $fechaTransaccion)
    {
        $this->log("Generando declaración de impuesto para el tipo: " . $tipoImpuesto->nombre);
        $this->log("Fecha de transacción: " . $fechaTransaccion);
        $data = $this->generarDataDeclaracionImpuesto($tipoImpuesto, $fechaTransaccion);
        $declaracionImpuesto = $this->declaracionImpuestoService->createDeclaracionImpuesto($data);
        if (!$declaracionImpuesto) {
            $this->lanzarExcepcionConCodigo("Error al crear la declaración de impuesto");
        }
        return $declaracionImpuesto;
    }

    private function generarDataDeclaracionImpuesto($tipoImpuesto, $fechaTransaccion)
    {
        $declaracionImpuestoUltima = $tipoImpuesto->declaracionImpuestoMasCercana($fechaTransaccion);
        $this->log("Declaración de impuesto más cercana: " . ($declaracionImpuestoUltima ? $declaracionImpuestoUltima->id : 'Ninguna'));
        if ($declaracionImpuestoUltima) {
            $fechaInicio = $this->generarFechaInicio($tipoImpuesto, $declaracionImpuestoUltima->fecha_fin);
        } else {
            $fechaInicio = $this->crearPrimeraFecha($tipoImpuesto, $fechaTransaccion);
        }
        $this->log("Fecha de inicio generada: " . $fechaInicio->format('Y-m-d'));
        $fechaFin = $this->generarFechaFin($tipoImpuesto, $fechaInicio);

        $this->log("Fecha de fin generada: " . $fechaFin->format('Y-m-d'));
        $data = [
            'id_tipo_impuesto' => $tipoImpuesto->id,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ];
        $this->log("Datos de declaración de impuesto generados: " . json_encode($data));
        if($fechaTransaccion > $fechaFin) {
            $this->log("La fecha de transacción es posterior a la fecha de fin de la declaración, ajustando fecha de fin.");
            $this->generarDataDeclaracionImpuesto($tipoImpuesto, $fechaTransaccion);
        }
        return $data;
    }

    private function obtenerNumeroSiguienteMes($fecha, $meses)
    {
        $this->log("El tipo de \$fecha es: " . gettype($fecha));
        if (gettype($fecha) == 'string') {
            $fechaConvertida = new DateTime($fecha);
            $this->log("La fecha convertida es: " . $fechaConvertida->format('Y-m-d'));
        } else {
            $fechaConvertida = $fecha;
        }

        $fechaConvertida->modify("+$meses month");
        return $fechaConvertida;
    }

    private function generarFecha($fechaBase, $mesesAdicionales, $dia)
    {
        try {
            // Obtener la fecha del mes correspondiente
            $siguienteFecha = $this->obtenerNumeroSiguienteMes($fechaBase, $mesesAdicionales);

            // Extraer año y mes
            $anio = (int) $siguienteFecha->format('Y');
            $mes = (int) $siguienteFecha->format('m');

            // Ajustar el día al rango válido del mes
            $diaAjustado = $this->ajustarDiaAlRangoDelMes($anio, $mes, $dia);

            // Generar la fecha final
            return new DateTime("{$anio}-{$mes}-{$diaAjustado}");
        } catch (\Exception $e) {
            $this->manejarError($e);
        }
    }

    private function crearPrimeraFecha($tipoImpuesto, $fechaBase)
    {
        try {
            // Determinar el mes inicial según el tipo de impuesto
            $mesInicial = $this->getMesSegunTipoImpuesto($tipoImpuesto, $fechaBase);
            $this->log("Mes inicial determinado: " . $mesInicial);
            // Extraer el año de la fecha base
            $anio = (int) date('Y', strtotime($fechaBase));
            $this->log("Año extraído de la fecha base: " . $anio);
            // Ajustar el día al rango válido del mes
            $diaAjustado = $this->ajustarDiaAlRangoDelMes($anio, $mesInicial, $tipoImpuesto->dia_inicio);

            // Generar la fecha inicial
            return new DateTime("{$anio}-{$mesInicial}-{$diaAjustado}");
        } catch (\Exception $e) {
            $this->manejarError($e);
        }
    }

    private function generarFechaInicio($tipoImpuesto, $fechaFinUltimoPeriodo): DateTime
    {
        return $this->generarFecha($fechaFinUltimoPeriodo, 1, $tipoImpuesto->dia_inicio);
    }

    /**
     * Obtiene el mes inicial del período fiscal según el tipo de impuesto y la fecha proporcionada
     *
     * @param object $tipoImpuesto Objeto del tipo de impuesto con información del plazo
     * @param string $fecha Fecha base para determinar el mes (formato Y-m-d)
     * @return int Mes inicial del período fiscal
     * @throws \Exception Si el plazo del tipo de impuesto no es válido
     */
    private function getMesSegunTipoImpuesto($tipoImpuesto, $fecha)
    {
        // Obtener el mes de la fecha proporcionada
        $mes = (int) date('m', strtotime($fecha));

        switch ($tipoImpuesto->plazo) {
            case PlazoImpuesto::$MENSUAL:
                // Para impuestos mensuales, el mes es el mismo
                return $mes;

            case PlazoImpuesto::$TRIMESTRAL:
                // Determinar el trimestre correspondiente
                if ($mes >= 1 && $mes <= 3) {
                    return 1; // Primer trimestre
                } elseif ($mes >= 4 && $mes <= 6) {
                    return 4; // Segundo trimestre
                } elseif ($mes >= 7 && $mes <= 9) {
                    return 7; // Tercer trimestre
                } elseif ($mes >= 10 && $mes <= 12) {
                    return 10; // Cuarto trimestre
                }
                break;

            case PlazoImpuesto::$ANUAL:
                // Para impuestos anuales, siempre es el primer mes del año
                return 1;

            default:
                // Manejo de error si el plazo no es válido
                $this->lanzarExcepcionConCodigo("El plazo del tipo de impuesto no es válido.");
        }

        // Si no se encuentra un caso válido, lanzar una excepción
        $this->lanzarExcepcionConCodigo("No se pudo determinar el mes según el tipo de impuesto.");
    }

    private function generarFechaFin($tipoImpuesto, $fechaInicio): DateTime
    {
        return $this->generarFecha($fechaInicio, $tipoImpuesto->mesesPlazo() -1, $tipoImpuesto->dia_fin);
    }
    private function ajustarDiaAlRangoDelMes($anio, $mes, $dia)
    {
        $ultimoDiaDelMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);

        if ($dia < 1) {
            return 1;
        }

        if ($dia > $ultimoDiaDelMes) {
            return $ultimoDiaDelMes;
        }

        return $dia;
    }
}
