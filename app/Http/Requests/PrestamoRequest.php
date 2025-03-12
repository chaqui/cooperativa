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
        return [
            'monto' => 'required|numeric',
            'interes' => 'required|numeric',
            'destino' => 'required|string',
            'plazo' => 'required|numeric',
            'tipo_plazo' => 'required|string',
            'uso_prestamo' => 'required|string',
            'dpi_cliente' => 'required|string',
            'propiedad_id' => 'required|numeric',
            'fiador_dpi' => 'required|string',
            'tipo_garante' => 'required|string',
            'frecuencia_pago' => 'required|string',
            'parentesco' => 'required|string',
        ];
    }
}
