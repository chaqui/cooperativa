<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Inversion extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'monto' => $this->monto,
            'tasa' => $this->tasa,
            'plazo' => $this->plazo,
            'fecha_inicio' => $this->fecha,
            'dpi_cliente' => $this->dpi_cliente,
            'cliente' => $this->cliente->nombres . ' ' . $this->cliente->apellidos,
        ];
    }
}
