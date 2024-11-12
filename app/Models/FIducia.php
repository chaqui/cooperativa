<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FIducia extends Model
{
    protected $table = 'fiducias';
    protected $fillable = [
        'id_tipo_fiducia',
        'id_cliente',
        'monto',
        'plazo',
        'fecha_inicio',
        'fecha_fin',
        'id_tipo_plazo',
        'id_estado_fiducia',
        'interes',
        'interes_mora',
        'id_tipo_interes'
    ];

    public function tipo_fiducia()
    {
        return $this->belongsTo(Tipo_Fiducia::class, 'id_tipo_fiducia');
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'id_cliente');
    }

    public function tipo_plazo()
    {
        return $this->belongsTo(Tipo_Plazo::class, 'id_tipo_plazo');
    }

    public function estado_fiducia()
    {
        return $this->belongsTo(Estado_Prestamo::class, 'id_estado_fiducia');
    }
}
