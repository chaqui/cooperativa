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
        BitacoraInteresService $bitacoraInteresService
    ) {
        parent::__construct($controladorEstado, $clientService, $propiedadService, $catalogoService, $userService, $cuotaHipotecaService, $prestamoExistenteService);
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
            'E1' => 'Teléfono',
            'F1' => 'GENERO',
            'G1' => 'MONTO ORIGINAL',
            'H1' => 'INTERES MENSUAL',
            'I1' => 'GARANTIA',
            'J1' => 'PLAZO (MESESx)',
            'K1' => 'DESTINO',
            'L1' => 'FECHA DE DESEMBOLSO',
            'M1' => 'FECHA DE FINALIZACION',
            'N1' => 'SALDO CAPITAL ACTUAL',
            'O1' => 'INTERES A LA FECHA',
            'P1' => 'ESTATUS',
            'Q1' => 'Días de atraso',
            'R1' => 'CUOTA TOTAL'
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
                if (isset($p->cliente) && $p->cliente) {
                    $nombres = $p->cliente->nombres ?? '';
                    $apellidos = $p->cliente->apellidos ?? '';
                    $clienteNombre = trim($nombres . ' ' . $apellidos);
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
                    $propiedadInfo = $p->propiedadAsociada->Descripcion ?? '';
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

                $p->nombreDestino = $this->catalogoService->getCatalogo($p->destino)['value'] ?? 'No especificado';
                $interesAcumulado = $p->cuotaActiva() ? $this->bitacoraInteresService->calcularInteresPendiente($p->cuotaActiva(), fechaPago: now()->format('Y-m-d'))['interes_pendiente'] ?? 0 : 0;
                // Llenar las celdas
                $sheet->setCellValue('A' . $row, $asesorNombre);
                $sheet->setCellValue('B' . $row, $p->codigo ?? '');
                $sheet->setCellValue('C' . $row, $clienteCodigo);
                $sheet->setCellValue('D' . $row, $clienteNombre);
                $sheet->setCellValue('E' . $row, $clienteTelefono);
                $sheet->setCellValue('F' . $row, $clienteGenero);
                $sheet->setCellValue('G' . $row, $p->monto ?? 0);
                $sheet->setCellValue('H' . $row, ($p->interes ?? 0) / 100);
                $sheet->setCellValue('I' . $row, $propiedadInfo);
                $sheet->setCellValue('J' . $row, $p->plazo ?? 0);
                $sheet->setCellValue('K' . $row, $p->nombreDestino ?? ($p->destino ?? ''));
                $sheet->setCellValue('L' . $row, $fechaInicio);
                $sheet->setCellValue('M' . $row, $fechaFin);
                $sheet->setCellValue('N' . $row, method_exists($p, 'saldoPendiente') ? $p->saldoPendiente() : ($p->saldo_pendiente ?? 0));
                $sheet->setCellValue('O' . $row, $interesAcumulado);
                $sheet->setCellValue('P' . $row, method_exists($p, 'morosidad') ? $p->morosidad() : ($p->dias_atraso ?? 0));
                $sheet->setCellValue('Q' . $row, method_exists($p, 'diasDeAtraso') ? $p->diasDeAtraso() : ($p->dias_atraso ?? 0));
                $sheet->setCellValue('R' . $row, $p->cuota ?? 0);
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
        foreach (range('A', 'Q') as $col) {
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
