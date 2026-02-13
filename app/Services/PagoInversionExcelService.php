<?php

namespace App\Services;

use App\Models\Inversion;
use App\Models\PagoInversion;
use App\Traits\ErrorHandler;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\UploadedFile;

class PagoInversionExcelService extends PagoInversionService
{

    use ErrorHandler;
    use Loggable;


    public function generarExcel(Inversion $inversion): array
    {
       $pagos = $inversion->pagosInversion()->where('existente', true)->where('realizado', false)->get();

        if ($pagos->isEmpty()) {
            $this->lanzarExcepcionConCodigo("No se encontraron pagos pendientes para la inversión ID: {$inversion->id}");
        }

        return $this->generarExcelData($pagos);
    }

    /**
     * Genera la estructura de datos para la plantilla Excel
     * Incluye columnas: id, monto, fecha, no_boleta
     *
     * @param $pagos Pagos a incluir en el Excel
     * @return array Array con instrucciones, encabezados y datos
     */
    private function generarExcelData($pagos): array
    {
        $this->log("Generando datos de Excel para {$pagos->count()} pagos de inversión");

        // Instrucciones para el usuario
        $instrucciones = [
            'INSTRUCCIONES:',
            '1. Complete el campo "No. Boleta" con el número de boleta del depósito realizado.',
            '2. Asegúrese de que el Monto y Fecha coincidan con el depósito bancario.',
            '3. No modifique otros campos (ID, No. Pago, Monto, Fecha).',
            '4. Guarde el archivo y cárguelo en el sistema para procesar los pagos.',
            ''
        ];

        // Encabezados del Excel
        $encabezados = [
            'ID',
            'No. Pago',
            'Monto',
            'Fecha',
            'No. Boleta (Ingresado por Usuario)'
        ];

        // Datos de los pagos
        $datos = [];
        foreach ($pagos as $index => $pago) {
            $datos[] = [
                'id' => $pago->id,
                'numeroPago' => $pago->numero_pago ?? ($index + 1),
                'monto' => $pago->monto ?? 0,
                'fecha' => $pago->fecha ? date('d/m/Y', strtotime($pago->fecha)) : '',
                'no_boleta' => '' // Campo vacío para que el usuario ingrese el número
            ];
        }

        $this->log("Estructura de Excel generada con " . count($datos) . " registros");

        return [
            'instrucciones' => $instrucciones,
            'encabezados' => $encabezados,
            'datos' => $datos,
            'totalRegistros' => count($datos)
        ];
    }

    /**
     * Lee un archivo Excel y extrae los datos
     * Espera las columnas: ID, No. Pago, Monto, Fecha, No. Boleta
     *
     * @param UploadedFile $archivo Archivo Excel a leer
     * @throws \Exception Si hay error al leer el archivo
     */
    private function leerExcel(UploadedFile $archivo)
    {
        try {
            $this->log("Iniciando lectura del archivo Excel: {$archivo->getClientOriginalName()}");

            // Cargar el archivo Excel
            $spreadsheet = IOFactory::load($archivo->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();

            $datos = [];
            $filas = $worksheet->toArray();

            // Omitir la primera fila (encabezados)
            for ($i = 1; $i < count($filas); $i++) {
                $fila = $filas[$i];

                // Ignorar filas vacías
                if (empty($fila[0]) && empty($fila[1]) && empty($fila[4])) {
                    continue;
                }

                // Mapear los datos a un array asociativo
                $datos[] = [
                    'id' => isset($fila[0]) ? (int)$fila[0] : null,
                    'numeroPago' => isset($fila[1]) ? $fila[1] : null,
                    'monto' => isset($fila[2]) ? (float)$fila[2] : null,
                    'fecha' => isset($fila[3]) ? $fila[3] : null,
                    'no_boleta' => isset($fila[4]) ? trim($fila[4]) : ''
                ];
            }

            $this->log("Archivo Excel leído exitosamente. Total de filas: " . count($datos));

            return $datos;

        } catch (\Exception $e) {
            $this->logError("Error al leer archivo Excel: {$e->getMessage()}");
            $this->lanzarExcepcionConCodigo("Error al leer el archivo Excel: {$e->getMessage()}");
        }
    }

    /**
     * Procesa el archivo Excel de pagos de inversión
     * Lee los datos, valida que pertenezcan a la inversión y almacena los pagos
     *
     * @param UploadedFile $archivo Archivo Excel con los pagos
     * @param Inversion $inversion Inversión a la cual pertenecen los pagos
     * @return array Resultado del procesamiento con detalles
     * @throws \Exception Si hay errores en validación o almacenamiento
     */
    public function procesarExcelPagos(UploadedFile $archivo, Inversion $inversion): array
    {
        $datos = $this->leerExcel($archivo);

        DB::beginTransaction();
        try {
            $this->log("Iniciando procesamiento de Excel para inversión ID: {$inversion->id}");

            $resultado = [
                'procesados' => 0,
                'errores' => [],
                'detalles' => []
            ];

            // Validar que hay datos
            if (empty($datos)) {
                $this->lanzarExcepcionConCodigo("El archivo Excel no contiene datos");
            }

            foreach ($datos as $index => $fila) {
                $numeroFila = $index + 2; // +2 porque fila 1 es encabezado y arrays son 0-indexed

                try {
                    // Validar que exista el ID del pago
                    if (empty($fila['id'])) {
                        throw new \Exception("Fila {$numeroFila}: ID del pago es requerido");
                    }

                    // Validar que exista el número de boleta
                    if (empty($fila['no_boleta'])) {
                        throw new \Exception("Fila {$numeroFila}: No. Boleta es requerido");
                    }

                    // Obtener el pago
                    $pago = $this->getPagoInversion($fila['id']);
                    $this->log("Procesando pago ID: {$pago->id}");

                    // Validar que el pago pertenezca a la inversión
                    if ($pago->inversion_id !== $inversion->id) {
                        throw new \Exception("Fila {$numeroFila}: El pago ID {$pago->id} no pertenece a la inversión ID {$inversion->id}");
                    }

                    // Validar que el pago no esté realizado
                    if ($pago->realizado) {
                        throw new \Exception("Fila {$numeroFila}: El pago ID {$pago->id} ya ha sido realizado");
                    }

                    // Validar que el pago esté marcado como existente
                    if (!$pago->existente) {
                        throw new \Exception("Fila {$numeroFila}: El pago ID {$pago->id} no está marcado como existente");
                    }

                    // Actualizar el pago con el número de boleta
                    $pago->no_boleta = $fila['no_boleta'];
                    $pago->realizado = true;
                    $pago->save();

                    $resultado['procesados']++;
                    $resultado['detalles'][] = [
                        'fila' => $numeroFila,
                        'id_pago' => $pago->id,
                        'no_boleta' => $fila['no_boleta'],
                        'estado' => 'Procesado exitosamente'
                    ];

                    $this->log("Pago ID {$pago->id} actualizado con boleta: {$fila['no_boleta']}");

                } catch (\Exception $e) {
                    $resultado['errores'][] = [
                        'fila' => $numeroFila,
                        'error' => $e->getMessage()
                    ];
                    $this->log("Error en fila {$numeroFila}: {$e->getMessage()}");
                }
            }

            // Si hay errores, no guardar cambios
            if (!empty($resultado['errores'])) {
                DB::rollBack();
                $this->lanzarExcepcionConCodigo(
                    "Se encontraron errores en el archivo Excel. Procesados: {$resultado['procesados']}, Errores: " .
                    count($resultado['errores']) . ". Detalles: " . json_encode($resultado['errores'])
                );
            }

            DB::commit();
            $this->log("Procesamiento completado: {$resultado['procesados']} pagos procesados exitosamente");

            return $resultado;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError("Error al procesar Excel de pagos: {$e->getMessage()}");
            throw $e;
        }
    }
}

