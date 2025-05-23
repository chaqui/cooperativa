<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagarCuota extends FormRequest
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
            'no_documento' => ['required', 'string'],
            'tipo_documento' => ['required', 'string'],
            'fecha_documento' => ['required', 'date'],
            'monto' => ['required', 'numeric'],
            'id_cuenta' => ['required', 'integer', 'exists:tipo_cuenta_interna,id'],

        ];
    }
}
