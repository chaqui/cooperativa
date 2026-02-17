<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PrestamoHipotecario;

class Historico_Saldo extends Model
{
    protected $table = 'historial_saldo_interes';

    protected $fillable = [
        'saldo',
        'interes_pagado',
        'prestamo_hipotecario_id',
    ];

    public function prestamoHipotecario()
    {
        return $this->belongsTo(Prestamo_Hipotecario::class);
    }
}
