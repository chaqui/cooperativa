<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Beneficiario extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'parentezco' => $this->parentezco,
            'porcentaje' => $this->porcentaje,
            'fecha_nacimiento' => $this->fecha_nacimiento, // Nuevo campo para fecha de nacimiento
        ];
    }
}
