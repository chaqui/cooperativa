<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inversion extends Model
{
    protected $table = 'inversiones';
    protected $fillable = [
        'monto',
        'interes',
        'plazo',
        'id_estado',
        'tipo_taza',
        'tipo_plazo',
        'cuenta_recaudadora',
        'dpi_cliente',
        'fecha',
        'fecha_inicio',
        'codigo'
    ];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'id_estado');
    }

    public function tipoTaza()
    {
        return $this->belongsTo(Tipo_Tasa_Interes::class, 'tipo_taza');
    }

    public function tipoPlazo()
    {
        return $this->belongsTo(Tipo_Plazo::class, 'tipo_plazo');
    }


    public function pagosInversion()
    {
        return $this->hasMany(Pago_Inversion::class, 'inversion_id');
    }

    public function cuentaRecaudadora()
    {
        return $this->belongsTo(Cuenta_Bancaria::class, 'cuenta_recaudadora');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstado::class, 'id_inversion');
    }

    public function deposito()
    {
        return $this->hasOne(Deposito::class, 'id_inversion');
    }
}
