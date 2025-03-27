<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\Constants\Roles;
use App\Traits\Authorizable;

class TipoCuentaInternaRequest extends FormRequest
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
            'nombre_banco' => 'required|string|max:50',
            'tipo_cuenta' => 'required|string|max:35',
            'numero_cuenta' => 'required|string|max:25',
            'saldo' => 'required|numeric|min:0',
        ];
    }
}
