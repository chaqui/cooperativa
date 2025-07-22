<?php

namespace App\Http\Controllers;

use App\Services\PrestamoExcelService;
use App\Models\Prestamo_Hipotecario;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExcelController extends Controller
{
    protected $prestamoExcelService;

    public function __construct(PrestamoExcelService $prestamoExcelService)
    {
        $this->prestamoExcelService = $prestamoExcelService;
    }

    /**
     * Genera y descarga un Excel con un préstamo específico
     */
    public function downloadPrestamo($id)
    {
        try {
            $prestamo = Prestamo_Hipotecario::with([
                'asesor', 'cliente', 'propiedad', 'estado'
            ])->findOrFail($id);

            $excelData = $this->prestamoExcelService->generateExcel([$prestamo]);

            return response($excelData['content'])
                ->withHeaders($excelData['headers']);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el archivo Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera y descarga un Excel con múltiples préstamos
     */
    public function downloadMultiple(Request $request)
    {
        try {
            $ids = $request->input('prestamo_ids', []);

            if (empty($ids)) {
                return response()->json([
                    'error' => 'No se proporcionaron IDs de préstamos'
                ], 400);
            }

            $prestamos = Prestamo_Hipotecario::with([
                'asesor', 'cliente', 'propiedad', 'estado'
            ])->whereIn('id', $ids)->get();

            $excelData = $this->prestamoExcelService->generateExcel($prestamos);

            return response($excelData['content'])
                ->withHeaders($excelData['headers']);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el archivo Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera y descarga un Excel con todos los préstamos
     */
    public function downloadAll()
    {
        try {
            $excelData = $this->prestamoExcelService->generateExcelAll();

            return response($excelData['content'])
                ->withHeaders($excelData['headers']);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el archivo Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera y descarga un Excel con préstamos filtrados
     */
    public function downloadFiltered(Request $request)
    {
        try {
            $query = Prestamo_Hipotecario::with([
                'asesor', 'cliente', 'propiedad', 'estado'
            ]);

            // Aplicar filtros según los parámetros de la request
            if ($request->has('estado')) {
                $query->where('estado_id', $request->input('estado'));
            }

            if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                $query->whereBetween('fecha_inicio', [
                    $request->input('fecha_inicio'),
                    $request->input('fecha_fin')
                ]);
            }

            if ($request->has('cliente_id')) {
                $query->where('dpi_cliente', $request->input('cliente_id'));
            }

            $prestamos = $query->get();

            $excelData = $this->prestamoExcelService->generateExcel($prestamos);

            return response($excelData['content'])
                ->withHeaders($excelData['headers']);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el archivo Excel: ' . $e->getMessage()
            ], 500);
        }
    }
}
