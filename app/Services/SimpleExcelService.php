<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class SimpleExcelService
{
    /**
     * Valida que un archivo sea Excel válido
     */
    public function validateExcelFile(UploadedFile $file, int $maxSize = 5242880): array
    {
        $errors = [];

        // Validar extensión
        $allowedExtensions = ['xlsx', 'xls', 'csv'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Extensión no válida. Solo se permiten: ' . implode(', ', $allowedExtensions);
        }

        // Validar tamaño
        if ($file->getSize() > $maxSize) {
            $errors[] = 'El archivo excede el tamaño máximo de ' . ($maxSize / 1024 / 1024) . 'MB';
        }

        // Validar que no esté vacío
        if ($file->getSize() == 0) {
            $errors[] = 'El archivo está vacío';
        }

        // Validar MIME type
        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv'
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Tipo de archivo no válido';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'file_info' => [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'extension' => $extension,
                'mime_type' => $file->getMimeType()
            ]
        ];
    }

    /**
     * Verifica si es un archivo Excel válido (función simple)
     */
    public function isValidExcel(UploadedFile $file): bool
    {
        $result = $this->validateExcelFile($file);
        return $result['is_valid'];
    }
}
