<?php

namespace App\EstadosInversion;

use App\Constants\EstadoInversion;

class InversionCreada extends EstadoBaseInversion
{
    public function __construct()
    {
        parent::__construct(null, EstadoInversion::$CREADO);
    }
}
