<?php

namespace App\Services;

use App\Models\Tipo_Plazo;

class TipoPlazoService
{

    public function getTipoPlazos()
    {
        return Tipo_Plazo::all();
    }

    public function getTipoPlazo($id)
    {
        return Tipo_Plazo::find($id);
    }
}
