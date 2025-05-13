<?php

namespace App\Http\Requests;

use App\Constants\Roles;
use App\Traits\Authorizable;
use Illuminate\Foundation\Http\FormRequest;

class PrestamoRequest extends FormRequest
{
    use Authorizable;
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
        if ($this->input("existente")) {
            $validacionesBasicas = array_merge($validacionesBasicas, $this->validacionesSiExiste());
        }
        return $validacionesBasicas;
    }

    private function validacionesSiExiste(): array
    {
        return [
            'fecha_creacion' => 'required|string',
            'saldo' => 'required|numeric',
            'fecha_autorizacion' => 'required|string',
            'numero_cuota_pagada' => 'required|numeric',
            'fecha_desembolso' => 'required|string',
            'gastos_formalidad' => 'required|numeric',
            'gastos_administrativos' => 'required|numeric',
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
            'existente' => 'required|boolean',
        ];
    }
}
