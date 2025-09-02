<?php

namespace App\Http\Controllers;

use App\Services\ExcelTemplateService;
use Illuminate\Http\Request;

class ExcelTemplateController extends Controller
{
    public function __construct(
        private ExcelTemplateService $excelTemplateService
    ) {}

    /**
     * Descargar plantilla de Excel para depósitos
     */
    public function downloadDepositTemplate()
    {
        try {
            $filePath = $this->excelTemplateService->generateDepositTemplate();

            $fileName = 'Plantilla_Depositos_' . date('Y-m-d') . '.xlsx';

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar la plantilla: ' . $e->getMessage()
            ], 500);
        }
    }
}
