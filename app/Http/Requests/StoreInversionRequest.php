<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInversionRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric'],
            'interes' => ['required', 'numeric'],
            'plazo' => ['required', 'numeric'],
            'fecha_inicio' => ['required', 'date'],
            'dpi_cliente' => ['required', 'string'],
            'cuenta_recaudadora' => ['required', 'string'],
            'tipo_plazo' => ['required', 'numeric'],
            'beneficiarios' => ['required', 'array'],
            'beneficiarios.*.nombre' => ['required', 'string'],
            'beneficiarios.*.parentezco' => ['required', 'string'],
            'beneficiarios.*.porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
