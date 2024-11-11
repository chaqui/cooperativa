<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo_Hipotecario extends Model
{
    protected $table = 'prestamo_hipotecarios';
    protected $fillable = ['monto', 'interes', 'plazo', 'cuota', 'fecha_inicio', 'fecha_fin',  'cliente', 'propiedad'];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'cliente');
    }

    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class, 'propiedad');
    }

    public function estado()
    {
        return $this->belongsTo(Estado_Prestamo::class, 'estado');
    }

    public function tipo_taza()
    {
        return $this->belongsTo(Tipo_Tasa_Interes::class, 'tipo_taza');
    }

    public function tipo_plazo()
    {
        return $this->belongsTo(Tipo_Plazo::class, 'tipo_plazo');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'prestamo');
    }
}
