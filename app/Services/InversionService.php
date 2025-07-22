<?php

namespace App\Services;

use App\Constants\EstadoInversion;
use App\EstadosInversion\ControladorEstado;

use App\Traits\Loggable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Constants\InicialesCodigo;

use App\Models\Inversion;

class InversionService extends CodigoService
{

    use Loggable;
    private  CuotaInversionService $cuotaInversionService;

    private ControladorEstado $controladorEstado;

    private PdfService $pdfService;

    public function __construct(
        CuotaInversionService $cuotaInversionService,
        ControladorEstado $controladorEstado,
        PdfService $pdfService
    ) {
        $this->cuotaInversionService = $cuotaInversionService;
        $this->controladorEstado = $controladorEstado;
        $this->pdfService = $pdfService;
        parent::__construct(InicialesCodigo::$Inversion);
    }

    public function getInversion(string $id): Inversion
    {
        // Validar que el ID sea un valor válido
        if (empty($id) || (is_numeric($id) && $id <= 0)) {
            $this->logError("ID de inversión inválido: {$id}");
            throw new \InvalidArgumentException("El ID de la inversión debe ser un valor positivo");
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
    public function createInversion(array $inversionData): Inversion
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

            // Cambiar el estado de la inversión a "CREADO"
            $this->controladorEstado->cambiarEstado($inversion, ['estado' => EstadoInversion::$CREADO]);

            DB::commit();

            $this->log("Inversión creada con éxito: {$inversion->codigo}");
            return $inversion;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError("Error al crear la inversión: " . $e->getMessage());
            throw new \Exception("Error al crear la inversión: " . $e->getMessage(), 0, $e);
        }
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
            throw new \InvalidArgumentException("El monto de la inversión debe ser un número positivo");
        }
        if ($inversionData['interes'] <= 0) {
            throw new \InvalidArgumentException("El interés de la inversión debe ser un número positivo");
        }
        if ($inversionData['plazo'] <= 0) {
            throw new \InvalidArgumentException("El plazo de la inversión debe ser un número positivo");
        }

        $beneficiarios = $inversionData['beneficiarios'] ?? [];
        if (empty($beneficiarios) || !is_array($beneficiarios)) {
            throw new \InvalidArgumentException("Los beneficiarios deben ser un arreglo");
        }
        $totalPorcentaje = 0;
        foreach ($beneficiarios as $beneficiario) {
            $totalPorcentaje += $beneficiario['porcentaje'];
        }
        if ($totalPorcentaje != 100) {
            throw new \InvalidArgumentException("El total de los porcentajes de los beneficiarios debe ser 100");
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
        DB::beginTransaction();
        $inversion = $this->getInversion($id);
        $this->controladorEstado->cambiarEstado($inversion, $data);
        DB::commit();
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
            throw new \InvalidArgumentException("El ID de la inversion debe ser un número entero positivo");
        }
        $this->log("Iniciando generación de PDF para la inversion #{$id}");
        $inversion = $this->getInversion($id);
        $html = view('pdf.inversion', ['inversion' => $inversion])->render();
        return $this->pdfService->generatePdf($html);
    }
}
