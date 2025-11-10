<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Beneficiario as BeneficiarioResource;

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
            'conyuge' => $this->conyuge,
            'cargas_familiares' => $this->cargas_familiares,
            'integrantes_nucleo_familiar' => $this->integrantes_nucleo_familiar,
            'tipo_vivienda' => $this->tipo_vivienda,
            'estabilidad_domiciliaria' => $this->estabilidad_domiciliaria,
            'razon_otros_ingresos' => $this->razon_otros_ingresos,
            'nacionalidad' => $this->nacionalidad,

            // Relaciones
            'beneficiarios' => BeneficiarioResource::collection($this->whenLoaded('beneficiarios')),

            // Datos enriquecidos (si existen)
            'nombreMunicipio' => $this->when(isset($this->nombreMunicipio), $this->nombreMunicipio),
            'nombreDepartamento' => $this->when(isset($this->nombreDepartamento), $this->nombreDepartamento),
            'estadoCivil' => $this->when(isset($this->estadoCivil), $this->estadoCivil),
            'nombreTipoCliente' => $this->when(isset($this->nombreTipoCliente), $this->nombreTipoCliente),
            'nombreProfesion' => $this->when(isset($this->nombreProfesion), $this->nombreProfesion),
            'nombreNivelAcademico' => $this->when(isset($this->nombreNivelAcademico), $this->nombreNivelAcademico),

            // Referencias clasificadas (si existen)
            'referenciasPersonales' => $this->when(isset($this->referenciasPersonales), $this->referenciasPersonales),
            'referenciasLaborales' => $this->when(isset($this->referenciasLaborales), $this->referenciasLaborales),
            'referenciasComerciales' => $this->when(isset($this->referenciasComerciales), $this->referenciasComerciales),
            'referenciasFamiliares' => $this->when(isset($this->referenciasFamiliares), $this->referenciasFamiliares),
        ];
    }


}
