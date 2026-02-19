<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PdfCuentasInternasRequest extends FormRequest
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
            'anio' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'mes' => 'nullable|integer|min:1|max:12',
        ];
    }

    public function messages(): array
    {
        return [
            'anio.required' => 'El año es requerido.',
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.min' => 'El año mínimo permitido es 1900.',
            'anio.max' => 'El año máximo permitido es ' . (date('Y') + 1) . '.',
            'mes.integer' => 'El mes debe ser un número entre 1 y 12.',
            'mes.min' => 'El mes mínimo es 1.',
            'mes.max' => 'El mes máximo es 12.',
        ];
    }

    protected function prepareForValidation()
    {
        // Normalizar cadena vacía a null para que 'nullable' funcione correctamente
        if ($this->has('mes') && $this->input('mes') === '') {
            $this->merge(['mes' => null]);
        }
    }
}
