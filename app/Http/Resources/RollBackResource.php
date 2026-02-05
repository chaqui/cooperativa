<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RollBackResource extends JsonResource
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
            'codigoPrestamo' => $this->prestamo_hipotecario_id,
            'razon' => $this->razon,
            'fecha_autorizacion' => $this->fecha_autorizacion,
            'usuario_autorizo' => $this->user ? $this->user->name : null,
            'rollback_id' => $this->rollback_id,
            'fecha_creacion' => $this->created_at,
            'datos_anteriores' => json_decode($this->datos_anteriores, true),
            'datos_nuevos' => json_decode($this->datos_nuevos, true),
        ];
    }
}
