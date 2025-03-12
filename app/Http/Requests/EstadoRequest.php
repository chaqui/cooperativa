<?php

namespace App\Http\Requests;

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
        if ($this->input("estado") === EstadoPrestamo::$DESEMBOLZADO) {
            $validaciones = array_merge($validaciones, $this->validacionesDesembolsado());
        }
        return $validaciones;
    }

    private function validacionesPrincipales(): array
    {
        return [
            'estado' => 'required|string',
        ];
    }
    private function validacionesDesembolsado(): array
    {
        return [
            'no_documento_desembolso' => 'required|string',
            'tipo_documento_desembolso' => 'required|string',
        ];
    }
}
