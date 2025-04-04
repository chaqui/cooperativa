<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoCuentaInterna extends JsonResource
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
            'nombre_banco' => $this->nombre_banco,
            'tipo_cuenta' => $this->tipo_cuenta,
            'numero_cuenta' => $this->numero_cuenta,
            'saldo' => $this->saldo(),
            'ingresos' => $this->ingresos(),
            'egresos' => $this->egresos(),
            'monto_bloqueado' => $this->monto_bloqueado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
