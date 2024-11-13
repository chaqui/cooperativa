<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inversion extends Model
{
    protected $table = 'inversiones';
    protected $fillable = ['monto', 'interes', 'plazo', 'fecha',  'cliente', 'estado'];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

    public function estado()
    {
        return $this->belongsTo(Estado_Inversion::class, 'estado');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'inversion');
    }

    public function tipoTaza()
    {
        return $this->belongsTo(Tipo_Tasa_Interes::class, 'tipo_taza');
    }

    public function tipoPlazo()
    {
        return $this->belongsTo(Tipo_Plazo::class, 'tipo_plazo');
    }

    public function tipoInversion()
    {
        return $this->belongsTo(Tipo_Inversion::class, 'tipo_inversion');
    }

    public function pagosInversion()
    {
        return $this->hasMany(Pago_Inversion::class, 'inversion_id');
    }

    public function cuentaRecaudadora()
    {
        return $this->belongsTo(Cuenta_Bancaria::class, 'cuenta_recaudadora');
    }


}
