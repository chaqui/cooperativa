<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Deposito extends JsonResource
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
            'tipo_documento' => $this->tipo_documento?? null,
            'numero_documento' => $this->numero_documento?? null,
            'imagen' => $this->imagen?? null,
            'monto' => (float)$this->monto,
            'inversion_id' => $this->inversion_id,
            'codigo_inversion' => $this->codigo_inversion?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
