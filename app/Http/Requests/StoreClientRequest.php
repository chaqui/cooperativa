<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
    public function rules()
    {
        return [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dpi' => 'required|string|max:20|unique:clients,dpi',
            'telefono' => 'required|string|max:20',
            'direccion' => 'required|string|max:255',
            'correo' => 'email|unique:clients,email',
            'ciudad' => 'required|string|max:50',
            'departamento' => 'required|string|max:25',
            'estado_civil' => 'required|string|max:20',
            'genero' => 'required|string|max:12',
            'nivel_academico' => 'required|string|max:50',
            'profesion' => 'required|string|max:50',
            'fecha_nacimiento' => 'required|date',
        ];
    }
}
