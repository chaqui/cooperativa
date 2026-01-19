<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Propiedad extends JsonResource
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
            'Direccion' => $this->Direccion,
            'Descripcion' => $this->Descripcion,
            'Valor_tasacion' => $this->Valor_tasacion,
            'Valor_comercial' => $this->Valor_comercial,
            'tipo_propiedad' => $this->tipo_propiedad,
            'dpi_cliente' => $this->dpi_cliente,
            'path_documentacion' => $this->path_documentacion,
        ];
    }
}
