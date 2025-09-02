<?php

namespace App\Http\Controllers;

use App\Services\ExcelService;
use Illuminate\Http\Request;
use App\Traits\Loggable;

class ExcelUploadController extends Controller
{
    use Loggable;

    public function __construct(
        private ExcelService $excelService
    ) {}

    /**
     * Subir y validar archivo Excel
     */
    public function uploadExcel(Request $request)
    {
        try {
            $this->log("Iniciando carga de archivo Excel");

            // Validar que existe el archivo en el request
            if (!$request->hasFile('excel_file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se ha seleccionado ningún archivo'
                ], 400);
            }

            $file = $request->file('excel_file');

            // Validar archivo Excel con opciones personalizadas
            $options = [
                'max_size' => $request->input('max_size', 10 * 1024 * 1024), // 10MB por defecto
                'validate_content' => $request->boolean('validate_content', true)
            ];

            if ($this->excelService->validateExcelFile($file, $options)) {

                // Obtener información detallada del archivo
                $fileInfo = $this->excelService->getExcelFileInfo($file);



                $this->log("Archivo Excel validado exitosamente: " . $fileInfo['original_name']);

                return response()->json([
                    'success' => true,
                    'message' => 'Archivo Excel válido',
                    'file_info' => $fileInfo
                ]);
            }

        } catch (\Exception $e) {
            $this->logError("Error al procesar archivo Excel: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al validar archivo Excel',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Validar múltiples archivos Excel
     */
    public function uploadMultipleExcel(Request $request)
    {
        try {
            if (!$request->hasFile('excel_files')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se han seleccionado archivos'
                ], 400);
            }

            $files = $request->file('excel_files');
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($files as $index => $file) {
                try {
                    if ($this->excelService->validateExcelFile($file)) {
                        $fileInfo = $this->excelService->getExcelFileInfo($file);
                        $results[] = [
                            'index' => $index,
                            'success' => true,
                            'file_info' => $fileInfo
                        ];
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'index' => $index,
                        'success' => false,
                        'filename' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                    $errorCount++;
                }
            }

            return response()->json([
                'success' => $errorCount === 0,
                'message' => "Procesados {$successCount} archivos exitosamente, {$errorCount} con errores",
                'summary' => [
                    'total' => count($files),
                    'success' => $successCount,
                    'errors' => $errorCount
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar archivos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint solo para validar sin procesar
     */
    public function validateOnly(Request $request)
    {
        try {
            $file = $request->file('excel_file');

            if (!$file) {
                return response()->json(['valid' => false, 'error' => 'No hay archivo']);
            }

            $isValid = $this->excelService->isValidExcelFile($file);

            if ($isValid) {
                $info = $this->excelService->getExcelFileInfo($file);
                return response()->json([
                    'valid' => true,
                    'message' => 'Archivo Excel válido',
                    'info' => $info
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener información de archivo sin validación completa
     */
    public function getFileInfo(Request $request)
    {
        try {
            $file = $request->file('excel_file');

            if (!$file) {
                return response()->json(['error' => 'No hay archivo'], 400);
            }

            // Solo información básica sin validación profunda
            $basicInfo = [
                'original_name' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'size_human' => $this->formatBytes($file->getSize()),
            ];

            return response()->json([
                'success' => true,
                'file_info' => $basicInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatear bytes en formato legible
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
