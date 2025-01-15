<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuentaBancaria extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'numero_cuenta' => $this->numero_cuenta,
            'tipo_cuenta' => $this->tipo_cuenta,
            'dpi_cliente' => $this->dpi_cliente,
            'nombre_banco' => $this->nombre_banco,
        ];
    }
}
