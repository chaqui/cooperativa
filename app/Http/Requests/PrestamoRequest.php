<?php

namespace App\Http\Requests;

use App\Constants\Roles;
use App\Traits\Authorizable;
use App\Traits\Loggable;
use Illuminate\Foundation\Http\FormRequest;

class PrestamoRequest extends FormRequest
{
    use Authorizable;
    use Loggable;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->authorizeRol([Roles::$ADMIN, Roles::$ASESOR]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        $validacionesBasicas = $this->validacionesBasicas();
        $this->log("Input existente: " . json_encode($this->input("existente")));
        $existente = filter_var($this->input("existente"), FILTER_VALIDATE_BOOLEAN);
        if ($existente) {
            $validacionesBasicas = array_merge($validacionesBasicas, $this->validacionesSiExiste());
        }
        return $validacionesBasicas;
    }

    private function validacionesSiExiste(): array
    {
        return [
            'fecha_creacion' => 'required|string',
            'fecha_autorizacion' => 'required|string',
            'fecha_desembolso' => 'required|string',
            'gastos_formalidad' => 'required|numeric',
            'gastos_administrativos' => 'required|numeric',
            'numero_documento' => 'required|string',
            'tipo_documento' => 'required|string',
        ];
    }

    private function validacionesBasicas(): array
    {
        return [
            'monto' => 'required|numeric',
            'interes' => 'required|numeric',
            'destino' => 'required|string',
            'plazo' => 'required|numeric',
            'tipo_plazo' => 'required|numeric',
            'uso_prestamo' => 'required|string',
            'dpi_cliente' => 'required|string',
            'propiedad_id' => 'required|numeric',
            'frecuencia_pago' => 'required|string',
        ];
    }
}
