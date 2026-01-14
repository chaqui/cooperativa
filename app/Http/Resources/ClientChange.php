<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientChange extends JsonResource
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
            'cambios' => json_decode($this->cambios, true),
            'dpi_cliente' => $this->dpi_cliente,
            'fecha_modificacion' => $this->created_at,
            'usuario_modifico' => $this->usuario_modifico,
        ];
    }
}
