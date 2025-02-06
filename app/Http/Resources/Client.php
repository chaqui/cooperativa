<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Client extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'dpi' => $this->dpi,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'correo' => $this->correo,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'ciudad' => $this->ciudad,
            'departamento' => $this->departamento,
            'estado_civil'  => $this->estado_civil,
            'genero' => $this->genero,
            'nivel_academico' => $this->nivel_academico,
            'profesion' => $this->profesion,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'estado' => $this->estadoCliente->name,
            'limite_credito' => $this->limite_credito,
            'credito_disponible' => $this->credito_disponible,
            'ingresosMensuales' => $this->ingresos_mensuales,
            'egresos_mensuales' => $this->egresos_mensuales,
            'capacidad_pago' => $this->capacidad_pago,
            'calificacion' => $this->calificacion,
            'fecha_actualizacion_calificacion' => $this->fecha_actualizacion_calificacion,
            'nit' => $this->nit,
            'puesto' => $this->puesto,
            'fechaInicio' => $this->fechaInicio,
            'tipoCliente' => $this->tipoCliente,
            'otrosIngresos' => $this->otrosIngresos,
            'numeroPatente' => $this->numeroPatente,
            'nombreEmpresa' => $this->nombreEmpresa,
            'telefonoEmpresa' => $this->telefonoEmpresa,
            'direccionEmpresa' => $this->direccionEmpresa,
            'egresosMensuales' => $this->egresos_mensuales,
            'patente' => $this->numeroPatente,
            'path' => $this->path,
            'codigo' => $this->codigo,


        ];
    }


}
