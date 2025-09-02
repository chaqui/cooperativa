<?php

namespace App\Services;


use App\Models\TipoCuentaInterna;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use App\Traits\ErrorHandler;

class TipoCuentaInternaService
{
    use ErrorHandler;
    use Loggable;

    private $cuentaInternaService;

    public function __construct(CuentaInternaService $cuentaInternaService)
    {
        $this->cuentaInternaService = $cuentaInternaService;
    }

    public function getAll()
    {
        return TipoCuentaInterna::where('visible', true)->get();
    }

    public function getCuentaParaDepositosAnteriores()
    {

        $tipoCuenta = TipoCuentaInterna::where('visible', false)->first();
        if (!$tipoCuenta) {
            $this->lanzarExcepcionConCodigo("No se encontró una cuenta interna para depósitos anteriores. Por favor, cree una cuenta con 'visible' en false.");
        }

        return $tipoCuenta;
    }

    public function getById($id)
    {
        try {
            $this->log("Buscando tipo de cuenta interna con ID: $id");

            $tipoCuenta = TipoCuentaInterna::findOrFail($id);

            $this->log("Tipo de cuenta interna encontrado: " .
                ($tipoCuenta->nombre_banco ?? $tipoCuenta->name ?? "Sin nombre") .
                " (ID: $id)");

            return $tipoCuenta;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->logError("Tipo de cuenta interna no encontrado con ID: $id");
            throw $e; // Reenviar la excepción original para mantener el comportamiento esperado
        } catch (\Exception $e) {
            $this->manejarError($e);
        }
    }

    /**
     * Crea un nuevo tipo de cuenta interna con su saldo inicial
     *
     * @param array $data Datos del tipo de cuenta interna:
     *        - name: (requerido) Nombre del tipo de cuenta
     *        - descripcion: (requerido) Descripción de la cuenta
     *        - saldo: (requerido) Saldo inicial de la cuenta (debe ser >= 0)
     *        - interes: (opcional) Monto de interés inicial (por defecto 0)
     *        - capital: (opcional) Monto de capital inicial (por defecto 0)
     * @return TipoCuentaInterna Instancia del tipo de cuenta interna creada
     * @throws \InvalidArgumentException Si faltan datos requeridos o son inválidos
     * @throws \Exception Si ocurre un error durante el proceso de creación
     */
    public function create(array $data)
    {


        DB::beginTransaction();

        try {
            // Crear el tipo de cuenta interna
            $tipoCuentaInterna = TipoCuentaInterna::create([
                'nombre_banco' => $data['nombre_banco'],
                'tipo_cuenta' => $data['tipo_cuenta'],
                'numero_cuenta' => $data['numero_cuenta'],
                'saldo_inicial' => $data['saldo'],
            ]);

            // Generar descripción para la transacción inicial
            $descripcionTransaccion = sprintf(
                'Saldo inicial para la cuenta "%s"',
                $tipoCuentaInterna->numero_cuenta
            );

            if (!empty($data['descripcion'])) {
                $descripcionTransaccion .= ' - ' . $data['descripcion'];
            }

            // Registrar el saldo inicial como una transacción en la cuenta
            $this->cuentaInternaService->createCuenta([
                'tipo_cuenta_interna_id' => $tipoCuentaInterna->id,
                'ingreso' => $data['saldo'],
                'egreso' => 0,
                'descripcion' => $descripcionTransaccion,
                'interes' => $data['interes'] ?? 0,
                'capital' => $data['capital'] ?? 0
            ]);

            DB::commit();

            return $tipoCuentaInterna;
        } catch (\Exception $e) {
            DB::rollBack();

            // Loguear el error si existe un trait de logging
            if (method_exists($this, 'logError')) {
                $this->logError("Error al crear tipo de cuenta interna: " . $e->getMessage());
            }

            throw new \Exception("No se pudo crear el tipo de cuenta interna: " . $e->getMessage(), 0, $e);
        }
    }


    public function getCuentas($id)
    {
        $this->log("Obteniendo cuentas para tipo de cuenta interna #$id");
        $tipoCuentaInterna = $this->getById($id);
        $this->log("Cuentas obtenidas: " . $tipoCuentaInterna->cuentaInternas->count());
        return $tipoCuentaInterna->cuentaInternas;
    }

    public function getDepositos($id)
    {
        $this->log("Obteniendo depósitos para tipo de cuenta interna #$id");
        $tipoCuentaInterna = $this->getById($id);
        $this->log("Depósitos obtenidos: " . $tipoCuentaInterna->depositos->count());
        return $tipoCuentaInterna->depositos;
    }

    public function getRetiros($id)
    {
        $this->log("Obteniendo retiros para tipo de cuenta interna #$id");
        $tipoCuentaInterna = $this->getById($id);
        $this->log("Retiros obtenidos: " . $tipoCuentaInterna->retiros->count());
        return $tipoCuentaInterna->retiros;
    }

    public function desbloquearMonto($id, $monto)
    {
        $this->log("Desbloqueando monto de $monto para tipo de cuenta interna #$id");
        $tipoCuentaInterna = $this->getById($id);
        $tipoCuentaInterna->monto_bloqueado = $tipoCuentaInterna->monto_bloqueado - $monto;
        $tipoCuentaInterna->save();
        $this->log("Monto Q{$monto} desbloqueado en cuenta interna #{$id}");
        return $tipoCuentaInterna;
    }
    public function bloquearMonto($id, $monto)
    {
        $this->log("Bloqueando monto de $monto para tipo de cuenta interna #$id");
        $tipoCuentaInterna = $this->getById($id);
        $tipoCuentaInterna->monto_bloqueado = $tipoCuentaInterna->monto_bloqueado + $monto;
        $tipoCuentaInterna->save();
        $this->log("Monto Q{$monto} bloqueado en cuenta interna #{$id}");
        return $tipoCuentaInterna;
    }
}
