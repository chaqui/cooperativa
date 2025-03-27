<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositoRequest extends FormRequest
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
            'tipo_documento' => 'required|string|max:255',
            'numero_documento' => 'required|string|max:255',
            'tipo_cuenta_interna_id' => 'required|integer|exists:tipo_cuenta_interna,id',
        ];
    }
}
