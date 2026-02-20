<?php

namespace App\Services;

use App\Constants\EstadoInversion;
use App\EstadosInversion\ControladorEstado;

use App\Traits\Loggable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Constants\InicialesCodigo;

use App\Models\Inversion;
use App\Traits\ErrorHandler;

class InversionService extends CodigoService
{
    use ErrorHandler;
    use Loggable;
    private  CuotaInversionService $cuotaInversionService;

    private ControladorEstado $controladorEstado;

    private TipoCuentaInternaService $tipoCuentaInternaService;

    private PdfService $pdfService;

    private ArchivoService $archivoService;

    private PagoInversionExcelService $pagoInversionExcelService;

    public function __construct(
        CuotaInversionService $cuotaInversionService,
        ControladorEstado $controladorEstado,
        PdfService $pdfService,
        TipoCuentaInternaService $tipoCuentaInternaService,
        ArchivoService $archivoService
    ) {
        $this->cuotaInversionService = $cuotaInversionService;
        $this->controladorEstado = $controladorEstado;
        $this->pdfService = $pdfService;
        $this->tipoCuentaInternaService = $tipoCuentaInternaService;
        $this->archivoService = $archivoService;
        parent::__construct(InicialesCodigo::$Inversion);
    }

    public function getInversion(string $id): Inversion
    {
        // Validar que el ID sea un valor válido
        if (empty($id) || (is_numeric($id) && $id <= 0)) {
            $this->logError("ID de inversión inválido: {$id}");
            $this->lanzarExcepcionConCodigo("El ID de la inversión debe ser un valor positivo");
        }
        try {
            // Preparar consulta base
            $query = Inversion::query();
            $inversion = $query->findOrFail($id);
            $this->log("Inversión encontrada: {$inversion->codigo} (ID: {$id})");

            return $inversion;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->logError("Inversión no encontrada con ID: {$id}");
            throw $e; // Re-lanzar la excepción para mantener el comportamiento esperado
        } catch (\Exception $e) {
            $this->logError("Error al obtener inversión #{$id}: " . $e->getMessage());
            throw new \Exception("Error al obtener la inversión: " . $e->getMessage(), 0, $e);
        }
    }

    public function getInversiones(): Collection
    {
        return Inversion::all();
    }

    /**
     * Crea una nueva inversión y calcula la cuota de inversión
     *
     * @param array $inversionData Datos de la inversión
     * @return \App\Models\Inversion
     * @throws \Exception Si ocurre un error durante el proceso
     */
    public function createInversion(array $inversionData, $archivo): Inversion
    {
        // Validar los datos de la inversión
        $this->validarInversionData($inversionData);

        DB::beginTransaction();

        try {
            $this->log("Creando inversión con los siguientes datos: " . json_encode($inversionData));
            // Asignar valores predeterminados
            $inversionData['fecha'] = now();
            $inversionData['codigo'] = $this->createCode();

            // Crear la inversión
            $inversion = Inversion::create($inversionData);

            $inversion->path_documentacion = $this->guardarArchivoPrestamo($archivo, $inversion->codigo);
            $inversion->save();
            $existente = $inversionData['existente'] ?? false;
            if (!$existente) {
                $this->controladorEstado->cambiarEstado($inversion, ['estado' => EstadoInversion::$CREADO]);
            } else {
                $this->cambiarEstadosAutomaticamente($inversion, $inversionData);
            }

            DB::commit();
            $inversion->refresh();

            $this->log("Inversión creada con éxito: {$inversion->codigo}");
            return $inversion;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->manejarError($e, 'createInversion');
            // Esta línea nunca se alcanzará porque manejarError siempre lanza excepción
            throw new \Exception("Error inesperado en createInversion");
        }
    }

    private function cambiarEstadosAutomaticamente(Inversion $inversion, $datos): void
    {
        // Lógica para cambiar estados automáticamente según reglas de negocio
        $this->log("Verificando cambios automáticos de estado para la inversión ID: {$inversion->id}");
        $this->log("Estado actual de la inversión: {$inversion->id_estado}");
        $this->controladorEstado->cambiarEstado($inversion, ['estado' => EstadoInversion::$CREADO]);
        $this->log("Estado actual de la inversión después del primer cambio: {$inversion->id_estado}");
        $this->gestionarElDepositoAutomatico($inversion, $datos);

        // Refrescar la inversión para obtener el estado actualizado después del depósito
        $inversion->refresh();
        $this->log("Estado actual de la inversión después del depósito automático: {$inversion->id_estado}");
        $this->controladorEstado->cambiarEstado($inversion, ['estado' => EstadoInversion::$APROBADO, 'fecha_inicio'=>$datos['fecha_inicio'] ?? now()]);
         $this->log("Estado actual de la inversión después del cambio a APROBADO: {$inversion->id_estado}");
        $this->log("Estado actual de la inversión después del cambio a APROBADO: {$inversion->id_estado}");

        // Otras reglas pueden ser añadidas aquí
    }

    private function gestionarElDepositoAutomatico(Inversion $inversion, $datos): void
    {
        $this->log("Gestionando depósito automático para la inversión ID: {$inversion->id}");

        $depositoService = app(DepositoService::class);
        $datos = [
            'id_inversion' => $inversion->id,
            'monto' => $inversion->monto,
            'fecha_documento' => $inversion->fecha,
            'tipo_documento' => $datos['tipo_documento'] ?? 'inversion',
            'no_documento' => $datos['no_documento'] ?? $inversion->codigo,
            'numero_documento' => $datos['no_documento'] ?? $inversion->codigo,
            'motivo' => $datos['motivo'] ?? 'Depósito inicial de inversión ' . $inversion->codigo,
            'id_cuenta' => $this->tipoCuentaInternaService->getCuentaParaDepositosAnteriores()->id,
            'existente' => true // Flag para indicar que es un depósito automático
        ];

        // Usar crearDeposito en lugar de crearDepositoInterno para evitar transacciones anidadas
        $deposito = $depositoService->crearDeposito($datos);

        // Procesar el depósito inmediatamente ya que es automático
        $depositoService->depositar($deposito->id, $datos);

        $this->log("Depósito automático creado y procesado para la inversión ID: {$inversion->id}");
    }


    /**
     * Valida los datos de la inversión
     *
     * @param array $inversionData
     * @return void
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    private function validarInversionData(array $inversionData): void
    {
        // Validar los datos de la inversión aquí
        if ($inversionData['monto'] <= 0) {
            $this->lanzarExcepcionConCodigo("El monto de la inversión debe ser un número positivo");
        }
        if ($inversionData['interes'] <= 0) {
            $this->lanzarExcepcionConCodigo("El interés de la inversión debe ser un número positivo");
        }
        if ($inversionData['plazo'] <= 0) {
            $this->lanzarExcepcionConCodigo("El plazo de la inversión debe ser un número positivo");
        }
    }

    public function updateInversion(Inversion $inversion, array $inversionData): Inversion
    {
        $inversion->update($inversionData);
        return $inversion;
    }

    public function deleteInversion($id): void
    {
        DB::beginTransaction();
        $this->cuotaInversionService->deletePagoInversion($id);
        $inversion = Inversion::findOrFail($id);
        $inversion->delete();
        DB::commit();
    }

    public function cambiarEstado($id, $data)
    {
        // Verificar si ya hay una transacción activa
        $transactionStarted = false;
        if (!DB::transactionLevel()) {
            DB::beginTransaction();
            $transactionStarted = true;
        }

        try {
            $inversion = $this->getInversion($id);
            $this->controladorEstado->cambiarEstado($inversion, $data);

            if ($transactionStarted) {
                DB::commit();
            }
        } catch (\Exception $e) {
            if ($transactionStarted) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    public function getHistoricoInversion($id)
    {
        return Inversion::findOrFail($id)->historial;
    }

    public function getDepositosPendientes()
    {
        $inversiones = Inversion::where('id_estado', EstadoInversion::$CREADO)->get();
        $cuotasPendientes = collect();
        foreach ($inversiones as $inversion) {
            if (!$inversion->deposito) {
                continue;
            }
            $cuotas = $inversion->deposito()->where('realizado', false)->get();
            if ($cuotas->isNotEmpty()) {
                foreach ($cuotas as $cuota) {
                    $cuota->codigo_inversion = $inversion->codigo;
                }
                $cuotasPendientes = $cuotasPendientes->merge($cuotas);
            }
        }
        return $cuotasPendientes;
    }

    public function getDepositos($id)
    {
        $depositos = collect();
        $inversion = $this->getInversion($id);
        if ($inversion->deposito) {
            $depositos->push($inversion->deposito);
        }
        return  $depositos;
    }


    public function getPdf($id)
    {
        if ($id <= 0) {
            $this->lanzarExcepcionConCodigo("El ID de la inversion debe ser un número entero positivo");
        }
        $this->log("Iniciando generación de PDF para la inversion #{$id}");
        $inversion = $this->getInversion($id);
        $beneficiarios = $inversion->cliente->beneficiarios;
        $html = view('pdf.inversion', ['inversion' => $inversion, 'beneficiarios' => $beneficiarios])->render();
        return $this->pdfService->generatePdf($html);
    }

    public function guardarArchivoPrestamo($archivo, $codigoInversion)
    {
        $path = 'archivos/inversiones/documentacion';
        $fileName = 'inversion_' . $codigoInversion . '.pdf';
        // Usar el servicio de archivo para guardar el archivo
        return $this->archivoService->guardarArchivo($archivo, $path, $fileName);
    }

    public function generarPagosInversionExistenteExcel($id): array
    {
        $inversion = $this->getInversion($id);

        if(!$inversion->exists) {
            $this->lanzarExcepcionConCodigo("No se puede generar excel para una inversión que no es anterior. ID: {$id}");
        }
        return $this->pagoInversionExcelService->generarExcel($inversion);
    }

    public function procesarExcelPagosExistente($archivo,  $id): array
    {
        $inversion = $this->getInversion($id);
        if(!$inversion->existente) {
            $this->lanzarExcepcionConCodigo("No se puede procesar excel para una inversión que no es anterior. ID: {$id}");
        }
        return $this->pagoInversionExcelService->procesarExcelPagos($archivo, $inversion);
    }
}
