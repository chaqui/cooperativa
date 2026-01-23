<?php

namespace App\Services;

use App\Models\Prestamo_Remplazo;


class PrestamoRemplazadoService
{
    public function registrarPrestamoRemplazo(int $prestamoCanceladoId, int $prestamoRemplazoId): Prestamo_Remplazo
    {
        return Prestamo_Remplazo::create([
            'prestamo_cancelado' => $prestamoCanceladoId,
            'prestamo_remplazo' => $prestamoRemplazoId,
        ]);
    }
}
