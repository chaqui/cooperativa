<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\EstadosPrestamo\ControladorEstado;
use Carbon\Carbon;
use App\Traits\ErrorHandler;




class PrestamoExcelService extends PrestamoService
{
    private BitacoraInteresService $bitacoraInteresService;

    public function __construct(
        ControladorEstado $controladorEstado,
        ClientService $clientService,
        PropiedadService $propiedadService,
        CatologoService $catalogoService,
        UserService $userService,
        CuotaHipotecaService $cuotaHipotecaService,
        PrestamoExistenService $prestamoExistenteService,
        BitacoraInteresService $bitacoraInteresService,
        PrestamoArchivoService $prestamoArchivoService,
        PrestamoRemplazadoService $prestamoRemplazadoService
    ) {
        parent::__construct($controladorEstado, $clientService, $propiedadService, $catalogoService, $userService, $cuotaHipotecaService, $prestamoExistenteService, $prestamoArchivoService, $prestamoRemplazadoService);
        $this->bitacoraInteresService = $bitacoraInteresService;
    }


    /**
     * Genera un archivo Excel con los datos de préstamos
     * @param mixed $prestamos Un préstamo individual o array de préstamos
     * @return array Información del archivo generado (content, filename, headers)
     */
    public function generateExcel($prestamos = null)
    {
        if ($prestamos === null) {
            // Cargar todos los préstamos con las relaciones necesarias
            $prestamos = \App\Models\Prestamo_Hipotecario::with([
                'asesor',      // Relación con User (id_usuario)
                'cliente',     // Relación con Client
                'propiedad',   // Relación con Propiedad
                'estado'       // Relación con Estado
            ])->where('estado_id', '=', 3)->get();
        } else {
            // Si se pasan préstamos, asegurar que las relaciones estén cargadas
            if (is_array($prestamos) || $prestamos instanceof \Illuminate\Database\Eloquent\Collection) {
                // Obtener IDs de los préstamos
                $ids = collect($prestamos)->pluck('id')->toArray();

                // Recargar con relaciones
                $prestamos = \App\Models\Prestamo_Hipotecario::with([
                    'asesor',
                    'cliente',
                    'propiedad',
                    'estado'
                ])->whereIn('id', $ids)->get();
            } else {
                // Es un solo préstamo
                $prestamos = \App\Models\Prestamo_Hipotecario::with([
                    'asesor',
                    'cliente',
                    'propiedad',
                    'estado'
                ])->where('id', $prestamos->id)->get();
            }
        }

        $this->log('Generando archivo Excel con ' . count($prestamos) . ' préstamos');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar encabezados
        $headers = [
            'A1' => 'Nombre de Asesor Financiero',
            'B1' => 'No. de Financiamiento',
            'C1' => 'No. de Cliente',
            'D1' => 'Nombre del Cliente',
            'E1' => 'NIT del Cliente',
            'F1' => 'Teléfono',
            'G1' => 'GENERO',
            'H1' => 'MONTO ORIGINAL',
            'I1' => 'INTERES MENSUAL',
            'J1' => 'GARANTIA',
            'K1' => 'PLAZO (MESESx)',
            'L1' => 'DESTINO',
            'M1' => 'FECHA DE DESEMBOLSO',
            'N1' => 'FECHA DE FINALIZACION',
            'O1' => 'SALDO CAPITAL ACTUAL',
            'P1' => 'CUOTA CAPITAL',
            'Q1' => 'INTERES A LA FECHA',
            'R1' => 'ESTATUS',
            'S1' => 'Días de atraso',
            'T1' => 'CUOTA TOTAL'
        ];

        // Aplicar encabezados
        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        // Aplicar estilo a los encabezados
        $headerRange = 'A1:R1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'] // Amarillo
            ],
            'font' => [
                'bold' => true,
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Convertir a array/collection si es necesario para el foreach
        if (!is_array($prestamos) && !($prestamos instanceof \Illuminate\Database\Eloquent\Collection)) {
            $prestamos = [$prestamos];
        }

        // Llenar datos
        $row = 2;
        foreach ($prestamos as $p) {
            // Verificar que $p sea un modelo válido
            if (!($p instanceof \App\Models\Prestamo_Hipotecario)) {
                $this->log('Elemento no válido en la colección, saltando...');
                continue;
            }

            try {
                // Validar y obtener datos de forma segura
                $asesorNombre = '';
                if (isset($p->asesor) && $p->asesor) {
                    $asesorNombre = $p->asesor->name ?? '';
                }

                $clienteNombre = '';
                $clienteNit = '';
                if (isset($p->cliente) && $p->cliente) {
                    $nombres = $p->cliente->nombres ?? '';
                    $apellidos = $p->cliente->apellidos ?? '';
                    $clienteNombre = trim($nombres . ' ' . $apellidos);
                    $clienteNit = $p->cliente->nit ?? '';
                }

                $clienteCodigo = '';
                $clienteTelefono = '';
                $clienteGenero = '';
                if (isset($p->cliente) && $p->cliente) {
                    $clienteCodigo = $p->cliente->codigo ?? '';
                    $clienteTelefono = $p->cliente->telefono ?? '';
                    $clienteGenero = $this->catalogoService->getCatalogo($p->cliente->genero)['value'] ?? '';
                }

                $propiedadInfo = '';
                if (isset($p->propiedadAsociada) && $p->propiedadAsociada) {
                    $propiedadInfo = $this->catalogoService->getCatalogo($p->propiedadAsociada->tipo_propiedad)['value'] ?? '';
                }

                $estadoNombre = '';
                if (isset($p->estado) && $p->estado) {
                    $estadoNombre = $p->estado->nombre ?? '';
                }

                // Validar fechas de forma segura
                $fechaInicio = '';
                $fechaInicioAttr = $p->getAttribute('fecha_inicio');
                if (!empty($fechaInicioAttr)) {
                    try {
                        $fechaInicio = Carbon::parse($fechaInicioAttr)->format('d/m/Y');
                    } catch (\Exception $e) {
                        $this->log('Error al formatear fecha_inicio: ' . $e->getMessage());
                        $fechaInicio = '';
                    }
                }

                $fechaFin = '';
                $fechaFinAttr = $p->getAttribute('fecha_fin');
                if (!empty($fechaFinAttr)) {
                    try {
                        $fechaFin = Carbon::parse($fechaFinAttr)->format('d/m/Y');
                    } catch (\Exception $e) {
                        $this->log('Error al formatear fecha_fin: ' . $e->getMessage());
                        $fechaFin = '';
                    }
                }

                $capitalCuota = 0;
                if (method_exists($p, 'cuotaActiva') && $p->cuotaActiva()) {
                    $cuotaActiva = $p->cuotaActiva();
                    $capitalCuota = $cuotaActiva->capital - $cuotaActiva->capital_pagado;
                }

                $p->nombreDestino = $this->catalogoService->getCatalogo($p->destino)['value'] ?? 'No especificado';

                $cuotaActiva = $p->cuotaActiva();
                if (!$cuotaActiva) {
                    $interesAcumulado = 0;
                } else {
                    $interesAcumulado = $p->cuotaActiva() ? $this->bitacoraInteresService->calcularInteresPendiente($p->cuotaActiva(), now()->format('Y-m-d'))['interes_pendiente'] ?? 0 : 0;
                }

                // Llenar las celdas
                $sheet->setCellValue('A' . $row, $asesorNombre);
                $sheet->setCellValue('B' . $row, $p->codigo ?? '');
                $sheet->setCellValue('C' . $row, $clienteCodigo);
                $sheet->setCellValue('D' . $row, $clienteNombre);
                $sheet->setCellValue('E' . $row, $clienteNit);
                $sheet->setCellValue('F' . $row, $clienteTelefono);
                $sheet->setCellValue('G' . $row, $clienteGenero);
                $sheet->setCellValue('H' . $row, $p->monto ?? 0);
                $sheet->setCellValue('I' . $row, ($p->interes ?? 0) / 100);
                $sheet->setCellValue('J' . $row, $propiedadInfo);
                $sheet->setCellValue('K' . $row, $p->plazo ?? 0);
                $sheet->setCellValue('L' . $row, $p->nombreDestino ?? ($p->destino ?? ''));
                $sheet->setCellValue('M' . $row, $fechaInicio);
                $sheet->setCellValue('N' . $row, $fechaFin);
                $sheet->setCellValue('O' . $row, method_exists($p, 'saldoPendiente') ? $p->saldoPendiente() : ($p->saldo_pendiente ?? 0));
                $sheet->setCellValue('P' . $row, $capitalCuota);
                $sheet->setCellValue('Q' . $row, $interesAcumulado);
                $sheet->setCellValue('R' . $row, method_exists($p, 'morosidad') ? $p->morosidad() : ($p->dias_atraso ?? 0));
                $sheet->setCellValue('S' . $row, method_exists($p, 'diasDeAtraso') ? $p->diasDeAtraso() : ($p->dias_atraso ?? 0));
                $sheet->setCellValue('T' . $row, $p->cuota ?? 0);
                $row++;
            } catch (\Exception $e) {
                $this->log('Error al procesar préstamo ID ' . ($p->id ?? 'desconocido') . ': ' . $e->getMessage());
                continue;
            }
        }

        // Aplicar formato a los datos
        $dataRange = 'A2:Q' . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Formato de moneda para columnas de dinero
        $moneyColumns = ['G', 'N', 'O', 'R'];
        foreach ($moneyColumns as $col) {
            $sheet->getStyle($col . '2:' . $col . ($row - 1))->getNumberFormat()->setFormatCode('_("Q"* #,##0.00_);_("Q"* \(#,##0.00\);_("Q"* "-"??_);_(@_)');
        }

        // Formato de porcentaje para interés
        $sheet->getStyle('H2:H' . ($row - 1))->getNumberFormat()->setFormatCode('0.00%');

        // Ajustar ancho de columnas
        foreach (range('A', 'T') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Generar archivo para descarga directa
        $fileName = 'prestamos_' . date('Y-m-d_H-i-s') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        // Capturar el contenido del archivo en buffer
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $this->log('Archivo Excel generado para descarga: ' . $fileName);

        // Retornar un array con el contenido y metadatos para que el controlador maneje la respuesta
        return [
            'content' => $content,
            'filename' => $fileName,
            'headers' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $fileName . '"',
                'Cache-Control' => 'max-age=0'
            ]
        ];
    }

    /**
     * Genera un archivo Excel con los datos de múltiples préstamos
     * @param array $prestamos
     * @return array Información del archivo generado (content, filename, headers)
     */
    public function generateExcelMultiple($prestamos)
    {
        return $this->generateExcel($prestamos);
    }

    /**
     * Genera un archivo Excel con todos los préstamos del sistema
     * @return array Información del archivo generado (content, filename, headers)
     */
    public function generateExcelAll()
    {
        return $this->generateExcel();
    }
}
