<?php

namespace App\Services;

use App\Models\ClientView;
use App\Traits\ErrorHandler;
use App\Traits\Loggable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;


class ClientExcelService
{
    use ErrorHandler;

    use Loggable;


    public function obtenerClientesExcel()
    {
        $clients = ClientView::all();
        return $this->generarExcel($clients);
    }

    private function generarExcel($clients)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Definir encabezados
        $headers = [
            'Código Cliente',
            'DPI Cliente',
            'Nombre Completo',
            'Teléfono',
            'Correo',
            'Dirección',
            'Género',
            'Fecha de Nacimiento'
        ];
        // Agregar encabezados a la primera fila
        $sheet->fromArray($headers, null, 'A1');
        // Agregar datos de clientes
        $row = 2; // Comenzar desde la segunda fila
        foreach ($clients as $client) {
            // Establecer DPI como texto explícitamente para evitar formato numérico
            $sheet->setCellValueExplicit('B' . $row, $client->client_dpi, DataType::TYPE_STRING);

            // Convertir código de género a texto
            $generoTexto = match ((int) $client->genero) {
                15 => 'Masculino',
                16 => 'Femenino',
                default => $client->genero
            };

            $data = [
                $client->codigo_cliente,
                null, // DPI ya establecido arriba como texto
                $client->nombre_completo,
                $client->telefono,
                $client->correo,
                $client->direccion,
                $generoTexto,
                $client->fecha_nacimiento
            ];
            $sheet->fromArray($data, null, 'A' . $row);
            // Volver a establecer DPI como texto (fromArray puede sobrescribir)
            $sheet->setCellValueExplicit('B' . $row, $client->client_dpi, DataType::TYPE_STRING);
            $row++;
        }

        $dataRange = 'A2:H' . ($row - 1);
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
         // Ajustar ancho de columnas
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Generar archivo para descarga directa
        $fileName = 'clientes_' . date('Ymd_His') . '.xlsx';

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
