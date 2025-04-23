<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Retiro extends JsonResource
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
            'tipo_documento' => $this->tipo_documento ?? null,
            'numero_documento' => $this->numero_documento ?? null,
            'imagen' => $this->imagen ?? null,
            'monto' => (float)$this->monto,
            'prestamo_id' => $this->id_prestamo ?? null,
            'codigo_prestamo' => $this->codigo_prestamo ?? null,
            'numero_cuenta_interna' => $this->tipoCuentaInterna->numero_cuenta ?? null,
            'tipo_cuenta_interna_id' => $this->tipoCuentaInterna->tipo_cuenta ?? null,
            'nombre_banco' => $this->tipoCuentaInterna->nombre_banco ?? null,
            'nombre_cliente' => $this->nombreCliente,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'realizado' => $this->realizado,
            'motivo' => $this->motivo ?? null,
            'gastos_administrativos' => $this->gastosAdministrativos ?? null,
            'gastos_formalidad' => $this->gastosFormalidad ?? null,
            'id_cuenta' => $this->tipoCuentaInterna->id ?? null,
            'codigo_inversion' => $this->codigoInversion ?? null,
            'cuenta_recaudadora' => $this->cuenta_recaudadora ?? null,
        ];
    }
}
