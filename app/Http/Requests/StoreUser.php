<?php

namespace App\Http\Requests;

use App\Constants\Roles;
use App\Traits\Authorizable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Http\FormRequest;

class StoreUser extends FormRequest
{
    use Authorizable;
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
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role_id' => ['required', 'integer'],
        ];
    }
}
