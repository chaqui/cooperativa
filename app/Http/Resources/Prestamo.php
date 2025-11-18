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
            'cliente' => $this->cliente->getFullNameAttribute(),
            'propiedad' => $this->propiedad->Descripcion,
            'dpi_cliente' => $this->dpi_cliente,
            'tipo_plazo' => $this->tipo_plazo,
            'fiador' => $this->fiador_dpi ? $this->fiador->getFullNameAttribute() : null,
            'destino' => $this->destino,
            'uso_prestamo' => $this->uso_prestamo,
            'fiador_dpi' => $this->fiador_dpi ? $this->fiador->dpi : null,
            'tipo_garante' => $this->tipo_garante ? $this->tipo_garante : null,
            'frecuencia_pago' => $this->frecuencia_pago,
            'parentesco' => $this->parentesco,
            'codigo' => $this->codigo,
            'monto_liquido' => $this->montoLiquido(),
            'gastos_formalidad' => (float) $this->gastos_formalidad,
            'gastos_administrativos' => (float) $this->gastos_administrativos,
            'nombre_plazo' => $this->tipoPlazo->nombre,
            'plazo' => $this->plazo,
            'cuota' => $this->cuota,
            'intereses' => $this->intereses(),
            'saldo' => $this->saldoPendiente(),
            'morosidad' => $this->morosidad(),
            'asesor' => $this->asesor ? $this->asesor->name : null,
            'motivo_rechazo' => $this->getMotivoRechazo(),
            'propiedad_id' => $this->propiedad_id,
        ];
    }
}
