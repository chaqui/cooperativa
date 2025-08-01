<?php

namespace App\Services;

use App\Constants\InicialesCodigo;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use App\Traits\ErrorHandler;

class CodigoService
{
    use ErrorHandler;
    use Loggable;

    //al agregar un nuevo tipo de código, se debe agregar a la clase InicialesCodigo
    private $tipoCodigo;

    public function __construct($tipoCodigo)
    {
        $this->tipoCodigo = InicialesCodigo::getTipo($tipoCodigo);
    }

    private function getCorrelativo()
    {
        $this->log('Obteniendo el correlativo de la secuencia: ' . $this->tipoCodigo['secuencia']);
        $result = DB::select('SELECT nextval(\'' . $this->tipoCodigo['secuencia'] . '\') AS correlativo');
        $correlativo = $result[0]->correlativo;
        $this->log('El correlativo obtenido es: ' . $correlativo);
        return $correlativo;
    }

    /**
     * Genera un código único basado en el tipo de código configurado
     *
     * @return string Código generado
     * @throws \Exception Si ocurre un error al generar el código
     */
    protected function createCode(): string
    {
        try {
            // Validar que el tipo de código esté configurado correctamente
            if (empty($this->tipoCodigo['prefijo']) || empty($this->tipoCodigo['secuencia'])) {
                $this->lanzarExcepcionConCodigo("El tipo de código no está configurado correctamente.");
            }

            $this->log('Generando el código para el tipo: ' . $this->tipoCodigo['prefijo']);

            // Obtener el correlativo de la secuencia
            $correlativo = $this->getCorrelativo();

            // Validar que el correlativo sea un número válido
            if (!is_numeric($correlativo) || $correlativo <= 0) {
                $this->lanzarExcepcionConCodigo("El correlativo obtenido no es válido: {$correlativo}");
            }

            // Formatear el código con las iniciales y el correlativo
            $codigo = sprintf('%s-%05d', $this->tipoCodigo['prefijo'], $correlativo);

            $this->log("Código generado: {$codigo}");
            return $codigo;
        } catch (\Exception $e) {
            $this->manejarError($e, 'createCode');
            // Esta línea nunca se alcanzará porque manejarError siempre lanza excepción
            throw new \Exception("Error inesperado en createCode");
        }
    }
}
