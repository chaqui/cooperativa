<?php

namespace App\Http\Requests;

use App\Constants\EstadoInversion;
use App\Constants\EstadoPrestamo;
use App\Constants\Roles;
use App\Traits\Authorizable;
use Illuminate\Foundation\Http\FormRequest;

class EstadoRequest extends FormRequest
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
        $validaciones = $this->validacionesPrincipales();
        if ($this->input("estado") === EstadoPrestamo::$APROBADO) {
            $validaciones = array_merge($validaciones, $this->validacionsAprobado());
        }
        return $validaciones;
    }

    private function validacionesPrincipales(): array
    {
        return [
            'estado' => 'required|string',
        ];
    }

    private function validacionsAprobado(): array
    {
        return [
            'estado' => 'in:' . EstadoInversion::$APROBADO,
            'tipo_cuenta_interna_id' => 'required|integer|exists:tipo_cuenta_interna,id',
        ];
    }
}
