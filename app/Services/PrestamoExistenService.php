<?php

namespace App\Services;

use App\Constants\EstadoPrestamo;
use App\EstadosPrestamo\ControladorEstado;
use App\Traits\Loggable;
use App\Traits\ErrorHandler;
use PhpOffice\PhpSpreadsheet\IOFactory;


class PrestamoExistenService
{
    use ErrorHandler;

    use Loggable;
    protected $controladorEstado;

    protected $excelService;

    protected $cuotaHipotecaService;

    public function __construct(
        ControladorEstado $controladorEstado,
        SimpleExcelService $excelService,
        CuotaHipotecaService $cuotaHipotecaService
    ) {
        $this->controladorEstado = $controladorEstado;
        $this->excelService = $excelService;
        $this->cuotaHipotecaService = $cuotaHipotecaService;
    }

    public function procesarPrestamoExistente($prestamo, $data, $excelFile)
    {
        $this->prestamoCreado($prestamo, $data);
        $this->prestamoAutorizado($prestamo, $data);
        $this->desembolsarPrestamo($prestamo, $data);

        // Verificar si viene un archivo Excel en los datos
        if ($excelFile) {
            $this->log("Procesando archivo Excel de depósitos existentes");
            $depositosFromExcel = $this->procesarExcelDepositos($excelFile);
            $this->log("Total de depósitos procesados desde Excel: " . count($depositosFromExcel));
            $this->ingresarDepositosExistentes($prestamo, $depositosFromExcel);
        }
    }


    private function ingresarDepositosExistentes($prestamo, $depositos)
    {
        $this->log("Ingresando depósitos existentes para el préstamo: {$prestamo->codigo}");

        foreach ($depositos as $deposito) {
            $saldo = $this->cuotaHipotecaService->registrarPagoExistente($prestamo, $deposito);
            if ($saldo < 0) {
                $this->log("El saldo del préstamo {$prestamo->codigo} es negativo después de registrar el depósito");
            }
        }
    }

    private function prestamoCreado($prestamo, $data)
    {
        $this->log('Cambio de estado a CREADO' .
            ' para el prestamo con id: ' . $prestamo->id);
        $dataEstado = [
            'razon' => 'Préstamo creado',
            'estado' => EstadoPrestamo::$CREADO,
            'fecha' => $data['fecha_creacion'],
        ];
        $this->controladorEstado->cambiarEstado($prestamo, $dataEstado);
    }

    private function prestamoAutorizado($prestamo, $data)
    {

        $this->log('Cambio de estado a APROBADO' .
            ' para el prestamo con id: ' . $prestamo->id);
        $dataCambionEstado = [
            'estado' => EstadoPrestamo::$APROBADO,
            'fecha' => $data['fecha_autorizacion'],
            'razones' => 'Se autorizó el préstamo automaticamente porque ya existe',
            'gastos_formalidad' => $data['gastos_formalidad'],
            'gastos_administrativos' => $data['gastos_administrativos'],
        ];
        $this->controladorEstado->cambiarEstado($prestamo, $dataCambionEstado);
    }

    private function desembolsarPrestamo($prestamo, $data)
    {
        $this->log('Cambio de estado a DESEMBOLZADO' .
            ' para el prestamo con id: ' . $prestamo->id);
        $dataDesembolso = [
            'estado' => EstadoPrestamo::$DESEMBOLZADO,
            'razones' => 'Se desembolsó el préstamo automáticamente porque ya existe',
            'fecha' => $data['fecha_desembolso'],
            'numero_documento' => $data['numero_documento'],
            'tipo_documento' => $data['tipo_documento']
        ];
        $this->controladorEstado->cambiarEstado($prestamo, $dataDesembolso);
    }


    /**
     * Procesa archivo Excel de depósitos y extrae los datos
     *
     * @param \Illuminate\Http\UploadedFile $excelFile Archivo Excel
     * @return array Array de depósitos extraídos del Excel
     * @throws \Exception Si el archivo no es válido o hay errores de lectura
     */
    private function procesarExcelDepositos($excelFile): array
    {
        try {
            $this->log("Iniciando procesamiento de archivo Excel de depósitos");

            // Validar que es un archivo Excel válido
            if (!$this->excelService->isValidExcel($excelFile)) {
                $this->lanzarExcepcionConCodigo("El archivo proporcionado no es un Excel válido");
            }

            // Leer el archivo Excel
            $spreadsheet = IOFactory::load($excelFile->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            $depositos = [];
            $errores = [];
            $this->log("Leyendo filas del archivo Excel, total filas: {$highestRow}");

            // Leer desde la fila 2 (saltando encabezados)
            for ($row = 2; $row <= $highestRow; $row++) {
                $fechaDeposito = $worksheet->getCell("A{$row}")->getCalculatedValue();
                $monto = $worksheet->getCell("B{$row}")->getCalculatedValue();
                $tipoDocumento = $worksheet->getCell("C{$row}")->getCalculatedValue();
                $numeroDocumento = $worksheet->getCell("D{$row}")->getCalculatedValue();
                $penalizacion = $worksheet->getCell("E{$row}")->getCalculatedValue();

                // Saltar filas vacías
                if (empty($fechaDeposito) && empty($monto) && empty($tipoDocumento) && empty($numeroDocumento) && empty($penalizacion)) {
                    continue;
                }
                $this->log("Procesando fila {$row}: Fecha={$fechaDeposito}, Monto={$monto}, TipoDoc={$tipoDocumento}, NumDoc={$numeroDocumento}, Penalización={$penalizacion}");

                // Validar que todos los campos estén presentes
                if (
                    empty($fechaDeposito) ||
                    $monto === '' || // Verifica si monto está vacío (cadena vacía)
                    empty($tipoDocumento) ||
                    empty($numeroDocumento) ||
                    $penalizacion === '' // Verifica si penalizacion está vacío (cadena vacía)
                ) {
                    $errores[] = "Fila {$row}: Todos los campos son requeridos";
                    continue;
                }

                // Convertir fecha si es numérica (formato Excel)
                if (is_numeric($fechaDeposito)) {
                    $fechaDeposito = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fechaDeposito)->format('Y-m-d');
                } else {
                    // Intentar parsear la fecha
                    $fechaObj = \DateTime::createFromFormat('d/m/Y', $fechaDeposito);
                    if (!$fechaObj) {
                        $errores[] = "Fila {$row}: Fecha inválida. Formato esperado: DD/MM/YYYY";
                        continue;
                    }
                    $fechaDeposito = $fechaObj->format('Y-m-d');
                }

                // Validar monto
                if (!is_numeric($monto) || $monto <= 0) {
                    $errores[] = "Fila {$row}: El monto debe ser un número mayor a 0";
                    continue;
                }

                // Validar tipo de documento
                $tiposValidos = ['CHEQUE', 'DEPOSITO', 'TRANSFERENCIA'];
                $tipoDocumento = strtoupper(trim($tipoDocumento));
                if (!in_array($tipoDocumento, $tiposValidos)) {
                    $errores[] = "Fila {$row}: Tipo de documento inválido. Tipos válidos: " . implode(', ', $tiposValidos);
                    continue;
                }

                // Validar número de documento
                $numeroDocumento = trim($numeroDocumento);
                if (empty($numeroDocumento)) {
                    $errores[] = "Fila {$row}: El número de documento no puede estar vacío";
                    continue;
                }

                // Validar penalización
                if (!is_numeric($penalizacion) || $penalizacion < 0) {
                    $errores[] = "Fila {$row}: La penalización debe ser un número mayor o igual a 0";
                    continue;
                }

                // Agregar depósito válido
                $depositos[] = [
                    'fecha_documento' => $fechaDeposito,
                    'monto' => (float) $monto,
                    'tipo_documento' => $tipoDocumento,
                    'numero_documento' => $numeroDocumento,
                    'penalizacion' => (float) $penalizacion,
                    'fila_excel' => $row
                ];
            }

            // Si hay errores, lanzar excepción con todos los errores
            if (!empty($errores)) {
                $this->lanzarExcepcionConCodigo("Errores en el archivo Excel:\n" . implode("\n", $errores));
            }

            // Si no hay depósitos válidos
            if (empty($depositos)) {
                $this->lanzarExcepcionConCodigo("No se encontraron depósitos válidos en el archivo Excel");
            }

            $this->log("Procesados " . count($depositos) . " depósitos desde Excel exitosamente");
            return $depositos;
        } catch (\Exception $e) {
            $this->log("Error al procesar archivo Excel: " . $e->getMessage());
            throw $e;
        }
    }
}
