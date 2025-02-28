<?php

namespace App\Http\Requests;

use App\Constants\Roles;
use Illuminate\Foundation\Http\FormRequest;

class PropiedadRequest extends FormRequest
{
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
            'Direccion' => 'required|string|max:255',
            'Descripcion' => 'required|string|max:255',
            'Valor_tasacion' => 'required|numeric',
            'Valor_comercial' => 'required|numeric',
            'tipo_propiedad' => 'required|string|max:50',
            'dpi_cliente' => 'required|string|max:20',
        ];
    }
}
