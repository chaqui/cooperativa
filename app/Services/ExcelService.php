<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use App\Traits\ErrorHandler;
use App\Traits\Loggable;

class ExcelService
{
    use ErrorHandler, Loggable;

    /**
     * Extensiones de archivo Excel permitidas
     */
    private const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    /**
     * MIME types válidos para archivos Excel
     */
    private const ALLOWED_MIME_TYPES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv', // .csv
        'text/plain', // .csv (algunos navegadores)
        'application/csv', // .csv (algunos sistemas)
    ];

    /**
     * Tamaño máximo de archivo en bytes (10MB por defecto)
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * Valida que un archivo sea un Excel válido
     *
     * @param UploadedFile $file El archivo a validar
     * @param array $options Opciones adicionales de validación
     * @return bool True si es válido, false si no
     * @throws \Exception Si hay errores en la validación
     */
    public function validateExcelFile(UploadedFile $file, array $options = []): bool
    {
        try {
            $this->log("Iniciando validación de archivo Excel: " . $file->getClientOriginalName());

            // Validaciones básicas
            $this->validateBasicFile($file);

            // Validar extensión
            $this->validateExtension($file);

            // Validar MIME type
            $this->validateMimeType($file);

            // Validar tamaño
            $this->validateFileSize($file, $options['max_size'] ?? self::MAX_FILE_SIZE);

            // Validar contenido si es necesario
            if ($options['validate_content'] ?? true) {
                $this->validateExcelContent($file);
            }

            $this->log("Archivo Excel válido: " . $file->getClientOriginalName());
            return true;

        } catch (\Exception $e) {
            $this->logError("Error al validar archivo Excel: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validaciones básicas del archivo
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validateBasicFile(UploadedFile $file): void
    {
        if (!$file) {
            $this->lanzarExcepcionConCodigo("No se ha proporcionado ningún archivo");
        }

        if (!$file->isValid()) {
            $this->lanzarExcepcionConCodigo("El archivo no es válido o hubo un error en la carga");
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            $this->lanzarExcepcionConCodigo("Error en la carga del archivo: " . $this->getUploadErrorMessage($file->getError()));
        }
    }

    /**
     * Valida la extensión del archivo
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validateExtension(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (empty($extension)) {
            $this->lanzarExcepcionConCodigo("El archivo no tiene extensión");
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            $this->lanzarExcepcionConCodigo(
                "Extensión de archivo no permitida: .{$extension}. " .
                "Extensiones permitidas: " . implode(', ', array_map(fn($ext) => ".{$ext}", self::ALLOWED_EXTENSIONS))
            );
        }

        $this->log("Extensión válida: .{$extension}");
    }

    /**
     * Valida el MIME type del archivo
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validateMimeType(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            $this->lanzarExcepcionConCodigo(
                "Tipo de archivo no permitido: {$mimeType}. " .
                "Este archivo no parece ser un Excel válido."
            );
        }

        $this->log("MIME type válido: {$mimeType}");
    }

    /**
     * Valida el tamaño del archivo
     *
     * @param UploadedFile $file
     * @param int $maxSize Tamaño máximo en bytes
     * @throws \Exception
     */
    private function validateFileSize(UploadedFile $file, int $maxSize): void
    {
        $fileSize = $file->getSize();

        if ($fileSize > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            $fileSizeMB = round($fileSize / (1024 * 1024), 2);

            $this->lanzarExcepcionConCodigo(
                "El archivo es demasiado grande: {$fileSizeMB}MB. " .
                "Tamaño máximo permitido: {$maxSizeMB}MB"
            );
        }

        $this->log("Tamaño de archivo válido: " . round($fileSize / 1024, 2) . "KB");
    }

    /**
     * Valida el contenido del archivo Excel
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validateExcelContent(UploadedFile $file): void
    {
        try {
            $path = $file->getRealPath();

            // Verificar que el archivo existe y es legible
            if (!file_exists($path) || !is_readable($path)) {
                $this->lanzarExcepcionConCodigo("No se puede leer el archivo");
            }

            // Validar según la extensión
            $extension = strtolower($file->getClientOriginalExtension());

            switch ($extension) {
                case 'xlsx':
                    $this->validateXlsxContent($path);
                    break;
                case 'xls':
                    $this->validateXlsContent($path);
                    break;
                case 'csv':
                    $this->validateCsvContent($path);
                    break;
                default:
                    $this->lanzarExcepcionConCodigo("Extensión no soportada para validación de contenido: {$extension}");
            }

            $this->log("Contenido del archivo validado correctamente");

        } catch (\Exception $e) {
            $this->lanzarExcepcionConCodigo("Error al validar el contenido del archivo: " . $e->getMessage());
        }
    }

    /**
     * Valida contenido de archivo XLSX
     *
     * @param string $path
     * @throws \Exception
     */
    private function validateXlsxContent(string $path): void
    {
        // Verificar que el archivo es un ZIP válido (XLSX es un archivo ZIP)
        $zip = new \ZipArchive();
        $result = $zip->open($path, \ZipArchive::CHECKCONS);

        if ($result !== TRUE) {
            $this->lanzarExcepcionConCodigo("El archivo XLSX está corrupto o no es válido");
        }

        // Verificar que contiene los archivos básicos de un Excel
        $requiredFiles = ['xl/workbook.xml', '[Content_Types].xml'];
        foreach ($requiredFiles as $requiredFile) {
            if ($zip->locateName($requiredFile) === false) {
                $zip->close();
                $this->lanzarExcepcionConCodigo("El archivo no contiene la estructura válida de un Excel");
            }
        }

        $zip->close();
    }

    /**
     * Valida contenido de archivo XLS
     *
     * @param string $path
     * @throws \Exception
     */
    private function validateXlsContent(string $path): void
    {
        // Leer los primeros bytes para verificar la signatura del archivo XLS
        $handle = fopen($path, 'rb');
        if (!$handle) {
            $this->lanzarExcepcionConCodigo("No se puede abrir el archivo XLS para validación");
        }

        $signature = fread($handle, 8);
        fclose($handle);

        // Signaturas conocidas para archivos XLS
        $validSignatures = [
            "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", // OLE2 File
            "\x09\x08", // BIFF5/BIFF8
        ];

        $isValid = false;
        foreach ($validSignatures as $validSignature) {
            if (strpos($signature, $validSignature) === 0) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $this->lanzarExcepcionConCodigo("El archivo XLS no tiene una signatura válida");
        }
    }

    /**
     * Valida contenido de archivo CSV
     *
     * @param string $path
     * @throws \Exception
     */
    private function validateCsvContent(string $path): void
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->lanzarExcepcionConCodigo("No se puede abrir el archivo CSV para validación");
        }

        // Intentar leer la primera línea
        $firstLine = fgets($handle);
        fclose($handle);

        if ($firstLine === false) {
            $this->lanzarExcepcionConCodigo("El archivo CSV está vacío o corrupto");
        }

        // Verificar que contiene caracteres válidos para CSV
        if (!mb_check_encoding($firstLine, 'UTF-8') && !mb_check_encoding($firstLine, 'ISO-8859-1')) {
            $this->lanzarExcepcionConCodigo("El archivo CSV contiene caracteres no válidos");
        }
    }

    /**
     * Obtiene el mensaje de error para códigos de error de upload
     *
     * @param int $errorCode
     * @return string
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return "El archivo excede el tamaño máximo permitido por PHP";
            case UPLOAD_ERR_FORM_SIZE:
                return "El archivo excede el tamaño máximo especificado en el formulario";
            case UPLOAD_ERR_PARTIAL:
                return "El archivo se subió parcialmente";
            case UPLOAD_ERR_NO_FILE:
                return "No se subió ningún archivo";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Falta el directorio temporal";
            case UPLOAD_ERR_CANT_WRITE:
                return "Error al escribir el archivo al disco";
            case UPLOAD_ERR_EXTENSION:
                return "Una extensión de PHP detuvo la subida del archivo";
            default:
                return "Error desconocido en la subida del archivo";
        }
    }

    /**
     * Función de conveniencia para validar archivos Excel con configuración básica
     *
     * @param UploadedFile $file
     * @return bool
     * @throws \Exception
     */
    public function isValidExcelFile(UploadedFile $file): bool
    {
        return $this->validateExcelFile($file);
    }

    /**
     * Obtiene información del archivo Excel validado
     *
     * @param UploadedFile $file
     * @return array
     */
    public function getExcelFileInfo(UploadedFile $file): array
    {
        if (!$this->validateExcelFile($file)) {
            return [];
        }

        return [
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'size_human' => $this->formatBytes($file->getSize()),
            'is_valid' => true,
        ];
    }

    /**
     * Formatea bytes en formato legible
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

