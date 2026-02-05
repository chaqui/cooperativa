<?php

namespace App\Services;

use App\Models\Cuenta_Interna;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use App\Traits\ErrorHandler;
use App\Traits\RegistrarRollback;
use App\Constants\RollBackCampos;

class CuentaInternaService
{

    use ErrorHandler;
    use Loggable;
    use RegistrarRollback;
    /**
     * Obtiene todas las cuentas internas
     *
     * @return \Illuminate\Database\Eloquent\Collection Colección de cuentas internas
     */
    public function getAllCuentas()
    {
        return Cuenta_Interna::all();
    }

    /**
     * Crea un nuevo registro en la cuenta interna
     *
     * @param array $data Datos de la transacción con las siguientes claves:
     *        - ingreso: (requerido) Monto que ingresa a la cuenta (debe ser >= 0)
     *        - egreso: (requerido) Monto que sale de la cuenta (debe ser >= 0)
     *        - descripcion: (requerido) Descripción de la transacción
     *        - tipo_cuenta_interna_id: (requerido) ID del tipo de cuenta interna
     *        - interes: (opcional) Monto correspondiente a intereses
     *        - capital: (opcional) Monto correspondiente a capital
     *        - referencia_externa: (opcional) Identificador externo relacionado con la transacción
     * @return Cuenta_Interna Instancia del registro creado
     * @throws \InvalidArgumentException Si los datos proporcionados son inválidos
     * @throws \Exception Si ocurre un error durante el proceso de creación
     */
    public function createCuenta(array $data)
    {
        // Validar datos requeridos
        $this->validarDatosCuenta($data);

        // Iniciar transacción
        DB::beginTransaction();

        try {
            // Preparar datos con valores por defecto para campos opcionales
            $cuentaData = [
                'ingreso' => (float) $data['ingreso'],
                'egreso' => (float) $data['egreso'],
                'descripcion' => trim($data['descripcion']),
                'tipo_cuenta_interna_id' => $data['tipo_cuenta_interna_id'],
                'ganancia' => isset($data['interes']) ? (float) $data['interes'] : 0,
                'capital' => isset($data['capital']) ? (float) $data['capital'] : 0,
                'fecha' => $data['fecha'] ?? now(),
            ];

            // Crear el registro
            $cuenta = Cuenta_Interna::create($cuentaData);
            if ($data['id_prestamo_hipotecario'] ?? false) {
                $this->agregarDatosEliminar($data['id_prestamo_hipotecario'], $cuenta->id, RollBackCampos::$cuentasInternas);
            }

            DB::commit();

            return $cuenta;
        } catch (\Exception $e) {
            $this->manejarError($e);
        }
    }

    /**
     * Valida los datos para crear un registro en la cuenta interna
     *
     * @param array $data Datos a validar
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    private function validarDatosCuenta(array $data)
    {
        // Validar campos requeridos
        $camposRequeridos = ['ingreso', 'egreso', 'descripcion', 'tipo_cuenta_interna_id', 'capital', 'interes'];

        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo])) {
                throw new \InvalidArgumentException("El campo '{$campo}' es requerido");
            }
        }

        // Validar que ingreso y egreso sean numéricos y positivos
        if (!is_numeric($data['ingreso']) || !is_numeric($data['egreso'])) {
            throw new \InvalidArgumentException("Los campos 'ingreso' y 'egreso' deben ser valores numéricos");
        }

        if ((float) $data['ingreso'] < 0 || (float) $data['egreso'] < 0) {
            throw new \InvalidArgumentException("Los campos 'ingreso' y 'egreso' deben ser mayores o iguales a cero");
        }

        // Validar descripción
        if (trim($data['descripcion']) === '') {
            $this->lanzarExcepcionConCodigo("La descripción no puede estar vacía");
        }

        // Validar tipo_cuenta_interna_id
        if (!is_numeric($data['tipo_cuenta_interna_id']) || (int) $data['tipo_cuenta_interna_id'] <= 0) {
            $this->lanzarExcepcionConCodigo("El tipo de cuenta interna debe ser un valor numérico positivo");
        }

        // Validar campos opcionales numéricos
        $camposNumericosOpcionales = ['interes', 'capital'];

        foreach ($camposNumericosOpcionales as $campo) {
            if (isset($data[$campo]) && (!is_numeric($data[$campo]) || (float) $data[$campo] < 0)) {
                throw new \InvalidArgumentException("El campo '{$campo}' debe ser un valor numérico mayor o igual a cero");
            }
        }

        // Validar fecha si está presente
        if (isset($data['fecha']) && !$this->esFormatoFechaValido($data['fecha'])) {
            $this->lanzarExcepcionConCodigo("El formato de fecha proporcionado no es válido");
        }
    }

    /**
     * Verifica si una fecha tiene formato válido
     *
     * @param string $fecha Fecha a verificar
     * @return bool True si la fecha tiene formato válido
     */
    private function esFormatoFechaValido($fecha)
    {
        if (is_string($fecha)) {
            try {
                new \DateTime($fecha);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        return $fecha instanceof \DateTime || $fecha instanceof \Carbon\Carbon;
    }
}
