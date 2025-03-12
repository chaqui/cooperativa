<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Prestamo extends JsonResource
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
            'interes' => $this->interes,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'estado' => $this->estado->nombre,
            'cliente' => $this->cliente->nombres.' '.$this->cliente->apellidos,
            'propiedad' => $this->propiedad->Descripcion,
            'dpi_cliente' => $this->dpi_cliente,
            'tipo_plazo' => $this->tipo_plazo,
            'fiador' => $this->fiador->nombre,
            'destino' => $this->destino,
            'uso_prestamo' => $this->uso_prestamo,
            'fiador_dpi' => $this->fiador_dpi,
            'tipo_garante' => $this->tipo_garante,
            'frecuencia_pago' => $this->frecuencia_pago,
            'parentesco' => $this->parentesco,

        ];
    }
}
