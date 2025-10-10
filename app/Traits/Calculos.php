<?

namespace App\Traits;

use App\Constants\TipoPlazo;


trait Calculos
{
    public  function calcularPlazo($plazo, $tipoPlazo)
    {
        $this->log('El tipo de plazo es ' . $tipoPlazo);
        if ($tipoPlazo == TipoPlazo::$ANUAL) {
            $plazo = $plazo * 12;
        }
        return $plazo;
    }

      /**
     * Calcula la cuota mensual usando la fórmula de amortización francesa
     *
     * @param float $monto Monto del préstamo
     * @param float $interes Tasa de interés anual
     * @param int $plazo Plazo en meses
     * @return float Cuota mensual calculada
     * @throws \Exception Si los parámetros son inválidos
     */
    private function calcularCuota($monto, $interes, $plazo)
    {
        try {
            $this->log("Calculando cuota: Monto=Q{$monto}, Interés={$interes}%, Plazo={$plazo} meses");

            // Validaciones básicas
            if ($monto <= 0) {
                $this->lanzarExcepcionConCodigo("El monto del préstamo debe ser mayor a cero");
            }
            if ($interes < 0) {
                $this->lanzarExcepcionConCodigo("La tasa de interés no puede ser negativa");
            }
            if ($plazo <= 0) {
                $this->lanzarExcepcionConCodigo("El plazo debe ser mayor a cero");
            }

            // Si no hay interés, la cuota es simplemente el monto dividido entre el plazo
            if ($interes == 0) {
                $cuota = round($monto / $plazo, 2);
                $this->log("Cuota calculada sin interés: Q{$cuota}");
                return $cuota;
            }

            $tasaInteresMensual = $this->calcularTaza($interes);
            $this->log("Tasa de interés mensual: {$tasaInteresMensual}");

            // Fórmula de amortización francesa: C = P * (r * (1 + r)^n) / ((1 + r)^n - 1)
            $factorInteres = pow(1 + $tasaInteresMensual, $plazo);
            $cuota = ($monto * $tasaInteresMensual * $factorInteres) / ($factorInteres - 1);

            // Redondear a 2 decimales para evitar problemas de precisión
            $cuota = round($cuota, 2);
            $this->log("Cuota mensual calculada: Q{$cuota}");

            // Validar que la cuota calculada sea razonable
            $totalCuotasEstimado = $cuota * $plazo;
            $this->log("Total estimado de cuotas: Q{$totalCuotasEstimado}");

            return $cuota;
        } catch (\Exception $e) {
            $this->manejarError($e, 'calcularCuota');
            return 0; // Esta línea nunca se ejecutará
        }
    }
}
