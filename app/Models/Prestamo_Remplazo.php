<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Prestamo_Remplazo extends Model
{
    protected $table = 'prestamos_reemplazo';

    protected $fillable = [
        'prestamo_cancelado',
        'prestamo_remplazo',
    ];

    public function prestamoCancelado()
    {
        return $this->belongsTo(Prestamo_Hipotecario::class, 'prestamo_cancelado');
    }

    public function prestamoRemplazo()
    {
        return $this->belongsTo(Prestamo_Hipotecario::class, 'prestamo_remplazo');
    }
}
