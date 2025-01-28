<?php

namespace App\Http\Requests;

use App\Constants\TipoCliente;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        $rules = $this->getBasicRules();
        if ($this->input('tipoCliente') === TipoCliente::$EMPRESARIO) {
            $rules = $this->getRulesEmpresario(rules: $rules);
        } else {
            $rules = $this->getRulesAsalariado(rules: $rules);
        }
        return $rules;
    }

    /**
     *
     * Obtiene las reglas de validación básicas para un cliente.
     * @return array{apellidos: string, ciudad: string, correo: string, departamento: string, direccion: string, dpi: string, estado_civil: string, fechaInicio: string, fecha_nacimiento: string, genero: string, nivel_academico: string, nombres: string, profesion: string, referencias: string, referencias.*.nombre: string, referencias.*.telefono: string, referencias.*.tipo: string, telefono: string, tipoCliente: string}
     */
    private function getBasicRules()
    {
        return [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dpi' => 'required|string|max:20|unique:clients,dpi',
            'telefono' => 'required|string|max:20',
            'direccion' => 'required|string|max:255',
            'correo' => 'email|unique:clients,email',
            'ciudad' => 'required|string|max:50',
            'departamento' => 'required|string|max:25',
            'estado_civil' => 'required|string|max:20',
            'genero' => 'required|string|max:12',
            'nivel_academico' => 'required|string|max:50',
            'profesion' => 'required|string|max:50',
            'fecha_nacimiento' => 'required|date',
            'fechaInicio' => 'required|date',
            'tipoCliente' => 'required|string|max:20',
            'referencias' => 'required|array',
            'referencias.*.nombre' => 'required|string|max:255',
            'referencias.*.telefono' => 'required|string|max:20',
            'referencias.*.tipo' => 'required|string|max:20',
        ];
    }

    /**
     * Obtiene las reglas de validación para un cliente empresario.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function getRulesEmpresario($rules): array
    {
        $rules['nit'] = 'required|string|max:15';
        $rules['nombreEmpresa'] = 'required|string|max:255';
        $rules['telefonoEmpresa'] = 'required|string|max:20';
        $rules['direccionEmpresa'] = 'required|string|max:255';
        return $rules;
    }

    /**
     * Obtiene las reglas de validación para un cliente asalariado.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function getRulesAsalariado($rules): array
    {
        $rules['puesto'] = 'required|string|max:50';
        $rules['otrosIngresos'] = 'required|numeric';
        $rules['nombreEmpresa'] = 'required|string|max:255';
        return $rules;
    }
}
