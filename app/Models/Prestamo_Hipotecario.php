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
        'tipo_plazo',
        'fiador_dpi',
        'destino',
        'uso_prestamo',
        'fecha_aprobacion',
        'fecha_desembolso',
        'fecha_finalizacion',
        'fecha_cancelacion',
        'razon_cancelacion'
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

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_prestamo');
    }

    public function fiador()
    {
        return $this->belongsTo(Client::class, 'fiador_dpi');
    }
}
