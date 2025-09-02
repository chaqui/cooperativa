<?php

namespace App\Http\Controllers;

use App\Services\SimpleExcelService;
use Illuminate\Http\Request;

class SimpleExcelController extends Controller
{
    public function __construct(
        private SimpleExcelService $excelService
    ) {}

    /**
     * Validar archivo Excel
     */
    public function validateExcel(Request $request)
    {
        if (!$request->hasFile('excel_file')) {
            return response()->json([
                'success' => false,
                'message' => 'No se seleccionó archivo'
            ], 400);
        }

        $file = $request->file('excel_file');
        $result = $this->excelService->validateExcelFile($file);

        return response()->json([
            'success' => $result['is_valid'],
            'message' => $result['is_valid'] ? 'Archivo válido' : 'Archivo inválido',
            'errors' => $result['errors'],
            'file_info' => $result['file_info']
        ]);
    }

    /**
     * Subir archivo Excel
     */
    public function uploadExcel(Request $request)
    {
        if (!$request->hasFile('excel_file')) {
            return response()->json([
                'success' => false,
                'message' => 'No se seleccionó archivo'
            ], 400);
        }

        $file = $request->file('excel_file');

        // Validar primero
        if (!$this->excelService->isValidExcel($file)) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo Excel no válido'
            ], 400);
        }

        // Guardar archivo
        $path = $file->store('excel_files', 'public');

        return response()->json([
            'success' => true,
            'message' => 'Archivo subido correctamente',
            'path' => $path,
            'file_info' => [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ]
        ]);
    }
}
