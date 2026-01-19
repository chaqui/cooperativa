<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarPrestamoRequest;
use App\Http\Requests\EstadoRequest;
use App\Http\Requests\PrestamoRequest;
use App\Http\Resources\Prestamo as PrestamoResource;
use App\Http\Resources\HistoricoEstado as HistoricoEstadoResource;
use App\Http\Resources\Cuota as PagoResource;
use App\Http\Resources\Retiro as RetiroResource;
use App\Http\Resources\Propiedad as PropiedadResource;
use App\Http\Requests\StorePagarCuota;

use App\Services\ArchivoService;
use App\Services\PrestamoService;
use App\Services\PrestamoPdfService;
use App\Services\PrestamoExcelService;
use App\Services\EstadosPrestamoService;
use App\Services\PrestamoArchivoService;
use App\Traits\Loggable;

class PrestamoController extends Controller
{

    private $prestamoService;

    private $prestamoPdfService;

    private $estadosPrestamoService;

    private $prestamoExcelService;

    private $archivoService;

    private $prestamoArchivoService;

    use Loggable;

    public function __construct(
        PrestamoService $prestamoService,
        PrestamoPdfService $prestamoPdfService,
        EstadosPrestamoService $estadosPrestamoService,
        PrestamoExcelService $prestamoExcelService,
        ArchivoService $archivoService,
        PrestamoArchivoService $prestamoArchivoService
    ) {
        $this->prestamoService = $prestamoService;
        $this->prestamoPdfService = $prestamoPdfService;
        $this->estadosPrestamoService = $estadosPrestamoService;
        $this->prestamoExcelService = $prestamoExcelService;
        $this->archivoService = $archivoService;
        $this->prestamoArchivoService = $prestamoArchivoService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PrestamoResource::collection($this->prestamoService->all());
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(PrestamoRequest $request)
    {
         if(!$request->hasFile('file_soporte')){
            return response()->json(['message' => 'El archivo es requerido'], 400);
        }
        $prestamo = $this->prestamoService->create($request);

        return new PrestamoResource($prestamo);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $prestamo = $this->prestamoService->get($id);
        return new PrestamoResource($prestamo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PrestamoRequest $request, string $id)
    {
        return PrestamoResource::collection($this->prestamoService->update($id, $request->all()));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->prestamoService->delete($id);
    }

    public function cambiarEstado(EstadoRequest $request, string $id)
    {
        try {
            $this->estadosPrestamoService->cambiarEstado($id, $request->all());
            return response()->json(['message' => 'Estado cambiado correctamente'], 200);
        } catch (\Exception $e) {
            // Verificar si es una excepción de negocio basada en el mensaje
            $mensaje = $e->getMessage();

            // Si el mensaje contiene códigos de error del sistema, es una excepción de negocio
            if (preg_match('/\[RET-\d{8}-\d{4}-\w+\]/', $mensaje)) {
                // Extraer solo el mensaje sin el código
                $mensajeLimpio = preg_replace('/\[RET-\d{8}-\d{4}-\w+\]\s*/', '', $mensaje);

                return response()->json([
                    'error' => 'Error de validación',
                    'message' => $mensajeLimpio
                ], 400);
            }

            // Para otros errores, mantener el comportamiento original
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $mensaje
            ], 500);
        }
    }

    public function prestamosByEstado(string $estado)
    {
        return PrestamoResource::collection($this->estadosPrestamoService->getPrestamosByEstado($estado));
    }

    public function historial(string $id)
    {
        return HistoricoEstadoResource::collection($this->estadosPrestamoService->getHistorial($id));
    }

    public function generatePdf(string $id)
    {
        $pdf = $this->prestamoPdfService->generatePdf($id);
        return response($pdf, 200)->header('Content-Type', 'application/pdf');
    }

    public function pagos(string $id)
    {
        return PagoResource::collection($this->prestamoService->getPagos($id));
    }

    public function getRetirosPendientes()
    {
        return RetiroResource::collection($this->prestamoService->getRetirosPendientes());
    }

    public function generarEstadoCuenta(string $id)
    {
        $orientation = request()->query('orientation', 'landscape');
        $pdf = $this->prestamoPdfService->generarEstadoCuentaPdf($id, false, $orientation);
        return response($pdf, 200)->header('Content-Type', 'application/pdf');
    }

    public function generarEstadoCuentaDepositos(string $id)
    {
        $pdf = $this->prestamoPdfService->generarPdfDepositos($id);
        return response($pdf, 200)->header('Content-Type', 'application/pdf');
    }

    public function getEstadoCuenta(string $id)
    {
        $prestamo = $this->prestamoService->get($id);
        if (!$prestamo->estado_cuenta_path) {
            $this->log('No se ha generado el estado de cuenta para el préstamo: ' . $id);
            return response()->json(['message' => 'No se ha generado el estado de cuenta'], 404);
        }
        try {
            return response()->download($prestamo->estado_cuenta_path);
        } catch (\Exception $e) {
            $pdf = $this->prestamoPdfService->generarEstadoCuentaPdf($id, true);
            $path = storage_path(path: 'app/estados_cuenta/');

            $fileName = 'estado_cuenta_prestamo_' . $prestamo->id . '.pdf';
            $pathArchivo = $this->archivoService->guardarArchivo($pdf, $path, $fileName);
            $prestamo->estado_cuenta_path = $pathArchivo;
            $prestamo->save();
            return response($pdf, 200)->header('Content-Type', 'application/pdf');
        }
    }

    public function pagarCuota(StorePagarCuota $request, string $id)
    {
        $id = $this->prestamoService->pagarCuota($id, $request->all());
        return response()->json(['message' => 'Pago realizado correctamente', 'id_deposito' => $id], 200);
    }

    /**
     * Cancela un préstamo hipotecario
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelar(\Illuminate\Http\Request $request, string $id)
    {
        $this->log("Solicitud de cancelación para préstamo ID: {$id}");
        try {
            // Validar los datos de entrada
            $request->validate([
                'motivo' => 'required|string|max:500|min:10'
            ], [
                'motivo.required' => 'El motivo de cancelación es requerido',
                'motivo.string' => 'El motivo debe ser una cadena de texto',
                'motivo.max' => 'El motivo no puede exceder 500 caracteres',
                'motivo.min' => 'El motivo debe tener al menos 10 caracteres'
            ]);

            $this->log("Solicitud de cancelación para préstamo ID: {$id}");

            // Cancelar el préstamo
            $prestamoCancelado = $this->prestamoService->cancelarPrestamo($id, $request->motivo, $request->tipo);

            $this->log("Préstamo {$prestamoCancelado->codigo} cancelado exitosamente");

            return response()->json([
                'message' => 'Préstamo cancelado exitosamente',
                'data' => [
                    'id' => $prestamoCancelado->id,
                    'codigo' => $prestamoCancelado->codigo,
                    'motivo_cancelacion' => $prestamoCancelado->motivo_cancelacion,
                    'fecha_cancelacion' => $prestamoCancelado->getFechaCancelacionFormateada(),
                    'estado_cancelado' => $prestamoCancelado->estaCancelado()
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->log("Error de validación al cancelar préstamo: " . json_encode($e->errors()));
            return response()->json([
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            $this->log("Error al cancelar préstamo ID {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al cancelar el préstamo: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Genera y descarga un Excel con un préstamo específico
     */
    public function downloadExcelPrestamo()
    {
        try {

            $excelData = $this->prestamoExcelService->generateExcel();

            return response($excelData['content'])
                ->withHeaders($excelData['headers']);
        } catch (\Exception $e) {
            $this->log('Error al generar el archivo Excel: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al generar el archivo Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca préstamos con filtros múltiples
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function buscar(\Illuminate\Http\Request $request)
    {
        try {
            $this->log("Iniciando búsqueda de préstamos con filtros: " . json_encode($request->all()));

            // Obtener filtros de la request
            $filtros = [];

            if ($request->filled('dpi_cliente')) {
                $filtros['dpi_cliente'] = $request->dpi_cliente;
            }

            if ($request->filled('id')) {
                $filtros['id'] = $request->id;
            }

            if ($request->filled('codigo')) {
                $filtros['codigo'] = $request->codigo;
            }

            if ($request->filled('nombre_cliente')) {
                $filtros['nombre_cliente'] = $request->nombre_cliente;
            }

            if ($request->filled('estado_id')) {
                $filtros['estado_id'] = $request->estado_id;
            }

            if ($request->filled('fecha_inicio_desde')) {
                $filtros['fecha_inicio_desde'] = $request->fecha_inicio_desde;
            }

            if ($request->filled('fecha_inicio_hasta')) {
                $filtros['fecha_inicio_hasta'] = $request->fecha_inicio_hasta;
            }

            if ($request->filled('monto_minimo')) {
                $filtros['monto_minimo'] = $request->monto_minimo;
            }

            if ($request->filled('monto_maximo')) {
                $filtros['monto_maximo'] = $request->monto_maximo;
            }

            if ($request->filled('id_usuario')) {
                $filtros['id_usuario'] = $request->id_usuario;
            }

            if ($request->filled('cancelado')) {
                $filtros['cancelado'] = $request->cancelado;
            }

            if ($request->filled('orden_por')) {
                $filtros['orden_por'] = $request->orden_por;
            }

            if ($request->filled('direccion')) {
                $filtros['direccion'] = $request->direccion;
            }

            if ($request->filled('limite')) {
                $filtros['limite'] = $request->limite;
            }

            // Realizar búsqueda
            $prestamos = $this->prestamoService->buscarPrestamos($filtros);

            $this->log("Búsqueda completada. Encontrados: " . $prestamos->count() . " préstamos");

            return response()->json([
                'message' => 'Búsqueda completada exitosamente',
                'data' => PrestamoResource::collection($prestamos),
                'meta' => [
                    'total_encontrados' => $prestamos->count(),
                    'filtros_aplicados' => $filtros
                ]
            ], 200);
        } catch (\Exception $e) {
            $this->log("Error en búsqueda de préstamos: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al buscar préstamos: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Busca préstamos con paginación
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function buscarPaginado(\Illuminate\Http\Request $request)
    {
        try {
            // Validar parámetros de paginación
            $request->validate([
                'pagina' => 'nullable|integer|min:1',
                'por_pagina' => 'nullable|integer|min:1|max:100',
                'dpi_cliente' => 'nullable|string|max:20',
                'id' => 'nullable|integer|min:1',
                'codigo' => 'nullable|string|max:50',
                'nombre_cliente' => 'nullable|string|max:100',
                'estado_id' => 'nullable|integer|min:1',
                'fecha_inicio_desde' => 'nullable|date',
                'fecha_inicio_hasta' => 'nullable|date|after_or_equal:fecha_inicio_desde',
                'monto_minimo' => 'nullable|numeric|min:0',
                'monto_maximo' => 'nullable|numeric|min:0',
                'id_usuario' => 'nullable|integer|min:1',
                'cancelado' => 'nullable|boolean',
                'orden_por' => 'nullable|string|in:id,codigo,monto,fecha_inicio,created_at',
                'direccion' => 'nullable|string|in:asc,desc'
            ]);

            $this->log("Iniciando búsqueda paginada de préstamos");

            // Obtener parámetros de paginación
            $pagina = $request->get('pagina', 1);
            $porPagina = $request->get('por_pagina', 15);

            // Obtener filtros
            $filtros = $request->only([
                'dpi_cliente',
                'id',
                'codigo',
                'nombre_cliente',
                'estado_id',
                'fecha_inicio_desde',
                'fecha_inicio_hasta',
                'monto_minimo',
                'monto_maximo',
                'id_usuario',
                'cancelado',
                'orden_por',
                'direccion'
            ]);

            // Realizar búsqueda paginada
            $resultados = $this->prestamoService->buscarPrestamosPaginado($filtros, $pagina, $porPagina);

            $this->log("Búsqueda paginada completada. Página: {$pagina}, Total: " . $resultados->total());

            return response()->json([
                'message' => 'Búsqueda paginada completada exitosamente',
                'data' => PrestamoResource::collection($resultados->items()),
                'meta' => [
                    'current_page' => $resultados->currentPage(),
                    'last_page' => $resultados->lastPage(),
                    'per_page' => $resultados->perPage(),
                    'total' => $resultados->total(),
                    'from' => $resultados->firstItem(),
                    'to' => $resultados->lastItem(),
                    'filtros_aplicados' => array_filter($filtros)
                ],
                'links' => [
                    'first' => $resultados->url(1),
                    'last' => $resultados->url($resultados->lastPage()),
                    'prev' => $resultados->previousPageUrl(),
                    'next' => $resultados->nextPageUrl()
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->log("Error de validación en búsqueda paginada: " . json_encode($e->errors()));
            return response()->json([
                'message' => 'Parámetros de búsqueda inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            $this->log("Error en búsqueda paginada de préstamos: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al buscar préstamos: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function actualizarPrestamo(ActualizarPrestamoRequest $request, string $id)
    {
        try {
            $prestamo = $this->prestamoService->actualizarPrestamo($request, $id);
            return new PrestamoResource($prestamo);
        } catch (\Exception $e) {
            $this->log("Error al actualizar el préstamo ID {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar el préstamo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPropiedad($id)
    {
        try {
            $propiedad = $this->prestamoService->propiedadAsociada($id);
            return new PropiedadResource($propiedad);
        } catch (\Exception $e) {
            $this->log("Error al obtener la propiedad asociada al préstamo ID {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener la propiedad: ' . $e->getMessage()
            ], 500);
        }
    }
}
