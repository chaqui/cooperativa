<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo_Hipotecario extends Model
{
    protected $table = 'prestamo_hipotecarios';
    protected $fillable = [
        'dpi_cliente',
        'monto',
        'interes',
        'plazo',
        'fecha_inicio',
        'fecha_fin',
        'estado_id',
        'propiedad_id',
        'tipo_taza',
        'tipo_plazo',
    ];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class, 'propiedad_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado_Prestamo::class, 'estado_id');
    }

    public function tipoTaza()
    {
        return $this->belongsTo(Tipo_Tasa_Interes::class, 'tipo_taza');
    }

    public function tipoPlazo()
    {
        return $this->belongsTo(Tipo_Plazo::class, 'tipo_plazo');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_prestamo');
    }
}
