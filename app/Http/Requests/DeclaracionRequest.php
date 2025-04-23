<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\Authorizable;
use App\Constants\Roles;

class DeclaracionRequest extends FormRequest
{
    use Authorizable;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->authorizeRol([Roles::$ADMIN]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'numero_formulario' => 'required|string|max:50',
            'fecha_presentacion' => 'required|date',
        ];
    }
}
