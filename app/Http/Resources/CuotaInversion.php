<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuotaInversion extends JsonResource
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
            'monto' => (float) $this->monto,
            'monto_interes' => (float) $this->montoInteres,
            'isr_retenido' => (float) $this->montoISR,
            'fecha' => $this->fecha,
            'fecha_pago' => $this->fecha_pago,
            'no_boleta' => $this->no_boleta,
            'realizado' => $this->realizado? 'Si': 'No',
            'inversion_id' => $this->inversion_id,
        ];
    }
}
