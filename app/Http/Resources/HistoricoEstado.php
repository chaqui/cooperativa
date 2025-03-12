<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoricoEstado extends JsonResource
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
            'nombre' => $this->estado->nombre,
            'razon' => $this->razon,
            'anotacion' => $this->anotacion,
            'no_documento_desembolso' => $this->no_documento_desembolso,
            'tipo_documento_desembolso' => $this->tipo_documento_desembolso,
            'fecha' => $this->fecha,
        ];
    }
}
