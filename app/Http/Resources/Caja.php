<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Caja extends JsonResource
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
            'ingreso' => (float) $this->ingreso,
            'interes' => (float) $this->interes,
            'capital' => (float) $this->capital,
            'egreso' => (float) $this->egreso,
            'descripcion' => $this->descripcion,
            'fecha' => $this->created_at,
        ];
    }
}
