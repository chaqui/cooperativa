<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Cuota extends JsonResource
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
            'monto' => $this->monto,
            'fecha' => $this->saldo,
            'fecha_pago' => $this->fecha_pago,
            'realizado' => $this->realizado? 'Si': 'No',
        ];
    }
}
