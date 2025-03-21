<?php

namespace App\Services;

use App\Constants\EstadoPrestamo;
use App\Models\Retiro;
use Illuminate\Support\Facades\DB;

class RetiroService
{

    private $cuentaInternaService;


    public function __construct(CuentaInternaService $cuentaInternaService)
    {
        $this->cuentaInternaService = $cuentaInternaService;

    }
    // Crear un nuevo retiro
    public function crearRetiro(array $data)
    {
        $saldo = $this->cuentaInternaService->getSaldo();
        if ($saldo < $data['monto']) {
            throw new \Exception('El monto a retirar es mayor al saldo disponible.');
        }
        $data['realizado'] = false;
        return Retiro::create($data);
    }

    // Obtener todos los retiros
    public function obtenerRetiros()
    {
        return Retiro::all();
    }

    // Realizar un retiro con lógica de validación y transacción
    public function realizarRetiro(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $retiro = Retiro::findOrFail($id);
            if ($retiro->realizado) {
                throw new \Exception('El retiro ya ha sido realizado.');
            }
            $retiro->tipo_documento = $data['tipo_documento'];
            $retiro->numero_documento = $data['numero_documento'];
            $retiro->imagen = $data['imagen']?? null;
            $retiro->realizado = true;
            $retiro->save();

            $retiro->realizado = true;
            $retiro->save();


            // Si el retiro está relacionado con un préstamo hipotecario, actualizar su estado
            if ($retiro->id_prestamo) {
                $prestamoHipotecarioService = app(PrestamoService::class);
                $data['estado'] = EstadoPrestamo::$DESEMBOLZADO;
                $prestamoHipotecarioService->cambiarEstado($retiro->id_prestamo, $data);
            }

            DB::commit();
            return $retiro;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
