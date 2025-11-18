<?php

namespace App\Http\Requests;

use App\Constants\Roles;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\Authorizable;

class ActualizarPrestamoRequest extends FormRequest
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
            "interes" => "required|numeric|min:0",
            'monto' => 'required|numeric',
            "plazo" => "required|integer|min:1",
            'tipo_plazo' => 'required|numeric',
            'uso_prestamo' => 'required|string',
            'propiedad_id' => 'required|numeric',
            'frecuencia_pago' => 'required|string',
            'destino' => 'required|string',
        ];
    }
}
