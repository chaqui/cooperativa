<?php

namespace App\Services;


use App\Traits\ErrorHandler;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class DepositoExcelService extends DepositoService
{
    use ErrorHandler;
    public function __construct(
        PdfService $pdfService,
        ImpuestoTransaccionService $impuestoTransaccionService,
        BitacoraInteresService $bitacoraInteresService
    ) {
        parent::__construct($pdfService, $impuestoTransaccionService, $bitacoraInteresService);
    }

    public function generarExcelDepositosPorFecha($fecha)
    {
        $depositos = $this->obtenerDepositosPorFecha($fecha);
        if ($depositos->isEmpty()) {
            $this->lanzarExcepcionConCodigo("No se encontraron depósitos para la fecha proporcionada: {$fecha}");
        }
        return $this->generateExcel($depositos);
    }
    private function generateExcel($depositos)

    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar encabezados de columna
        $headers = ['ID', 'Monto', 'Fecha', 'Numero de Documento', 'Motivo'];
        $columnIndex = 1;
        foreach ($headers as $header) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue($columnLetter . '1', $header);
            $columnIndex++;
        }

        // Rellenar datos de los depósitos
        $rowIndex = 2;
        foreach ($depositos as $deposito) {
            $sheet->setCellValue('A' . $rowIndex, $deposito->id);
            $sheet->setCellValue('B' . $rowIndex, $deposito->monto);
            $sheet->setCellValue('C' . $rowIndex, $deposito->fecha);
            $sheet->setCellValue('D' . $rowIndex, $deposito->numero_documento);
            $sheet->setCellValue('E' . $rowIndex, $deposito->motivo);
            $rowIndex++;
        }

        // Generar el archivo Excel en memoria
        $fileName = 'depositos_' . date('Y-m-d_H-i-s') . '.xlsx';

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
}
