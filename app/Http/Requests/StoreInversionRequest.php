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
        return false;
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
            'tasa' => ['required', 'numeric'],
            'plazo' => ['required', 'numeric'],
            'fecha_inicio' => ['required', 'date'],
            'dpi_cliente' => ['required', 'string'],
        ];
    }
}
