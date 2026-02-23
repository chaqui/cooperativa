<?php

namespace App\Services;

use App\Models\Inversion;
use App\Models\PagoInversion;
use App\Traits\ErrorHandler;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PagoInversionExcelService extends PagoInversionService
{

    use ErrorHandler;
    use Loggable;


    /**
     * Genera y descarga directamente un archivo Excel con pagos pendientes de inversión
     *
     * @param Inversion $inversion Inversión de la cual obtener los pagos pendientes
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse Descarga del archivo Excel
     * @throws \Exception Si no hay pagos pendientes o hay error al crear el archivo
     */
    public function generarExcel(Inversion $inversion)
    {
       $pagos = $inversion->pagosInversion()->where('existente', true)->where('realizado', false)->get();

        if ($pagos->isEmpty()) {
            $this->lanzarExcepcionConCodigo("No se encontraron pagos pendientes para la inversión ID: {$inversion->id}");
        }

        $datosExcel = $this->generarExcelData($pagos);
        $rutaArchivo = $this->crearArchivoExcel($datosExcel, $inversion->id);

        // Retornar descarga directa del archivo sin guardarlo permanentemente
        return $this->descargarArchivo($rutaArchivo);
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
     * Crea el archivo Excel físico con los datos proporcionados
     *
     * @param array $datosExcel Datos del Excel generados por generarExcelData
     * @param int $inversionId ID de la inversión para el nombre del archivo
     * @return string Ruta del archivo creado
     * @throws \Exception Si hay error al crear el archivo
     */
    private function crearArchivoExcel(array $datosExcel, int $inversionId)
    {
        try {
            $this->log("Creando archivo Excel para inversión ID: {$inversionId}");

            // Crear nueva spreadsheet
            $spreadsheet = new Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle('Pagos Pendientes');

            $filaActual = 1;

            // Agregar instrucciones
            foreach ($datosExcel['instrucciones'] as $instruccion) {
                $worksheet->setCellValue('A' . $filaActual, $instruccion);

                // Estilizar las instrucciones
                if ($filaActual === 1) {
                    // Título principal en negrita
                    $worksheet->getStyle('A' . $filaActual)->getFont()->setBold(true)->setSize(12);
                    $worksheet->getStyle('A' . $filaActual)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFE6E6FA');
                } else {
                    // Instrucciones en texto normal
                    $worksheet->getStyle('A' . $filaActual)->getFont()->setSize(10);
                }

                $filaActual++;
            }

            // Agregar una fila vacía después de las instrucciones
            $filaActual++;

            // Agregar encabezados
            $columnas = ['A', 'B', 'C', 'D', 'E'];
            foreach ($datosExcel['encabezados'] as $index => $encabezado) {
                $worksheet->setCellValue($columnas[$index] . $filaActual, $encabezado);

                // Estilizar encabezados
                $worksheet->getStyle($columnas[$index] . $filaActual)->getFont()
                    ->setBold(true)
                    ->setSize(11);
                $worksheet->getStyle($columnas[$index] . $filaActual)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD3D3D3');
                $worksheet->getStyle($columnas[$index] . $filaActual)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $filaEncabezados = $filaActual;
            $filaActual++;

            // Agregar datos
            foreach ($datosExcel['datos'] as $filaData) {
                $worksheet->setCellValue('A' . $filaActual, $filaData['id']);
                $worksheet->setCellValue('B' . $filaActual, $filaData['numeroPago']);
                $worksheet->setCellValue('C' . $filaActual, $filaData['monto']);
                $worksheet->setCellValue('D' . $filaActual, $filaData['fecha']);
                $worksheet->setCellValue('E' . $filaActual, $filaData['no_boleta']);

                // Centrar los datos
                foreach ($columnas as $col) {
                    $worksheet->getStyle($col . $filaActual)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $filaActual++;
            }

            // Ajustar ancho de columnas
            $worksheet->getColumnDimension('A')->setWidth(10);  // ID
            $worksheet->getColumnDimension('B')->setWidth(12);  // No. Pago
            $worksheet->getColumnDimension('C')->setWidth(15);  // Monto
            $worksheet->getColumnDimension('D')->setWidth(12);  // Fecha
            $worksheet->getColumnDimension('E')->setWidth(30);  // No. Boleta

            // Agregar bordes a la tabla de datos
            $rangoTabla = 'A' . $filaEncabezados . ':E' . ($filaActual - 1);
            $worksheet->getStyle($rangoTabla)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            // Proteger celdas (excepto la columna E - No. Boleta)
            $worksheet->getProtection()->setSheet(true);
            $worksheet->getStyle('E:E')->getProtection()->setLocked(false);

            // Crear el nombre del archivo
            $nombreArchivo = "pagos_pendientes_inversion_{$inversionId}.xlsx";
            $rutaCompleta = storage_path("app/temp/{$nombreArchivo}");

            // Asegurar que el directorio existe
            $directorio = dirname($rutaCompleta);
            if (!file_exists($directorio)) {
                mkdir($directorio, 0755, true);
            }

            // Guardar el archivo
            $writer = new Xlsx($spreadsheet);
            $writer->save($rutaCompleta);

            $this->log("Archivo Excel creado exitosamente: {$rutaCompleta}");

            return $rutaCompleta;

        } catch (\Exception $e) {
            $this->logError("Error al crear archivo Excel: {$e->getMessage()}");
            $this->lanzarExcepcionConCodigo("Error al crear el archivo Excel: {$e->getMessage()}");
        }
    }

    /**
     * Lee un archivo Excel y extrae los datos
     * Busca automáticamente donde empiezan los datos (después de las instrucciones)
     * Espera las columnas: ID, No. Pago, Monto, Fecha, No. Boleta
     *
     * @param UploadedFile $archivo Archivo Excel a leer
     * @return array Datos extraídos del Excel
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

            // Buscar donde empiezan los encabezados (buscar la fila que contiene "ID")
            $filaEncabezados = -1;
            for ($i = 0; $i < count($filas); $i++) {
                if (!empty($filas[$i][0]) && strtoupper(trim($filas[$i][0])) === 'ID') {
                    $filaEncabezados = $i;
                    break;
                }
            }

            if ($filaEncabezados === -1) {
                throw new \Exception("No se encontraron los encabezados en el archivo Excel");
            }

            $this->log("Encabezados encontrados en la fila: " . ($filaEncabezados + 1));

            // Leer los datos a partir de la fila siguiente a los encabezados
            for ($i = $filaEncabezados + 1; $i < count($filas); $i++) {
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

            $this->log("Archivo Excel leído exitosamente. Total de filas de datos: " . count($datos));

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

    /**
     * Descarga el archivo Excel y lo elimina del servidor
     *
     * @param string $rutaArchivo Ruta completa del archivo a descargar
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Exception Si el archivo no existe
     */
    public function descargarArchivo(string $rutaArchivo)
    {
        if (!file_exists($rutaArchivo)) {
            $this->lanzarExcepcionConCodigo("El archivo no existe: {$rutaArchivo}");
        }

        $nombreArchivo = basename($rutaArchivo);

        return response()->download($rutaArchivo, $nombreArchivo)->deleteFileAfterSend(true);
    }

    /**
     * Limpia archivos temporales de Excel antiguos
     *
     * @param int $diasAntiguedad Archivos más antiguos que este número de días serán eliminados
     */
    public function limpiarArchivosTemporales(int $diasAntiguedad = 1): void
    {
        try {
            $directorioTemp = storage_path('app/temp');

            if (!is_dir($directorioTemp)) {
                return;
            }

            $archivos = glob($directorioTemp . '/pagos_pendientes_inversion_*.xlsx');
            $tiempoLimite = time() - ($diasAntiguedad * 24 * 60 * 60);

            $archivosEliminados = 0;
            foreach ($archivos as $archivo) {
                if (filemtime($archivo) < $tiempoLimite) {
                    unlink($archivo);
                    $archivosEliminados++;
                }
            }

            if ($archivosEliminados > 0) {
                $this->log("Archivos temporales eliminados: {$archivosEliminados}");
            }

        } catch (\Exception $e) {
            $this->logError("Error al limpiar archivos temporales: {$e->getMessage()}");
        }
    }
}

