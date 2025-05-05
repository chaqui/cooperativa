<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Cuota extends JsonResource
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
            'monto' => $this->monto(),
            'fecha' => $this->fecha,
            'fecha_pago' => $this->fecha_pago,
            'monto_interes' => $this->interes,
            'interes_pagado' => $this->interes_pagado,
            'amortizacion_pagada' => $this->capital_pagado,
            'amortizacion' => $this->capital,
            'penalizacion_pagada' => $this->recargo,
            'saldo' => $this->saldo,
            'realizado' => $this->realizado ,
            'id_prestamo' => $this->id_prestamo,
            'codigo_prestamo' => $this->prestamo->codigo,
            'monto_pagado' => $this->monto_pagado,
            'nuevo_saldo' => $this->nuevo_saldo,
            'saldo_faltante' => $this->saldoFaltante() < 0 ? 0 : $this->saldoFaltante(),
            'id_deposito' => $this->id_deposito,
            'penalizacion' => $this->penalizacion,
            'numero_deposito' => $this->depositos->count(),
            'numero_pago_prestamo' => $this->numero_pago_prestamo? $this->numero_pago_prestamo : 0,
        ];
    }
}
