<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelTemplateService
{
    /**
     * Genera plantilla de Excel para depósitos
     */
    public function generateDepositTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar título de la hoja
        $sheet->setTitle('Plantilla Depósitos');

        // Definir las columnas
        $headers = [
            'A1' => 'Fecha de Depósito',
            'B1' => 'Monto',
            'C1' => 'Tipo de Documento',
            'D1' => 'Número de Documento'
        ];

        // Establecer encabezados
        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        // Aplicar estilos a los encabezados
        $headerRange = 'A1:D1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(20); // Fecha
        $sheet->getColumnDimension('B')->setWidth(15); // Monto
        $sheet->getColumnDimension('C')->setWidth(20); // Tipo documento
        $sheet->getColumnDimension('D')->setWidth(25); // Número documento

        // Agregar filas de ejemplo con formato
        $exampleData = [
            ['02/02/2024', '1500.00', 'CHEQUE', '1234567890101'],
            ['03/03/2024', '2000.50', 'DEPOSITO', 'A12345678'],
            ['04/04/2024', '750.25', 'TRANSFERENCIA', 'LIC123456789']
        ];

        $row = 2;
        foreach ($exampleData as $data) {
            $sheet->fromArray($data, null, "A{$row}");

            // Aplicar formato a las filas de ejemplo
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA']
                ]
            ]);

            // Formato específico para fecha
            $sheet->getStyle("A{$row}")->getNumberFormat()
                ->setFormatCode('yyyy-mm-dd');

            // Formato específico para monto
            $sheet->getStyle("B{$row}")->getNumberFormat()
                ->setFormatCode('#,##0.00');

            $row++;
        }

        // Agregar validaciones de datos
        $this->addDataValidations($sheet);

        // Agregar instrucciones en una hoja separada
        $this->addInstructionsSheet($spreadsheet);

        // Guardar archivo temporal
        $fileName = 'plantilla_depositos_' . date('Y-m-d_H-i-s') . '.xlsx';
        $tempPath = storage_path('app/temp/' . $fileName);

        // Crear directorio si no existe
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Agregar validaciones a las celdas
     */
    private function addDataValidations($sheet)
    {
        // Validación para fecha (columna A)
        $validation = $sheet->getCell('A2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DATE);
        $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_BETWEEN);
        $validation->setFormula1('2020-01-01');
        $validation->setFormula2('2030-12-31');
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Fecha inválida');
        $validation->setError('Por favor ingrese una fecha válida entre 2020-01-01 y 2030-12-31');

        // Validación para monto (columna B)
        $validation = $sheet->getCell('B2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DECIMAL);
        $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_GREATERTHAN);
        $validation->setFormula1('0');
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Monto inválido');
        $validation->setError('El monto debe ser mayor a 0');

        // Validación para tipo de documento (columna C)
        $validation = $sheet->getCell('C2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setFormula1('"DPI,PASAPORTE,LICENCIA,CEDULA"');
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Tipo de documento inválido');
        $validation->setError('Seleccione un tipo de documento válido');
    }

    /**
     * Agregar hoja de instrucciones
     */
    private function addInstructionsSheet($spreadsheet)
    {
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instrucciones');

        $instructions = [
            'INSTRUCCIONES PARA USO DE LA PLANTILLA',
            '',
            '1. FECHA DE DEPÓSITO:',
            '   - Formato: YYYY-MM-DD (Ejemplo: 2024-08-27)',
            '   - Rango válido: 2020-01-01 a 2030-12-31',
            '',
            '2. MONTO:',
            '   - Solo números decimales',
            '   - Debe ser mayor a 0',
            '   - Ejemplo: 1500.50',
            '',
            '3. TIPO DE DOCUMENTO:',
            '   - Opciones válidas: DPI, PASAPORTE, LICENCIA, CEDULA',
            '   - Usar exactamente una de estas opciones',
            '',
            '4. NÚMERO DE DOCUMENTO:',
            '   - Texto alfanumérico',
            '   - Sin espacios ni caracteres especiales',
            '',
            'NOTAS IMPORTANTES:',
            '- No modificar los encabezados de las columnas',
            '- Eliminar las filas de ejemplo antes de agregar datos reales',
            '- Guardar el archivo en formato Excel (.xlsx)',
            '- Verificar que todos los campos estén completos antes de subir'
        ];

        $row = 1;
        foreach ($instructions as $instruction) {
            $instructionsSheet->setCellValue("A{$row}", $instruction);

            if ($row === 1) {
                // Título principal
                $instructionsSheet->getStyle("A{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => '2F5496']
                    ]
                ]);
            } elseif (strpos($instruction, ':') !== false && strlen($instruction) < 30) {
                // Subtítulos
                $instructionsSheet->getStyle("A{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '4472C4']
                    ]
                ]);
            }

            $row++;
        }

        $instructionsSheet->getColumnDimension('A')->setWidth(60);
    }
}
